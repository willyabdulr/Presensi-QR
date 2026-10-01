@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-teal-700">Ringkasan Sistem</p>
            <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Dashboard Admin</h1>
            <p class="mt-1 text-sm text-slate-500">Ikhtisar akun, mata kuliah, sesi, dan presensi.</p>
        </div>
        @if($stats['pending'] > 0)
            <a href="{{ route('admin.users.index', ['status' => 'pending']) }}" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                <i class="fa-solid fa-clock"></i>{{ $stats['pending'] }} akun menunggu
            </a>
        @endif
    </div>

    <section class="grid grid-cols-2 gap-3 xl:grid-cols-3" aria-label="Statistik sistem">
        @foreach([
            ['label' => 'Mahasiswa', 'value' => $stats['mahasiswa'], 'icon' => 'fa-user-graduate', 'tone' => 'bg-blue-50 text-blue-700'],
            ['label' => 'Dosen', 'value' => $stats['dosen'], 'icon' => 'fa-chalkboard-user', 'tone' => 'bg-violet-50 text-violet-700'],
            ['label' => 'Mata Kuliah', 'value' => $stats['mata_kuliah'], 'icon' => 'fa-book-open', 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Sesi Pertemuan', 'value' => $stats['pertemuan'], 'icon' => 'fa-calendar-days', 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Total Kehadiran', 'value' => $stats['presensi'], 'icon' => 'fa-clipboard-check', 'tone' => 'bg-cyan-50 text-cyan-800'],
            ['label' => 'Hadir Hari Ini', 'value' => $stats['presensi_hari_ini'], 'icon' => 'fa-calendar-check', 'tone' => 'bg-teal-50 text-teal-800'],
        ] as $stat)
            <div class="rounded-md border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-slate-500 sm:text-sm">{{ $stat['label'] }}</span>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md {{ $stat['tone'] }}"><i class="fa-solid {{ $stat['icon'] }}"></i></span>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-900">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid grid-cols-1 gap-5 2xl:grid-cols-2">
        <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5">
                <div>
                    <h2 class="font-bold text-slate-900">Pendaftaran Menunggu</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Akun mahasiswa dan dosen yang perlu ditinjau.</p>
                </div>
                <a href="{{ route('admin.users.index', ['status' => 'pending']) }}" class="shrink-0 text-sm font-semibold text-teal-700 hover:text-teal-900">Semua</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($pendingUsers as $pendingUser)
                    <a href="{{ route('admin.users.show', $pendingUser) }}" class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-teal-50/50 sm:px-5">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ $pendingUser->name }}</span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ strtoupper($pendingUser->role) }} · {{ $pendingUser->nomor_induk }}</span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block text-xs text-slate-500">{{ $pendingUser->created_at->format('d M Y') }}</span>
                            <span class="mt-1 block text-xs font-semibold text-teal-700">Tinjau <i class="fa-solid fa-arrow-right ml-1"></i></span>
                        </span>
                    </a>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada pendaftaran yang menunggu.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5">
                <div>
                    <h2 class="font-bold text-slate-900">Sesi Terbaru</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Jumlah presensi hadir di tiap pertemuan.</p>
                </div>
                <a href="{{ route('admin.attendance') }}" class="shrink-0 text-sm font-semibold text-teal-700 hover:text-teal-900">Rekap</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentMeetings as $meeting)
                    <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ $meeting->jadwalKuliah->mataKuliah->nama_mk ?? 'Mata kuliah' }} · P{{ $meeting->pertemuan_ke }}</span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $meeting->jadwalKuliah->dosen->name ?? 'Dosen belum tersedia' }} · {{ $meeting->created_at->format('d M Y') }}</span>
                        </span>
                        <span class="shrink-0 rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800">{{ $meeting->total_hadir }} hadir</span>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada sesi pertemuan.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
