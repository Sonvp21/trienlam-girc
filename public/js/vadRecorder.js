/** Port 1:1 từ useVadRecorder.js — không đổi logic. */
export async function recordWithVad({
  maxMs = 8000,
  silenceMs = 1300,
  startTimeoutMs = 5000,
  onLevel = null,
  onSpeech = null,
  signal = null,
} = {}) {
  const stream = await navigator.mediaDevices.getUserMedia({
    audio: {
      echoCancellation: true,
      noiseSuppression: true,
      autoGainControl: true,
      channelCount: 1,
    },
  });

  const AudioCtx = window.AudioContext || window.webkitAudioContext;
  const ctx = new AudioCtx();
  const source = ctx.createMediaStreamSource(stream);
  const analyser = ctx.createAnalyser();
  analyser.fftSize = 1024;
  source.connect(analyser);
  const buf = new Float32Array(analyser.fftSize);

  const mime = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '';
  const rec = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
  const chunks = [];
  rec.ondataavailable = (e) => { if (e.data.size) chunks.push(e.data); };
  const stopped = new Promise((r) => (rec.onstop = r));
  rec.start();

  const t0 = performance.now();
  let calibSum = 0;
  let calibCount = 0;
  let noiseFloor = 0.003;
  let spoke = false;
  let speechStart = 0;
  let lastVoice = 0;
  let aboveSince = null;
  let cancelled = signal?.aborted ?? false;

  const MIN_ONSET_MS = 220;

  await new Promise((resolve) => {
    const onAbort = () => { cancelled = true; };
    signal?.addEventListener('abort', onAbort);

    const tick = () => {
      if (cancelled) { signal?.removeEventListener('abort', onAbort); resolve(); return; }

      analyser.getFloatTimeDomainData(buf);
      let sum = 0;
      for (let i = 0; i < buf.length; i++) sum += buf[i] * buf[i];
      const rms = Math.sqrt(sum / buf.length);

      const now = performance.now();
      const elapsed = now - t0;

      const calibrating = elapsed < 350;
      if (calibrating) {
        calibSum += rms;
        calibCount++;
        noiseFloor = calibSum / calibCount;
      }

      const threshold = Math.min(Math.max(noiseFloor * 2.0, 0.014), 0.06);

      if (onLevel) onLevel(Math.min(1, rms / 0.12));

      if (!calibrating) {
        if (rms > threshold) {
          if (aboveSince === null) aboveSince = now;
          if (!spoke && now - aboveSince >= MIN_ONSET_MS) {
            spoke = true;
            speechStart = aboveSince;
            if (onSpeech) onSpeech();
          }
          if (spoke) lastVoice = now;
        } else {
          aboveSince = null;
        }
      }

      const done =
        elapsed >= maxMs ||
        (!spoke && elapsed >= startTimeoutMs) ||
        (spoke && now - lastVoice >= silenceMs && now - speechStart >= 400);

      if (done) { signal?.removeEventListener('abort', onAbort); resolve(); }
      else setTimeout(tick, 50);
    };
    tick();
  });

  rec.stop();
  await stopped;
  stream.getTracks().forEach((t) => t.stop());
  ctx.close().catch(() => {});

  return { blob: new Blob(chunks, { type: mime || 'audio/webm' }), spoke, cancelled };
}
