import { startFaceDetection } from './faceDetection.js';
import { recordWithVad } from './vadRecorder.js';

// ===================== Cấu hình / hằng số (giữ nguyên từ App.vue) =====================
const DEFAULT_VIDEO_ID = 'LLdSjNTCoyU'; // !! THAY bằng ID video YouTube giới thiệu thật !!
const DEFAULT_VIDEO_URL = `https://www.youtube.com/embed/${DEFAULT_VIDEO_ID}?autoplay=1&mute=1&loop=1&playlist=${DEFAULT_VIDEO_ID}&enablejsapi=1`;
const GREETING_TEXT = 'Xin chào bạn, bạn muốn xem dự án gì?';
const STABLE_MS = 1500;
const IDLE_RESET_MS = 3000;
const MOUTH_TRIGGER_COOLDOWN_MS = 900;
const MAX_AUTO_RETRY = 3;

const CATEGORIES = [
  { name: 'Chính phủ số', icon: 'account_balance', image: '/chinhphuso.jpg', chip: 'bg-blue-50 text-blue-700', accent: 'text-blue-700' },
  { name: 'Kinh tế số', icon: 'monitoring', image: '/kinhteso.jpg', chip: 'bg-amber-50 text-amber-700', accent: 'text-amber-700' },
  { name: 'Xã hội số', icon: 'diversity_3', image: '/xahoiso.jpg', chip: 'bg-teal-50 text-teal-700', accent: 'text-teal-700' },
];

const ITEM_ICONS = {
  'thoi-tiet': 'thunderstorm', 'thuy-loi': 'water_drop', 'khoang-san': 'terrain',
  'moi-truong-khong-khi': 'airwave', 'dat-dai': 'map', 'chuyen-doi-so-xa': 'apartment',
  'ocop': 'storefront', 'so-huu-tri-tue': 'copyright', 'nong-lam-nghiep': 'agriculture',
  'benh-ly-phoi': 'pulmonology', 'lop-hoc-so': 'school', 'thu-vien-nong-dan': 'local_library',
  'truy-xuat-nguon-goc': 'qr_code_scanner', 'tuoi-che-thong-minh': 'sensors',
  'bao-cao-thong-ke-nn': 'agriculture', 'bac-si-ai-cay-trong': 'eco',
};
const itemIcon = (id) => ITEM_ICONS[id] || 'play_circle';

const normVi = (s) => (s || '').toLowerCase().replace(/đ/g, 'd').normalize('NFD').replace(/[\u0300-\u036f]/g, '');

const PAUSE_KEYWORDS = ['tam dung', 'dung lai', 'tam ngung', 'ngung lai', 'dung video', 'pause'];
const isPauseCommand = (text) => PAUSE_KEYWORDS.some((k) => normVi(text).includes(k));

const RESUME_KEYWORDS = ['tiep tuc', 'phat tiep', 'chieu tiep', 'xem tiep', 'resume', 'play'];
const isResumeCommand = (text) => RESUME_KEYWORDS.some((k) => normVi(text).includes(k));

// ===================== State (thay cho ref() của Vue) =====================
const state = {
  phase: 'init', // init | detect | greeting | listening | processing | ready
  statusText: 'Đang tải mô hình nhận diện...',
  projects: [],
  selected: null,
  videoSrc: DEFAULT_VIDEO_URL,
  voiceMessage: '',
  processingLabel: 'Đang xử lý...',
  matchedToast: null,
  listenState: 'waiting',
  levels: Array(24).fill(0.05),
  faceVisible: false,
  mouthTalking: false,
  searchQuery: '',
  activeField: 'Tất cả',
  projectsLoaded: false, // tránh hiện nhầm thông báo trong lúc chờ fetch lần đầu
};

let stopDetection = null;
let lastFaceAt = performance.now();
let autoRetried = false;
let toastTimer = null;
let listenAbort = null;
let greetingPlaying = false;
let noMatchStreak = 0;
let autoRetryTimer = null;
let bgArmed = false;
let mouthTriggerCooldownUntil = 0;
let firstSeen = null;

// ===================== DOM refs =====================
const el = {};
function cacheEls() {
  el.cameraOverlay = document.getElementById('cameraOverlay');
  el.circleWrap = document.getElementById('circleWrap');
  el.camRing = document.getElementById('camRing');
  el.camVideo = document.getElementById('camVideo');
  el.liveDot = document.getElementById('liveDot');

  el.matchedToast = document.getElementById('matchedToast');
  el.matchedToastName = document.getElementById('matchedToastName');

  el.voiceBox = document.getElementById('voiceBox');
  el.voiceListening = document.getElementById('voiceListening');
  el.voiceListenLabel = document.getElementById('voiceListenLabel');
  el.voiceBars = document.getElementById('voiceBars');
  el.voiceProcessing = document.getElementById('voiceProcessing');
  el.voiceProcessingLabel = document.getElementById('voiceProcessingLabel');
  el.voiceMessageBox = document.getElementById('voiceMessageBox');
  el.voiceMessageText = document.getElementById('voiceMessageText');

  el.micButton = document.getElementById('micButton');

  el.projectListWrap = document.getElementById('projectListWrap');
  el.noProjects = document.getElementById('noProjects');
  el.noProjectsQuery = document.getElementById('noProjectsQuery');

  el.videoTitle = document.getElementById('videoTitle');
  el.videoFrameWrap = document.getElementById('videoFrameWrap');
  el.videoDesc = document.getElementById('videoDescWrap');
  el.videoDescText = document.getElementById('videoDescText');
}

// ===================== Render (thay cho reactivity của Vue) =====================
function render() {
  renderCameraOverlay();
  renderMatchedToast();
  renderVoiceBox();
  renderMicButton();
  renderProjectList();
  renderVideoPanel();
}

function renderCameraOverlay() {
  const showOverlay = state.phase === 'detect' && state.faceVisible;
  el.cameraOverlay.classList.toggle('opacity-100', showOverlay);
  el.cameraOverlay.classList.toggle('opacity-0', !showOverlay);
  el.cameraOverlay.classList.toggle('pointer-events-none', !showOverlay);

  const isInitOrDetect = state.phase === 'init' || state.phase === 'detect';
  el.circleWrap.classList.toggle('circle-big', isInitOrDetect);
  el.circleWrap.classList.toggle('circle-small', !isInitOrDetect);
  const circleFade = isInitOrDetect && !state.faceVisible;
  el.circleWrap.classList.toggle('opacity-0', circleFade);
  el.circleWrap.classList.toggle('opacity-100', !circleFade);

  el.camRing.classList.toggle('cam-ring', isInitOrDetect);
  el.camRing.classList.toggle('bg-emerald-400', !isInitOrDetect);

  el.liveDot.classList.toggle('hidden', isInitOrDetect);
}

function renderMatchedToast() {
  el.matchedToast.classList.toggle('hidden', !state.matchedToast);
  if (state.matchedToast) el.matchedToastName.textContent = state.matchedToast.name;
}

function renderVoiceBox() {
  const show = state.phase === 'listening' || state.phase === 'processing' || !!state.voiceMessage;
  el.voiceBox.classList.toggle('hidden', !show);
  if (!show) return;

  el.voiceListening.classList.toggle('hidden', state.phase !== 'listening');
  el.voiceProcessing.classList.toggle('hidden', state.phase !== 'processing');
  el.voiceMessageBox.classList.toggle('hidden', !(state.phase !== 'listening' && state.phase !== 'processing'));

  if (state.phase === 'listening') {
    el.voiceListenLabel.textContent = state.listenState === 'speaking' ? 'Đang nghe bạn nói...' : 'Mời bạn nói tên chủ đề...';
    el.voiceBars.innerHTML = state.levels
      .map((l) => `<div class="vu-bar flex-1 ${state.listenState === 'speaking' ? 'bg-brand-red' : 'bg-slate-300'}" style="height:${Math.round(5 + l * 26)}px"></div>`)
      .join('');
  } else if (state.phase === 'processing') {
    el.voiceProcessingLabel.textContent = state.processingLabel;
  } else {
    el.voiceMessageText.textContent = state.voiceMessage;
  }
}

function renderMicButton() {
  const show = state.phase === 'ready' && !state.voiceMessage;
  el.micButton.classList.toggle('hidden', !show);
}

function renderProjectList() {
  const q = normVi(state.searchQuery.trim());
  const filtered = state.projects.filter((p) => {
    if (state.activeField !== 'Tất cả' && p.field !== state.activeField) return false;
    if (!q) return true;
    return normVi(p.name).includes(q) || normVi(p.field).includes(q) || normVi(p.description).includes(q);
  });

  const grouped = CATEGORIES.map((cat) => ({ ...cat, items: filtered.filter((p) => p.category === cat.name) })).filter((g) => g.items.length > 0);

  const showNoProjects = state.projectsLoaded && filtered.length === 0;
  el.noProjects.classList.toggle('hidden', !showNoProjects);
  if (showNoProjects) el.noProjectsQuery.textContent = state.searchQuery;

  el.projectListWrap.innerHTML = grouped.map((group) => `
    <div class="relative flex items-center overflow-hidden rounded-2xl border border-white/40 bg-white/80 shadow-lg backdrop-blur-lg">
      <span class="absolute right-3 top-3 z-10 shrink-0 rounded-full px-2.5 py-1 text-xs font-bold shadow-sm ${group.chip}">${group.items.length} chủ đề</span>
      <div class="flex w-28 shrink-0 items-center justify-center py-3 sm:w-52">
        <div class="aspect-square w-full overflow-hidden rounded-xl">
          <img src="${group.image}" alt="${group.name}" class="h-full w-full object-cover" onerror="this.style.display='none'" />
        </div>
      </div>
      <ul class="min-w-0 flex-1 divide-y divide-slate-100 px-2 py-2 sm:px-3">
        ${group.items.map((p) => `
          <li>
            <button data-project-id="${p.id}" class="project-item flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition ${state.selected?.id === p.id ? 'bg-brand-blue/8' : 'hover:bg-slate-50'}">
              <span class="material-symbols-outlined shrink-0 text-lg ${group.accent}">${itemIcon(p.id)}</span>
              <span class="min-w-0 flex-1 text-sm font-medium text-slate-700 sm:text-[15px] ${state.selected?.id === p.id ? 'font-bold text-brand-blue' : ''}">${p.name}</span>
              ${state.selected?.id === p.id ? '<span class="material-symbols-outlined icon-filled shrink-0 text-lg text-brand-blue">play_circle</span>' : ''}
            </button>
          </li>
        `).join('')}
      </ul>
    </div>
  `).join('');

  el.projectListWrap.querySelectorAll('.project-item').forEach((btn) => {
    btn.addEventListener('click', () => {
      const p = state.projects.find((x) => x.id === btn.dataset.projectId);
      if (p) playProject(p);
    });
  });
}

let currentIframeEl = null;
function renderVideoPanel() {
  el.videoTitle.textContent = state.selected ? state.selected.name : 'Triển lãm Khoa học Công nghệ và Đổi mới Sáng tạo';

  if (state.videoSrc) {
    el.videoFrameWrap.className = 'w-full overflow-hidden bg-slate-100/70 aspect-video';
    if (!currentIframeEl || currentIframeEl.src !== state.videoSrc) {
      el.videoFrameWrap.innerHTML = `<iframe id="videoIframe" class="h-full w-full" src="${state.videoSrc}" title="Video triển lãm" frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>`;
      currentIframeEl = el.videoFrameWrap.querySelector('#videoIframe');
      currentIframeEl.addEventListener('load', onIframeLoad);
    }
  } else {
    el.videoFrameWrap.className = 'w-full overflow-hidden bg-slate-100/70 flex-1';
    el.videoFrameWrap.innerHTML = `<div class="flex h-full w-full items-center justify-center px-6 text-center text-sm text-slate-700">Chọn một chủ đề bên trái hoặc nói tên chủ đề để phát video</div>`;
    currentIframeEl = null;
  }

  el.videoDesc.classList.toggle('hidden', !state.selected);
  if (state.selected) el.videoDescText.textContent = state.selected.description;
}

// ===================== VideoPanel: điều khiển YouTube qua postMessage =====================
function sendCommand(func) {
  currentIframeEl?.contentWindow?.postMessage(JSON.stringify({ event: 'command', func, args: [] }), '*');
}
function pauseVideo() { sendCommand('pauseVideo'); }
function playVideo() { sendCommand('playVideo'); }
function onIframeLoad() {
  currentIframeEl?.contentWindow?.postMessage(JSON.stringify({ event: 'listening', id: 'videoSection' }), '*');
}
window.addEventListener('message', (e) => {
  if (!currentIframeEl || e.source !== currentIframeEl.contentWindow) return;
  let data;
  try { data = JSON.parse(e.data); } catch { return; }
  if (data.event === 'infoDelivery' && data.info && data.info.playerState === 0) onVideoEnded();
});

// ===================== Logic chính (port từ App.vue) =====================
function backToReady() {
  state.phase = 'ready';
  mouthTriggerCooldownUntil = performance.now() + MOUTH_TRIGGER_COOLDOWN_MS;
  render();
}
function startBgListen() { bgArmed = true; }
function stopBgListen() { bgArmed = false; }

function watchMouthTalking(isTalking) {
  state.mouthTalking = isTalking;
  if (!isTalking || !bgArmed) return;
  if (state.phase !== 'ready' || greetingPlaying) return;
  if (performance.now() < mouthTriggerCooldownUntil) return;
  startListening();
}

async function init() {
  cacheEls();
  render();

  try {
    state.projects = await (await fetch('/api/projects')).json();
  } catch (e) {
    state.statusText = 'Không gọi được API backend.';
  } finally {
    state.projectsLoaded = true;
    render();
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
    el.camVideo.srcObject = stream;
    await el.camVideo.play();
  } catch (e) {
    state.statusText = 'Không truy cập được camera: ' + e.message;
    render();
    return;
  }

  stopDetection = await startFaceDetection(el.camVideo, {
    onFaceChange: (hasFace) => {
      if (hasFace) lastFaceAt = performance.now();
      state.faceVisible = hasFace;
      render();

      if (state.phase !== 'detect') return;
      const now = performance.now();
      if (!hasFace) {
        firstSeen = null;
        state.statusText = 'Mời bạn đứng vào giữa khung hình...';
        return;
      }
      if (firstSeen === null) firstSeen = now;
      if (now - firstSeen >= STABLE_MS) unlock();
      else state.statusText = 'Đã thấy bạn, giữ nguyên nhé...';
    },
    onMouthActivity: watchMouthTalking,
  });

  state.phase = 'detect';
  state.statusText = 'Mời bạn đứng vào giữa khung hình...';
  render();

  setInterval(() => {
    const idle = state.phase !== 'detect' && state.phase !== 'listening' && state.phase !== 'processing';
    if (idle && performance.now() - lastFaceAt > IDLE_RESET_MS) resetToDetect();
  }, 1000);

  bindStaticEvents();
}

function resetToDetect() {
  stopBgListen();
  clearTimeout(autoRetryTimer);
  noMatchStreak = 0;
  state.selected = null;
  state.videoSrc = DEFAULT_VIDEO_URL;
  state.voiceMessage = '';
  state.matchedToast = null;
  state.searchQuery = '';
  state.activeField = 'Tất cả';
  state.faceVisible = false;
  state.phase = 'detect';
  state.statusText = 'Mời bạn đứng vào giữa khung hình...';
  render();
}

async function unlock() {
  if (state.phase !== 'detect') return;
  state.phase = 'greeting';
  render();
  greetingPlaying = true;
  playGreeting().finally(() => {
    greetingPlaying = false;
    if (state.faceVisible && state.phase === 'ready') startListening();
  });
  state.phase = 'ready';
  render();
}

function playGreeting() {
  return new Promise((resolve) => {
    let done = false;
    const finish = () => { if (!done) { done = true; resolve(); } };
    const fallback = () => {
      try {
        const u = new SpeechSynthesisUtterance(GREETING_TEXT);
        u.lang = 'vi-VN';
        u.onend = finish;
        u.onerror = finish;
        speechSynthesis.speak(u);
        setTimeout(finish, 6000);
      } catch (e) { finish(); }
    };
    const audio = new Audio('/audio/audio.mp3');
    audio.onended = finish;
    audio.onerror = fallback;
    audio.play().catch(fallback);
  });
}

async function startListening(isRetry = false) {
  if (state.phase === 'listening' || state.phase === 'processing') return;
  if (greetingPlaying) return;
  stopBgListen();
  clearTimeout(autoRetryTimer);

  let continueListening = false;
  let resumeVideo = false;

  if (!isRetry) { autoRetried = false; noMatchStreak = 0; }

  state.voiceMessage = '';
  state.matchedToast = null;
  state.listenState = 'waiting';
  state.levels = Array(24).fill(0.05);
  state.phase = 'listening';
  render();
  pauseVideo();

  listenAbort = new AbortController();
  const { signal } = listenAbort;

  let result;
  try {
    result = await recordWithVad({
      maxMs: 8000,
      silenceMs: 1300,
      startTimeoutMs: 5000,
      onLevel: (lvl) => { state.levels = [...state.levels.slice(1), Math.max(0.05, lvl)]; render(); },
      onSpeech: () => { state.listenState = 'speaking'; render(); },
      signal,
    });
  } catch (e) {
    state.voiceMessage = 'Không truy cập được micro: ' + e.message;
    backToReady();
    return;
  }

  if (result.cancelled) return;

  if (!result.spoke) {
    state.voiceMessage = 'Mình chưa nghe thấy bạn nói...';
    backToReady();
    scheduleAutoRetry();
    return;
  }

  state.phase = 'processing';
  state.processingLabel = 'Đang nhận dạng giọng nói...';
  render();

  try {
    const fd = new FormData();
    fd.append('audio', result.blob, 'speech.webm');
    const sttRes = await fetch('/api/stt', { method: 'POST', body: fd, signal });
    if (!sttRes.ok) throw new Error('Lỗi STT (' + sttRes.status + ')');
    const { text } = await sttRes.json();

    state.processingLabel = 'Đang xác nhận yêu cầu của bạn...';
    render();

    if (isPauseCommand(text)) {
      continueListening = true;
      state.voiceMessage = '';
      return;
    }

    if (isResumeCommand(text)) {
      resumeVideo = true;
      state.voiceMessage = '';
      return;
    }

    if (!text) {
      if (!autoRetried) {
        autoRetried = true;
        state.phase = 'ready';
        state.voiceMessage = '';
        render();
        startListening(true);
        return;
      }
      state.voiceMessage = 'Mình chưa nghe rõ...';
      scheduleAutoRetry();
      return;
    }

    state.processingLabel = 'Đang tìm chủ đề phù hợp...';
    render();
    const matchRes = await fetch('/api/match', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text }),
      signal,
    });
    if (!matchRes.ok) throw new Error('Lỗi match (' + matchRes.status + ')');
    const { project, provider, score } = await matchRes.json();

    const confident = project && (provider !== 'fuzzy-fallback' || (score ?? 0) >= 0.72);

    if (confident) {
      playProject(project, true);
      resumeVideo = true;
    } else {
      state.voiceMessage = 'Không tìm thấy chủ đề phù hợp, bạn thử nói lại tên chủ đề nhé.';
      scheduleAutoRetry();
    }
  } catch (e) {
    if (e.name !== 'AbortError') {
      state.voiceMessage = 'Có lỗi xử lý: ' + e.message;
      scheduleAutoRetry();
    }
  } finally {
    if (state.phase === 'processing') backToReady();
    render();
    if (continueListening) {
      startListening();
    } else if (resumeVideo) {
      playVideo();
      startBgListen();
    }
  }
}

function scheduleAutoRetry() {
  noMatchStreak++;
  render();
  if (noMatchStreak > MAX_AUTO_RETRY) {
    state.voiceMessage += ' Bạn bấm nút micro và thử lại nhé.';
    render();
    return;
  }
  autoRetryTimer = setTimeout(() => {
    if (state.phase === 'ready') startListening(true);
  }, 1500);
}

function closeVoiceStatus() {
  clearTimeout(autoRetryTimer);
  noMatchStreak = 0;
  listenAbort?.abort();
  state.voiceMessage = '';
  if (state.phase === 'listening' || state.phase === 'processing') backToReady();
  render();
  playVideo();
  startBgListen();
}

function onVideoEnded() {
  if (state.phase === 'ready') startListening();
}

function playProject(p, viaVoice = false) {
  state.selected = p;
  state.voiceMessage = '';

  if (p.video_url) {
    const sep = p.video_url.includes('?') ? '&' : '?';
    state.videoSrc = p.video_url + sep + 'autoplay=1&enablejsapi=1';
  }
  // Chưa có video minh hoạ (video_url null/rỗng): giữ nguyên videoSrc hiện tại, không đổi, không lỗi
  render();

  if (viaVoice) {
    state.matchedToast = { name: p.name };
    render();
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { state.matchedToast = null; render(); }, 4500);
  }
}

function toggleFullscreen() {
  if (document.fullscreenElement) document.exitFullscreen();
  else document.documentElement.requestFullscreen().catch(() => { });
}

function bindStaticEvents() {
  document.getElementById('fullscreenBtn')?.addEventListener('click', toggleFullscreen);
  el.micButton.addEventListener('click', () => startListening());
  document.getElementById('voiceRetryBtn')?.addEventListener('click', () => startListening());
  document.getElementById('voiceCloseBtn')?.addEventListener('click', closeVoiceStatus);
  document.getElementById('logoImg')?.addEventListener('error', (e) => { e.target.style.display = 'none'; });
}

document.addEventListener('DOMContentLoaded', init);