<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Triển lãm Khoa học Công nghệ và Đổi mới Sáng tạo</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  <style>
    html, body { overflow-x: hidden; }
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .icon-filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .text-brand-blue { color: #1E4E8C; }
    .text-brand-bluedark { background-color: #163a6b; }
    .bg-brand-blue\/8 { background-color: rgba(30,78,140,0.08); }
    .border-brand-blue { border-color: #1E4E8C; }
    .bg-brand-blue { background-color: #1E4E8C; }
    .hover\:bg-brand-bluedark:hover { background-color: #163a6b; }
    .text-brand-red { color: #C0392B; }
    .bg-brand-red { background-color: #C0392B; }

    .fade-up { animation: fadeUp .5s ease both; }
    @keyframes fadeUp { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform:none;} }

    .cam-ring {
      background: conic-gradient(from 0deg, #1E4E8C, #60a5fa, #1E4E8C);
      animation: spin 3s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .mic-pulse { animation: micPulse 2.2s ease-out infinite; opacity: 0; }
    @keyframes micPulse {
      0% { transform: scale(0.6); opacity: 0.5; }
      100% { transform: scale(1.8); opacity: 0; }
    }

    .vu-bar { border-radius: 3px; transition: height .08s linear; min-height: 5px; }

    #circleWrap { position: fixed; z-index: 50; transition: all .7s ease-in-out; }
    #circleWrap.circle-big {
      left: 50%; top: 50%; transform: translate(-50%, -50%);
      width: 10rem; height: 10rem;
    }
    @media (min-width: 640px) { #circleWrap.circle-big { width: 14rem; height: 14rem; } }
    @media (min-width: 768px) { #circleWrap.circle-big { width: 18rem; height: 18rem; } }
    #circleWrap.circle-small {
      top: 0.5rem; right: 50%; transform: translate(0,0);
      width: 6rem; height: 6rem;
    }
  </style>
</head>
<body class="bg-[#f5f7fb]">

  <!-- ============ Camera overlay (giữa màn hình lúc detect) ============ -->
  <div id="cameraOverlay" class="fixed inset-0 z-40 bg-[#f5f7fb] flex items-center justify-center overflow-hidden transition-opacity duration-500 ease-in-out opacity-0 pointer-events-none">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[520px] bg-gradient-to-b from-brand-blue/10 via-transparent to-transparent"></div>

    <div id="titleWrap" class="absolute top-6 sm:top-8 md:top-14 flex flex-col items-center text-center px-4 sm:px-6 fade-up">
      <div class="mb-4 h-16 w-16 sm:mb-5 sm:h-20 sm:w-20 md:h-24 md:w-24 overflow-hidden rounded-full shadow-lg ring-4 ring-white">
        <img id="logoImg2" src="/girc-logo.jpg" alt="GIRC" class="h-full w-full object-cover">
      </div>
      <p class="text-[11px] font-semibold uppercase tracking-[0.15em] text-brand-blue sm:text-xs sm:tracking-[0.25em] md:text-sm">
        Trung tâm Nghiên cứu Địa Tin học · GIRC
      </p>
      <h1 class="mt-3 max-w-3xl text-xl font-bold tracking-tight text-slate-900 leading-tight sm:text-2xl md:text-4xl">
        Triển lãm Khoa học Công nghệ<br class="hidden md:block">
        và <span class="text-brand-red">Đổi mới Sáng tạo</span>
      </h1>
    </div>
  </div>

  <!-- Ô tròn camera -->
  <div id="circleWrap" class="circle-big opacity-0">
    <div id="camRing" class="absolute -inset-1 rounded-full transition-opacity duration-700 cam-ring"></div>
    <div class="relative w-full h-full rounded-full overflow-hidden bg-slate-900 shadow-xl ring-4 ring-white">
      <video id="camVideo" autoplay muted playsinline class="w-full h-full object-cover" style="transform: scaleX(-1)"></video>
    </div>
    <span id="liveDot" class="hidden absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-white animate-pulse"></span>
  </div>

  <!-- Matched toast -->
  <div id="matchedToast" class="hidden fixed top-16 left-1/2 -translate-x-1/2 z-[60] fade-up max-w-[92%]">
    <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 shadow-lg">
      <span class="material-symbols-outlined icon-filled text-xl text-emerald-500 shrink-0">check_circle</span>
      <p class="truncate text-sm font-medium text-slate-800">Đang phát: <span id="matchedToastName"></span></p>
    </div>
  </div>

  <!-- Voice status box -->
  <div id="voiceBox" class="hidden fixed bottom-5 left-5 right-5 z-50 fade-up sm:left-auto sm:right-5 sm:w-96">
    <div class="relative rounded-2xl border border-slate-200 bg-white/95 backdrop-blur px-4 py-3.5 pr-9 shadow-xl sm:px-5 sm:py-4 sm:pr-10">
      <button id="voiceCloseBtn" title="Đóng" class="absolute right-2.5 top-2.5 flex h-6 w-6 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
        <span class="material-symbols-outlined text-base">close</span>
      </button>

      <div id="voiceListening" class="hidden flex items-center gap-4">
        <span class="relative flex h-4 w-4 shrink-0">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-red opacity-60"></span>
          <span class="relative inline-flex rounded-full h-4 w-4 bg-brand-red"></span>
        </span>
        <div class="min-w-0 flex-1">
          <p id="voiceListenLabel" class="text-sm md:text-base font-semibold text-slate-900"></p>
          <div id="voiceBars" class="mt-2 flex h-8 items-end gap-[3px]"></div>
        </div>
      </div>

      <div id="voiceProcessing" class="hidden flex items-center gap-3">
        <span class="h-5 w-5 shrink-0 rounded-full border-[3px] border-brand-blue border-t-transparent animate-spin"></span>
        <div class="min-w-0">
          <p id="voiceProcessingLabel" class="text-sm md:text-base font-semibold text-slate-900"></p>
        </div>
      </div>

      <div id="voiceMessageBox" class="hidden flex flex-wrap items-center justify-between gap-2.5">
        <div class="min-w-0">
          <p id="voiceMessageText" class="text-sm text-slate-800 sm:text-base"></p>
        </div>
        <button id="voiceRetryBtn" class="ml-auto flex shrink-0 items-center gap-1.5 rounded-full bg-brand-blue px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-brand-bluedark">
          <span class="material-symbols-outlined text-lg">mic</span> Nói lại
        </button>
      </div>
    </div>
  </div>

  <!-- Mic button -->
  <button id="micButton" title="Nói tên chủ đề" class="hidden fixed bottom-6 right-6 z-50 flex h-20 w-20 items-center justify-center">
    <span class="absolute inset-0 rounded-full" style="background-color:#1E9EB3;opacity:.12"></span>
    <span class="absolute inset-[6px] rounded-full" style="background-color:#1E9EB3;opacity:.22"></span>
    <span class="absolute inset-3 rounded-full mic-pulse" style="background-color:#1E9EB3"></span>
    <span class="absolute inset-3 rounded-full mic-pulse" style="background-color:#1E9EB3;animation-delay:1.1s"></span>
    <span class="relative flex h-14 w-14 items-center justify-center rounded-full shadow-lg transition-transform duration-200 hover:scale-105 active:scale-95" style="background-color:#1E9EB3">
      <span class="material-symbols-outlined icon-filled text-3xl text-white">mic</span>
    </span>
  </button>

  <div class="min-h-screen">
    @include('partials.header')

    <div class="relative">
      <section class="mx-auto py-4 text-center">
        <h2 class="relative inline-block px-6 py-2 text-xl font-extrabold uppercase text-[#0f172a]">
          <span class="absolute inset-0 -z-10 rounded-full bg-[#e5f0f9] blur-[4px]" style="height:38px;align-self:center;width:96%;justify-self:center;"></span>
          Một số ứng dụng chuyển đổi số và trí tuệ nhân tạo (AI)
        </h2>
      </section>

      <main class="mx-auto px-4 pb-16 sm:px-6 flex flex-col gap-5 xl:grid xl:grid-cols-12 xl:gap-6">
        <section class="order-2 flex flex-col gap-3 xl:order-1 xl:col-span-6">
          <div id="projectListWrap" class="flex flex-col gap-3"></div>
          <div id="noProjects" class="hidden rounded-xl border border-white/40 bg-white/45 py-10 text-center text-sm text-slate-700 shadow-lg backdrop-blur-lg">
            Không tìm thấy chủ đề nào khớp "<span id="noProjectsQuery"></span>"
          </div>
        </section>

        <section id="videoSection" class="sticky top-24 order-1 flex scroll-mt-24 xl:fixed xl:right-6 xl:top-[55%] xl:order-2 xl:w-[45%] xl:-translate-y-1/2">
          <div class="flex w-full self-start flex-col overflow-hidden rounded-xl border border-white/40 bg-white/45 shadow-lg backdrop-blur-lg">
            <div class="border-b border-white/40 bg-white/25 px-4 py-2.5 text-center">
              <h2 id="videoTitle" class="text-sm font-bold text-slate-800 sm:text-base"></h2>
            </div>
            <div id="videoFrameWrap" class="w-full overflow-hidden bg-slate-100/70 flex-1"></div>
            <div id="videoDescWrap" class="hidden border-t border-white/40 bg-white/25 px-4 py-2.5 text-xs leading-relaxed text-slate-800 sm:text-sm">
              <span id="videoDescText"></span>
            </div>
          </div>
        </section>
      </main>
    </div>

    @include('partials.footer')
  </div>

  <script type="module" src="/js/kiosk.js"></script>
</body>
</html>
