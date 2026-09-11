<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="icon" type="image/jpeg" href="/girc-logo.jpg">
    <link rel="apple-touch-icon" href="/girc-logo.jpg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách hệ thống triển lãm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .text-brand-blue {
            color: #1E4E8C;
        }

        .bg-brand-blue {
            background-color: #1E4E8C;
        }

        .border-brand-blue {
            border-color: #1E4E8C;
        }

        .text-brand-red {
            color: #C0392B;
        }
    </style>
</head>

<body class="bg-[#f5f7fb]">
    <div class="min-h-screen">
        @include('partials.header')

        <div class="relative">
            <section class="mx-auto py-4 text-center">
                <h2 class="relative inline-block px-6 py-2 text-xl font-extrabold uppercase text-[#0f172a]">
                    <span class="absolute inset-0 -z-10 rounded-full bg-[#e5f0f9] blur-[4px]"
                        style="height:38px;align-self:center;width:96%;justify-self:center;"></span>
                    Danh sách hệ thống triển lãm
                </h2>
            </section>

            <main class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 flex flex-col gap-5">
                @php
                    $categories = [
                        ['name' => 'Chính phủ số', 'image' => '/chinhphuso.jpg', 'accent' => 'text-blue-700'],
                        ['name' => 'Kinh tế số', 'image' => '/kinhteso.jpg', 'accent' => 'text-amber-700'],
                        ['name' => 'Xã hội số', 'image' => '/xahoiso.jpg', 'accent' => 'text-teal-700'],
                    ];
                    $sites = [
                        [
                            'id' => 'thoi-tiet',
                            'name' => 'Thông tin thời tiết và cảnh báo thiên tai',
                            'icon' => 'thunderstorm',
                            'category' => 'Chính phủ số',
                            'url' => 'https://pctt.laocai.gov.vn',
                        ],
                        [
                            'id' => 'thuy-loi',
                            'name' => 'Quản lý hạ tầng các công trình thủy lợi',
                            'icon' => 'water_drop',
                            'category' => 'Chính phủ số',
                            'url' => 'https://thuyloilc.girc.edu.vn',
                        ],
                        [
                            'id' => 'khoang-san',
                            'name' => 'Quản lý khoáng sản',
                            'icon' => 'terrain',
                            'category' => 'Chính phủ số',
                            'url' => 'https://qlks.girc.edu.vn',
                        ],
                        [
                            'id' => 'moi-truong-khong-khi',
                            'name' => 'Cảnh báo chất lượng môi trường không khí',
                            'icon' => 'airwave',
                            'category' => 'Chính phủ số',
                            'url' => 'https://aqicement.girc.edu.vn',
                        ],
                        [
                            'id' => 'dat-dai',
                            'name' => 'Quản lý và quy hoạch sử dụng đất',
                            'icon' => 'map',
                            'category' => 'Chính phủ số',
                            'url' => 'https://landuse.girc.edu.vn',
                        ],
                        [
                            'id' => 'chuyen-doi-so-xa',
                            'name' => 'Chuyển đổi số xã',
                            'icon' => 'apartment',
                            'category' => 'Chính phủ số',
                            'url' => 'https://cdsxa.girc.edu.vn',
                        ],
                        [
                            'id' => 'ocop',
                            'name' => 'Quản lý và phát triển sản phẩm OCOP',
                            'icon' => 'storefront',
                            'category' => 'Kinh tế số',
                            'url' => 'https://ocopbentre.girc.edu.vn',
                        ],
                        [
                            'id' => 'so-huu-tri-tue',
                            'name' => 'Quản lý và phát triển sở hữu trí tuệ',
                            'icon' => 'copyright',
                            'category' => 'Kinh tế số',
                            'url' => 'https://shttvinhlong.girc.edu.vn',
                        ],
                        [
                            'id' => 'nong-lam-nghiep',
                            'name' => 'Hệ thống thông tin ngành nông nghiệp',
                            'icon' => 'agriculture',
                            'category' => 'Kinh tế số',
                            'url' => 'https://csdlnnbk.girc.edu.vn',
                        ],
                        [
                            'id' => 'bac-si-ai-cay-trong',
                            'name' => 'Hệ thống Bác sĩ AI cho cây trồng',
                            'icon' => 'eco',
                            'category' => 'Kinh tế số',
                            'url' => 'https://aiplant.girc.edu.vn',
                        ],
                        [
                            'id' => 'tuoi-che-thong-minh',
                            'name' => 'Hệ thống tưới chè thông minh',
                            'icon' => 'sensors',
                            'category' => 'Kinh tế số',
                            'url' => null,
                        ],
                        [
                            'id' => 'truy-xuat-nguon-goc',
                            'name' => 'Truy xuất nguồn gốc sản phẩm',
                            'icon' => 'qr_code_scanner',
                            'category' => 'Kinh tế số',
                            'url' => 'https://truyxuat.girc.edu.vn',
                        ],
                        [
                            'id' => 'benh-ly-phoi',
                            'name' => 'Chuẩn đoán bệnh lý phổi',
                            'icon' => 'pulmonology',
                            'category' => 'Xã hội số',
                            'url' => 'http://lungai.girc.edu.vn',
                            'credentials' => [
                                ['label' => 'TK bác sĩ', 'value' => 'doctor1 / password'],
                                ['label' => 'TK bệnh nhân', 'value' => 'patient1 / password'],
                            ],
                        ],
                        [
                            'id' => 'lop-hoc-so',
                            'name' => 'Lớp học số',
                            'icon' => 'school',
                            'category' => 'Xã hội số',
                            'url' => 'https://lophocso.girc.edu.vn',
                            'credentials' => [
                                ['label' => 'TK học sinh', 'value' => 'student001@example.com / Student001@'],
                                ['label' => 'TK giáo viên', 'value' => 'hoa.tt@example.com / HoaTeacher1@'],
                            ],
                        ],
                        [
                            'id' => 'thu-vien-nong-dan',
                            'name' => 'Thư viện nông dân số',
                            'icon' => 'local_library',
                            'category' => 'Xã hội số',
                            'url' => 'https://thuviennln.girc.edu.vn',
                        ],
                    ];
                @endphp

                @foreach ($categories as $cat)
                    @php $items = collect($sites)->where('category', $cat['name'])->values(); @endphp
                    @if ($items->count())
                        <div
                            class="relative flex items-center overflow-hidden rounded-2xl border border-white/40 bg-white/80 shadow-lg backdrop-blur-lg">
                            <div class="flex w-28 shrink-0 items-center justify-center py-3 sm:w-52">
                                <div class="aspect-square w-full overflow-hidden rounded-xl">
                                    <img src="{{ $cat['image'] }}" alt="{{ $cat['name'] }}"
                                        class="h-full w-full object-cover" onerror="this.style.display='none'" />
                                </div>
                            </div>
                            <ul class="min-w-0 flex-1 divide-y divide-slate-100 px-2 py-2 sm:px-3">
                                @foreach ($items as $s)
                                    <li>
                                        @if ($s['url'])
                                            <a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer"
                                                class="flex w-full items-start gap-2.5 rounded-lg px-2.5 py-2.5 text-left transition hover:bg-slate-50">
                                                <span
                                                    class="material-symbols-outlined mt-0.5 shrink-0 text-lg {{ $cat['accent'] }}">{{ $s['icon'] }}</span>
                                                <span class="min-w-0 flex-1">
                                                    <span
                                                        class="block text-sm font-medium text-slate-700 sm:text-[15px]">{{ $s['name'] }}</span>
                                                    <span
                                                        class="mt-0.5 block truncate text-xs text-slate-400">{{ $s['url'] }}</span>
                                                    @if (!empty($s['credentials']))
                                                        <span class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                                            @foreach ($s['credentials'] as $c)
                                                                <span class="text-xs text-slate-500"><span
                                                                        class="font-medium">{{ $c['label'] }}:</span>
                                                                    {{ $c['value'] }}</span>
                                                            @endforeach
                                                        </span>
                                                    @endif
                                                </span>
                                                <span
                                                    class="material-symbols-outlined mt-0.5 shrink-0 text-base text-slate-400">open_in_new</span>
                                            </a>
                                        @else
                                            <div
                                                class="flex w-full items-start gap-2.5 rounded-lg px-2.5 py-2.5 text-left opacity-70">
                                                <span
                                                    class="material-symbols-outlined mt-0.5 shrink-0 text-lg {{ $cat['accent'] }}">{{ $s['icon'] }}</span>
                                                <span class="min-w-0 flex-1">
                                                    <span
                                                        class="block text-sm font-medium text-slate-700 sm:text-[15px]">{{ $s['name'] }}</span>
                                                    <span class="mt-0.5 block text-xs text-slate-400">Mô hình trưng
                                                        bày</span>
                                                </span>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </main>
        </div>

        @include('partials.footer')
    </div>

    <script>
        document.getElementById('fullscreenBtn')?.addEventListener('click', () => {
            if (document.fullscreenElement) document.exitFullscreen();
            else document.documentElement.requestFullscreen().catch(() => {});
        });
        document.getElementById('logoImg')?.addEventListener('error', (e) => {
            e.target.style.display = 'none';
        });
    </script>
</body>

</html>
