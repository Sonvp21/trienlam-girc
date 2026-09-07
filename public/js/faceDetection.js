/**
 * Port 1:1 từ useFaceDetection.js (Vue composable) sang JS thuần — không đổi logic,
 * chỉ bỏ cú pháp ES module export dạng khác đi (vẫn giữ export function để dùng qua <script type="module">).
 */
export async function startFaceDetection(videoEl, { onFaceChange, onMouthActivity } = {}) {
  const { FaceLandmarker, FilesetResolver } = await import(
    'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/vision_bundle.mjs'
  );

  const vision = await FilesetResolver.forVisionTasks(
    'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm'
  );

  const landmarker = await FaceLandmarker.createFromOptions(vision, {
    baseOptions: {
      modelAssetPath:
        'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task',
      delegate: 'GPU',
    },
    runningMode: 'VIDEO',
    numFaces: 1,
    outputFaceBlendshapes: false,
    outputFacialTransformationMatrixes: false,
  });

  const MIN_FACE_AREA_RATIO = 0.03;

  function faceAreaRatio(landmarks) {
    let minX = 1, maxX = 0, minY = 1, maxY = 0;
    for (const p of landmarks) {
      if (p.x < minX) minX = p.x;
      if (p.x > maxX) maxX = p.x;
      if (p.y < minY) minY = p.y;
      if (p.y > maxY) maxY = p.y;
    }
    return (maxX - minX) * (maxY - minY);
  }

  function dist(a, b) {
    const dx = a.x - b.x, dy = a.y - b.y;
    return Math.sqrt(dx * dx + dy * dy);
  }

  const UPPER_LIP = 13, LOWER_LIP = 14, MOUTH_LEFT = 61, MOUTH_RIGHT = 291;

  function mouthOpenRatio(landmarks) {
    const u = landmarks[UPPER_LIP], l = landmarks[LOWER_LIP];
    const lc = landmarks[MOUTH_LEFT], rc = landmarks[MOUTH_RIGHT];
    if (!u || !l || !lc || !rc) return 0;
    const mouthWidth = dist(lc, rc) || 1e-6;
    return dist(u, l) / mouthWidth;
  }

  const MAR_WINDOW_MS = 1300;
  const MAR_MOVE_EPS = 0.014;
  const MAR_TALK_DELTA = 0.045;
  const MIN_OSCILLATIONS = 4;
  let marHistory = [];

  function updateMarHistory(v) {
    const now = performance.now();
    marHistory.push({ t: now, v });
    marHistory = marHistory.filter((s) => now - s.t <= MAR_WINDOW_MS);
  }

  function isMouthTalking() {
    if (marHistory.length < 10) return false;
    let max = -Infinity, min = Infinity;
    let prevDir = 0;
    let oscillations = 0;
    for (let i = 0; i < marHistory.length; i++) {
      const v = marHistory[i].v;
      if (v > max) max = v;
      if (v < min) min = v;
      if (i === 0) continue;
      const diff = v - marHistory[i - 1].v;
      if (Math.abs(diff) < MAR_MOVE_EPS) continue;
      const dir = diff > 0 ? 1 : -1;
      if (prevDir !== 0 && dir !== prevDir) oscillations++;
      prevDir = dir;
    }
    return oscillations >= MIN_OSCILLATIONS && (max - min) > MAR_TALK_DELTA;
  }

  const WARMUP_MS = 1200;
  let firstValidFrameAt = null;
  const REQUIRED_CONSECUTIVE_FRAMES = 4;
  let consecutiveTrue = 0;

  let running = true;
  let lastVideoTime = -1;
  let loggedError = false;

  const loop = () => {
    if (!running) return;
    if (videoEl.readyState >= 2 && videoEl.videoWidth > 0 && videoEl.currentTime !== lastVideoTime) {
      lastVideoTime = videoEl.currentTime;
      if (firstValidFrameAt === null) firstValidFrameAt = performance.now();
      const warmingUp = performance.now() - firstValidFrameAt < WARMUP_MS;

      try {
        const result = landmarker.detectForVideo(videoEl, performance.now());
        const landmarks = result.faceLandmarks?.[0];
        const raw = !warmingUp && !!landmarks && faceAreaRatio(landmarks) >= MIN_FACE_AREA_RATIO;
        consecutiveTrue = raw ? consecutiveTrue + 1 : 0;
        const hasFace = consecutiveTrue >= REQUIRED_CONSECUTIVE_FRAMES;
        onFaceChange?.(hasFace);

        if (hasFace && landmarks) {
          updateMarHistory(mouthOpenRatio(landmarks));
          onMouthActivity?.(isMouthTalking());
        } else {
          marHistory = [];
          onMouthActivity?.(false);
        }
      } catch (e) {
        if (!loggedError) {
          loggedError = true;
          console.error('[useFaceDetection] Lỗi detectForVideo:', e);
        }
      }
    }
    requestAnimationFrame(loop);
  };
  requestAnimationFrame(loop);

  return () => {
    running = false;
    landmarker.close();
  };
}
