<!DOCTYPE html>
<html lang="vi">
<head>
  <link rel="icon" type="image/jpeg" href="/girc-logo.jpg">
  <link rel="apple-touch-icon" href="/girc-logo.jpg">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Triển lãm Ứng dụng AI & IoT</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .icon-filled { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .text-brand-blue { color: #1E4E8C; } .border-brand-blue { border-color: #1E4E8C; }
    .bg-brand-blue\/8 { background-color: rgba(30,78,140,0.08); }
    .text-brand-red { color: #C0392B; }
  </style>
</head>
<body class="bg-[#f5f7fb]">
  <div class="flex min-h-screen flex-col lg:h-screen lg:overflow-hidden">
    <header class="relative overflow-hidden border-b-2 border-brand-blue bg-white bg-cover bg-center" style="background-image: url('/bgheader.jpg')">
      <div class="absolute inset-0 bg-white/85"></div>
      <div class="relative mx-auto px-4 py-2 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-3">
            <img id="logoImg" src="/girc-logo.jpg" alt="GIRC" class="h-16 w-16" />
            <div class="leading-snug">
              <p class="text-base font-bold uppercase tracking-wide">Trường Đại học Nông Lâm</p>
              <p class="text-base font-bold uppercase tracking-wide">Trung tâm Nghiên cứu Địa Tin học</p>
              <p class="mt-1 text-[11px] text-slate-600 sm:text-xs">Địa chỉ: Phường Quyết Thắng, tỉnh Thái Nguyên</p>
              <p class="text-[11px] text-slate-600 sm:text-xs">Điện thoại: 0904 031 103 &nbsp;·&nbsp; Email: girc.tuaf@gmail.com</p>
            </div>
          </div>
          <div class="text-center">
            <h1 class="text-xl font-extrabold uppercase leading-tight text-brand-red sm:text-2xl">Triển lãm</h1>
            <p class="text-sm font-bold leading-snug text-blue-900 sm:text-xl">ỨNG DỤNG AI &amp; IoT</p>
            <p class="text-sm font-bold uppercase leading-snug text-blue-900 sm:text-xl">trong Sản xuất Nông nghiệp Thông minh</p>
          </div>
        </div>
      </div>
      <button id="fullscreenBtn" title="Toàn màn hình" class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-brand-blue">
        <span class="material-symbols-outlined text-lg">fullscreen</span>
      </button>
    </header>

    <div class="relative flex flex-1 flex-col lg:min-h-0">
      <section class="mx-auto py-2 text-center">
        <h2 class="relative inline-block px-6 py-2 text-xl font-extrabold text-[#0f172a]">
          <span class="absolute inset-0 -z-10 rounded-md bg-[#e5f0f9] blur-[4px]" style="height:38px;align-self:center;width:96%;justify-self:center;"></span>
          CÔNG NGHỆ SỐ, TIỀN ĐỀ CHO PHÁT TRIỂN NÔNG NGHIỆP BỀN VỮNG
        </h2>
      </section>

      @php
        $sites = [
          ['id'=>'iot','name'=>'Chăm cây trồng/cây chè','icon'=>'sensors','url'=>null,'video_url'=>'https://www.youtube.com/embed/uyH4HE9TKTw'],
          ['id'=>'cham-soc-vat-nuoi-ga','name'=>'Hệ thống chăm sóc vật nuôi/Gà','icon'=>'egg','url'=>null,'video_url'=>null],
          ['id'=>'bac-si-ai-cay-trong','name'=>'Bác sĩ của cây trồng','icon'=>'eco','url'=>'https://aiplant.girc.edu.vn','video_url'=>null],
          ['id'=>'qlsx-truy-xuat','name'=>'Truy xuất nguồn gốc nông sản','icon'=>'qr_code_scanner','url'=>'https://truyxuat.girc.edu.vn','video_url'=>'https://www.youtube.com/embed/4T0lVcOLJoY'],
          ['id'=>'thu-vien-so-ai','name'=>'Thư viện nông nghiệp số','icon'=>'local_library','url'=>'https://thuviennln.girc.edu.vn/','video_url'=>'https://www.youtube.com/embed/Y_Y3_TKI2ho'],
          ['id'=>'du-lich-nong-thon','name'=>'Du lịch nông thôn thông minh','icon'=>'travel_explore','url'=>null,'video_url'=>null],
          ['id'=>'tthc','name'=>'Hỗ trợ người dân nắm bắt TTHC','icon'=>'support_agent','url'=>'https://tuvantthc.girc.edu.vn/','video_url'=>null],
          ['id'=>'ocop','name'=>'Quản lý và phát triển, đánh giá, phân hạng SP OCOP','icon'=>'storefront','url'=>'https://ocopbentre.girc.edu.vn/','video_url'=>'https://www.youtube.com/embed/YPp2FUibpWE'],
          ['id'=>'bao-cao-thong-ke-nln','name'=>'Thống kê nông nghiệp cấp xã/toàn tỉnh','icon'=>'bar_chart','url'=>'https://csdlnn.girc.edu.vn/','video_url'=>'https://www.youtube.com/embed/CV-aaB4JEEg'],
        ];
        $firstWithVideo = collect($sites)->firstWhere('video_url', '!=', null);
      @endphp

      <main class="mx-auto grid w-full max-w-7xl grid-cols-1 gap-4 px-4 pb-3 sm:px-6 lg:min-h-0 lg:flex-1 lg:grid-cols-[minmax(0,460px)_1fr] lg:grid-rows-1">
        <ul id="siteListWrap" class="divide-y divide-slate-100 overflow-hidden lg:min-h-0 lg:overflow-y-auto rounded-md border border-white/40 bg-white/80 shadow-lg backdrop-blur-lg">
          @foreach ($sites as $s)
          <li>
            @if ($s['url'])
              <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer" class="flex w-full items-start gap-2.5 px-4 py-2.5 text-left transition hover:bg-slate-50">
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-lg text-blue-700">{{ $s['icon'] }}</span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-medium text-slate-700 sm:text-[15px]">{{ $s['name'] }}</span>
                  <span class="mt-0.5 block truncate text-xs text-slate-600">{{ $s['url'] }}</span>
                </span>
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-base text-slate-500">open_in_new</span>
              </a>
            @elseif ($s['video_url'])
              <button type="button" data-site-id="{{ $s['id'] }}" class="site-select-btn flex w-full items-start gap-2.5 px-4 py-2.5 text-left transition hover:bg-slate-50">
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-lg text-blue-700">{{ $s['icon'] }}</span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-medium text-slate-700 sm:text-[15px]">{{ $s['name'] }}</span>
                  <span class="mt-0.5 block text-xs text-slate-500">Mô hình trưng bày</span>
                </span>
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-lg text-slate-400">play_circle</span>
              </button>
            @else
              <div class="flex w-full cursor-default items-start gap-2.5 px-4 py-2.5 text-left opacity-70">
                <span class="material-symbols-outlined mt-0.5 shrink-0 text-lg text-slate-600">{{ $s['icon'] }}</span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-medium text-slate-700 sm:text-[15px]">{{ $s['name'] }}</span>
                  <span class="mt-0.5 block text-xs text-slate-500">Mô hình trưng bày</span>
                </span>
              </div>
            @endif
          </li>
          @endforeach
        </ul>

        <div class="flex flex-col gap-3 lg:min-h-0">
          <div class="overflow-hidden rounded-md border border-white/40 bg-black shadow-lg lg:min-h-0 lg:flex-1">
            <div class="aspect-video w-full lg:aspect-auto lg:h-full">
              <iframe id="mainVideoFrame" src="{{ $firstWithVideo['video_url'] ?? '' }}" title="Video minh hoạ hệ thống" class="h-full w-full" frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
          </div>
          <div class="grid grid-cols-6 gap-2" id="thumbGrid">
            @foreach (collect($sites)->where('video_url', '!=', null) as $s)
            @php
              $vid = null;
              if (preg_match('#/embed/([^?]+)#', $s['video_url'], $m)) $vid = $m[1];
            @endphp
            <button type="button" data-site-id="{{ $s['id'] }}" class="thumb-btn group relative aspect-video w-full overflow-hidden rounded-md border-2 border-white/40 shadow transition hover:border-blue-300">
              <img src="{{ $vid ? "https://img.youtube.com/vi/{$vid}/mqdefault.jpg" : '' }}" alt="{{ $s['name'] }}" class="h-full w-full object-cover" />
              <span class="absolute inset-0 flex items-center justify-center bg-black/10 transition group-hover:bg-black/25">
                <span class="material-symbols-outlined text-2xl text-white drop-shadow">play_circle</span>
              </span>
              <span class="absolute inset-x-0 bottom-0 line-clamp-1 bg-gradient-to-t from-black/75 to-transparent px-1 pb-0.5 pt-2 text-left text-[10px] leading-tight text-white">{{ $s['name'] }}</span>
            </button>
            @endforeach
          </div>
        </div>
      </main>
    </div>

    <footer class="relative flex h-[1cm] shrink-0 items-center justify-between overflow-hidden bg-[#5081BC] bg-cover bg-center px-4 text-white sm:px-6" style="background-image: url('/footer.png')">
      <div class="absolute inset-0 bg-black/30"></div>
      @php
        try {
          $cache = \Illuminate\Support\Facades\Cache::store('file');
          if (!$cache->has('hoithao_visits')) $cache->forever('hoithao_visits', 0);
          $visits = $cache->increment('hoithao_visits');
          $now = time();
          $online = $cache->get('hoithao_online', []);
          $online[session()->getId()] = $now;
          $online = array_filter($online, fn ($t) => $now - $t < 300);
          $cache->put('hoithao_online', $online, 600);
          $onlineCount = count($online);
        } catch (\Throwable $e) { $visits = '–'; $onlineCount = '–'; }
      @endphp
      <p class="relative text-xs font-semibold" style="text-shadow: 0 1px 3px rgba(0,0,0,0.6);">Designed by GIRC</p>
      <p class="relative flex items-center gap-4 text-xs font-semibold" style="text-shadow: 0 1px 3px rgba(0,0,0,0.6);">
        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">wifi</span>Online: {{ $onlineCount }}</span>
        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-base">visibility</span>Truy cập: {{ is_numeric($visits) ? number_format($visits) : $visits }}</span>
      </p>
    </footer>
  </div>

  <script>
    const sites = @json($sites);

    function selectSite(id) {
      const s = sites.find((x) => x.id === id);
      if (!s || !s.video_url) return;
      document.getElementById('mainVideoFrame').src = s.video_url;
    }

    document.querySelectorAll('.site-select-btn, .thumb-btn').forEach((btn) => {
      btn.addEventListener('click', () => selectSite(btn.dataset.siteId));
    });

    document.getElementById('fullscreenBtn')?.addEventListener('click', () => {
      if (document.fullscreenElement) document.exitFullscreen();
      else document.documentElement.requestFullscreen().catch(() => {});
    });
    document.getElementById('logoImg')?.addEventListener('error', (e) => { e.target.style.display = 'none'; });
  </script>
</body>
</html>
