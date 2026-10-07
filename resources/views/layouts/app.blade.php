<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduAttend') - EduAttend</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="flex min-h-screen flex-col bg-[#f2f6fc] text-slate-800">
    @php
        $authenticatedUser = auth('admin')->user() ?? auth('dosen')->user() ?? auth('mahasiswa')->user();
        $role = $authenticatedUser?->role;
        $roleStyle = match ($role) {
            'admin' => ['badge' => 'bg-teal-100 text-teal-800', 'icon' => 'fa-shield-halved'],
            'dosen' => ['badge' => 'bg-violet-100 text-violet-800', 'icon' => 'fa-chalkboard-user'],
            'mahasiswa' => ['badge' => 'bg-blue-100 text-blue-800', 'icon' => 'fa-graduation-cap'],
            default => ['badge' => 'bg-slate-100 text-slate-700', 'icon' => 'fa-qrcode'],
        };
        $homeRoute = match ($role) {
            'admin' => 'admin.dashboard',
            'dosen' => 'dosen.dashboard',
            'mahasiswa' => 'mahasiswa.dashboard',
            default => 'login',
        };

        $sidebarLinks = match ($role) {
            'admin' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'icon' => 'fa-chart-pie', 'active' => request()->routeIs('admin.dashboard')],
                ['label' => 'Data Mahasiswa', 'url' => route('admin.users.index', ['role' => 'mahasiswa']), 'icon' => 'fa-user-graduate', 'active' => request()->routeIs('admin.users.*') && (request('role') === 'mahasiswa' || request()->route('user')?->role === 'mahasiswa')],
                ['label' => 'Data Dosen', 'url' => route('admin.users.index', ['role' => 'dosen']), 'icon' => 'fa-chalkboard-user', 'active' => request()->routeIs('admin.users.*') && (request('role') === 'dosen' || request()->route('user')?->role === 'dosen')],
                ['label' => 'Mata Kuliah', 'url' => route('admin.courses'), 'icon' => 'fa-book-open', 'active' => request()->routeIs('admin.courses')],
                ['label' => 'Rekap Presensi', 'url' => route('admin.attendance'), 'icon' => 'fa-chart-column', 'active' => request()->routeIs('admin.attendance')],
                ['label' => 'Profil', 'url' => route('admin.profile'), 'icon' => 'fa-circle-user', 'active' => request()->routeIs('admin.profile')],
            ],
            'dosen' => [
                ['label' => 'Dashboard', 'url' => route('dosen.dashboard'), 'icon' => 'fa-house', 'active' => request()->routeIs('dosen.dashboard')],
                ['label' => 'Kelas & Sesi QR', 'url' => route('dosen.dashboard').'#kelas', 'icon' => 'fa-qrcode', 'active' => request()->routeIs('dosen.pertemuan.*')],
                ['label' => 'Rekap Presensi', 'url' => route('dosen.attendance'), 'icon' => 'fa-file-lines', 'active' => request()->routeIs('dosen.attendance')],
                ['label' => 'Profil', 'url' => route('dosen.profile'), 'icon' => 'fa-circle-user', 'active' => request()->routeIs('dosen.profile')],
            ],
            'mahasiswa' => [
                ['label' => 'Dashboard', 'url' => route('mahasiswa.dashboard'), 'icon' => 'fa-house', 'active' => request()->routeIs('mahasiswa.dashboard')],
                ['label' => 'Scan Presensi', 'url' => route('mahasiswa.scan'), 'icon' => 'fa-camera', 'active' => request()->routeIs('mahasiswa.scan')],
                ['label' => 'Riwayat Presensi', 'url' => route('mahasiswa.riwayat'), 'icon' => 'fa-clock-rotate-left', 'active' => request()->routeIs('mahasiswa.riwayat')],
                ['label' => 'Profil', 'url' => route('mahasiswa.profile'), 'icon' => 'fa-circle-user', 'active' => request()->routeIs('mahasiswa.profile')],
            ],
            default => [],
        };
    @endphp

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-screen-2xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route($homeRoute) }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-[#17385f] text-white">
                    <i class="fa-solid fa-graduation-cap"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-base font-extrabold text-[#17385f]">EduAttend</span>
                    <span class="hidden text-[11px] text-slate-500 sm:block">Sistem Presensi Perkuliahan</span>
                </span>
            </a>

            @if($authenticatedUser)
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <p class="max-w-48 truncate text-sm font-semibold text-slate-800">{{ $authenticatedUser->name }}</p>
                        <span class="text-[11px] font-semibold {{ $roleStyle['badge'] }} rounded px-1.5 py-0.5">{{ ucfirst($role) }}</span>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-slate-600" aria-hidden="true">
                        @if($authenticatedUser->profile_photo_path)
                            <img src="{{ asset('storage/' . $authenticatedUser->profile_photo_path) }}" alt="" class="h-full w-full object-cover">
                        @else
                            <i class="fa-solid {{ $roleStyle['icon'] }}"></i>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700" title="Keluar" aria-label="Keluar">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Daftar</a>
                </div>
            @endif
        </div>

        @if($authenticatedUser)
            <div class="border-t border-slate-100 px-4 py-2 lg:hidden">
                <details>
                    <summary class="flex cursor-pointer list-none items-center justify-between py-1 text-sm font-semibold text-slate-700">
                        <span><i class="fa-solid fa-bars mr-2 text-slate-500"></i>Menu {{ ucfirst($role) }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                    </summary>
                    <nav class="grid gap-1 pb-2 pt-2 sm:grid-cols-2" aria-label="Navigasi utama">
                        @foreach($sidebarLinks as $link)
                            <a href="{{ $link['url'] }}" @class([
                                'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium',
                                'bg-blue-600 text-white' => $link['active'],
                                'text-slate-600 hover:bg-slate-100' => ! $link['active'],
                            ])>
                                <i class="fa-solid {{ $link['icon'] }} w-4 text-center"></i>{{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </details>
            </div>
        @endif
    </header>

    <div @class([
        'mx-auto flex w-full max-w-screen-2xl flex-1 gap-6 px-4 py-5 sm:px-6',
        'lg:items-start' => $authenticatedUser,
        'items-center' => ! $authenticatedUser,
    ])>
        @if($authenticatedUser)
            <aside class="sticky top-24 hidden w-60 shrink-0 flex-col overflow-hidden rounded-md bg-[#172f4d] text-white shadow-sm lg:flex" aria-label="Navigasi {{ ucfirst($role) }}">
                <div class="border-b border-white/10 px-4 py-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-md bg-white/10 text-lg text-white">
                            @if($authenticatedUser->profile_photo_path)
                                <img src="{{ asset('storage/' . $authenticatedUser->profile_photo_path) }}" alt="" class="h-full w-full object-cover">
                            @else
                                <i class="fa-solid {{ $roleStyle['icon'] }}"></i>
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-bold">{{ ucfirst($role) }}</span>
                            <span class="block max-w-36 truncate text-xs text-slate-300">{{ $authenticatedUser->name }}</span>
                        </span>
                    </div>
                </div>
                <nav class="flex flex-col gap-1 p-3" aria-label="Navigasi utama">
                    @foreach($sidebarLinks as $link)
                        <a href="{{ $link['url'] }}" @class([
                            'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition',
                            'bg-blue-600 text-white shadow-sm' => $link['active'],
                            'text-slate-300 hover:bg-white/10 hover:text-white' => ! $link['active'],
                        ])>
                            <i class="fa-solid {{ $link['icon'] }} w-4 text-center"></i>{{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
                <div class="mt-auto border-t border-white/10 p-3 text-[11px] text-slate-400">EduAttend · {{ date('Y') }}</div>
            </aside>
        @endif

        <main class="min-w-0 flex-1 {{ $authenticatedUser ? '' : 'w-full' }}">
            @if(session('success'))
                <div class="mb-4 flex items-center gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 flex items-center gap-3 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i><span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <footer class="border-t border-slate-200 bg-white px-4 py-3 text-center text-[11px] text-slate-500">
        EduAttend · Presensi QR & validasi geolokasi
    </footer>

    @if($authenticatedUser)
        <script>
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    window.location.reload();
                }
            });
        </script>
    @endif

    @stack('scripts')
</body>
</html>
