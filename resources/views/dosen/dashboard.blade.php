@extends('layouts.app')

@section('title', 'Dashboard Dosen')

@section('content')
@php
    $selectedMeetings = $selectedJadwal?->pertemuan?->sortBy('pertemuan_ke') ?? collect();
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-violet-700">Dashboard Dosen</p>
            <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Halo, {{ auth('dosen')->user()->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">Pilih mata kuliah, kelas, lalu pertemuan untuk membuka daftar presensi.</p>
        </div>
        <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-500">
            <i class="fa-regular fa-calendar text-violet-700"></i>{{ now()->translatedFormat('l, d F Y') }}
        </span>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Ringkasan dosen">
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <p class="text-sm text-slate-500">Kelas diampu</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['kelas'] }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <p class="text-sm text-slate-500">Total pertemuan</p>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['pertemuan'] }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <p class="text-sm text-slate-500">Catatan hadir</p>
            <p class="mt-3 text-3xl font-bold text-emerald-700">{{ $stats['hadir'] }}</p>
        </div>
    </section>

    @if($mataKuliahList->isEmpty())
        <section class="rounded-md border border-slate-200 bg-white px-5 py-12 text-center">
            <i class="fa-solid fa-chalkboard-user text-3xl text-slate-300"></i>
            <h2 class="mt-3 font-bold text-slate-800">Belum ada jadwal kuliah</h2>
            <p class="mt-1 text-sm text-slate-500">Jadwal kelas yang ditugaskan kepada Anda akan tampil di sini.</p>
        </section>
    @else
        <section id="kelas" class="space-y-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Mata Kuliah</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih mata kuliah untuk melihat kelas yang mengambilnya.</p>
            </div>
            <nav class="flex gap-2 overflow-x-auto pb-2" aria-label="Pilih mata kuliah">
                @foreach($mataKuliahList as $mataKuliah)
                    <a
                        href="{{ route('dosen.dashboard', ['mata_kuliah' => $mataKuliah->id]) }}"
                        @class([
                            'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition',
                            'border-violet-700 bg-violet-700 text-white' => $selectedMataKuliah?->id === $mataKuliah->id,
                            'border-slate-200 bg-white text-slate-700 hover:border-violet-300 hover:text-violet-800' => $selectedMataKuliah?->id !== $mataKuliah->id,
                        ])
                        @if($selectedMataKuliah?->id === $mataKuliah->id) aria-current="page" @endif
                    >
                        <span class="font-mono text-xs">{{ $mataKuliah->kode_mk }}</span>
                        <span>{{ $mataKuliah->nama_mk }}</span>
                    </a>
                @endforeach
            </nav>

            <section class="rounded-md border border-slate-200 bg-white p-4 sm:p-5" aria-label="Pilih kelas">
                <div class="mb-4">
                    <h3 class="font-bold text-slate-900">Kelas untuk {{ $selectedMataKuliah->nama_mk }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Pilih kelas untuk menampilkan pertemuannya.</p>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-2">
                    @forelse($kelasList as $jadwal)
                        <a
                            href="{{ route('dosen.dashboard', ['mata_kuliah' => $selectedMataKuliah->id, 'jadwal' => $jadwal->id]) }}#pertemuan"
                            @class([
                                'inline-flex min-h-11 shrink-0 items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition',
                                'border-blue-700 bg-blue-700 text-white' => $selectedJadwal?->id === $jadwal->id,
                                'border-slate-200 bg-slate-50 text-slate-700 hover:border-blue-300 hover:text-blue-800' => $selectedJadwal?->id !== $jadwal->id,
                            ])
                            @if($selectedJadwal?->id === $jadwal->id) aria-current="true" @endif
                        >
                            <i class="fa-solid fa-users text-xs"></i>{{ $jadwal->kelas ?: 'Kelas belum ditentukan' }}
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada kelas yang ditugaskan untuk mata kuliah ini.</p>
                    @endforelse
                </div>
            </section>

            @if($selectedJadwal)
                <section id="pertemuan" class="space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Pertemuan · {{ $selectedJadwal->kelas }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedMataKuliah->nama_mk }} · {{ $selectedJadwal->hari }}, {{ substr($selectedJadwal->jam_mulai, 0, 5) }}–{{ substr($selectedJadwal->jam_selesai, 0, 5) }}</p>
                        </div>
                        <a href="{{ route('dosen.jadwal.export_rekap', $selectedJadwal) }}" class="inline-flex min-h-10 items-center gap-2 self-start rounded-md border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto">
                            <i class="fa-solid fa-file-arrow-down text-emerald-700"></i> Ekspor CSV
                        </a>
                    </div>

                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @forelse($selectedMeetings as $pertemuan)
                            <article class="w-64 shrink-0 rounded-md border border-slate-200 bg-white p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-violet-50 text-sm font-bold text-violet-800">P{{ $pertemuan->pertemuan_ke }}</span>
                                    <span @class([
                                        'rounded-full px-2 py-1 text-[11px] font-semibold',
                                        'bg-emerald-50 text-emerald-800' => $pertemuan->status_pertemuan === 'Selesai',
                                        'bg-blue-50 text-blue-800' => $pertemuan->status_pertemuan === 'Berlangsung',
                                        'bg-slate-100 text-slate-600' => $pertemuan->status_pertemuan === 'Terjadwal',
                                        'bg-rose-50 text-rose-800' => $pertemuan->status_pertemuan === 'Dibatalkan',
                                    ])>{{ $pertemuan->status_pertemuan }}</span>
                                </div>
                                <h4 class="mt-3 min-h-10 font-semibold text-slate-900">{{ $pertemuan->topik }}</h4>
                                <p class="mt-2 text-xs font-semibold text-slate-600">
                                    Dosen: {{ $pertemuan->dosenPengganti->name ?? $selectedJadwal->dosen->name }}
                                    @if($pertemuan->dosenPengganti)
                                        <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] text-amber-800">Dosen Pengganti</span>
                                    @endif
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    <i class="fa-regular fa-calendar mr-1"></i>{{ $pertemuan->tanggal_pertemuan?->translatedFormat('d F Y') ?? 'Tanggal belum diatur' }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    Hadir {{ $pertemuan->presensi->where('status', 'Hadir')->count() }}
                                    <span class="px-1">·</span>
                                    Tidak Hadir {{ $pertemuan->presensi->where('status', 'Tidak Hadir')->count() }}
                                </p>
                                @if($pertemuan->status_pertemuan === 'Terjadwal')
                                    <form method="POST" action="{{ route('dosen.pertemuan.start', $pertemuan) }}" class="mt-4">
                                        @csrf
                                        <button type="submit" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-md bg-blue-700 px-3 py-2 text-xs font-bold text-white hover:bg-blue-800">
                                            <i class="fa-solid fa-play"></i> Mulai Pertemuan
                                        </button>
                                    </form>
                                @elseif($pertemuan->status_pertemuan === 'Berlangsung')
                                    <a href="{{ route('dosen.pertemuan.qr', $pertemuan) }}" class="mt-4 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-md bg-violet-700 px-3 py-2 text-xs font-bold text-white hover:bg-violet-800">
                                        <i class="fa-solid fa-qrcode"></i> Daftar Mahasiswa & QR
                                    </a>
                                @else
                                    <a href="{{ route('dosen.pertemuan.qr', $pertemuan) }}" class="mt-4 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                        <i class="fa-solid fa-eye"></i> Lihat Status Presensi
                                    </a>
                                @endif
                            </article>
                        @empty
                            <div class="w-full rounded-md border border-dashed border-slate-300 bg-white px-5 py-8 text-center text-sm text-slate-500">
                                Belum ada pertemuan untuk kelas ini.
                            </div>
                        @endforelse
                    </div>

                    @if($selectedJadwal->dosen_id === auth()->id())
                    <details class="rounded-md border border-slate-200 bg-white p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-800">Tambah pertemuan</summary>
                        <form method="POST" action="{{ route('dosen.pertemuan.store', $selectedJadwal) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                            @csrf
                            <input type="hidden" name="pertemuan_ke" value="{{ ($selectedJadwal->pertemuan->max('pertemuan_ke') ?? 0) + 1 }}">
                            <div>
                                <label for="topik-pertemuan" class="mb-1 block text-xs font-medium text-slate-700">Topik</label>
                                <input id="topik-pertemuan" name="topik" maxlength="255" placeholder="Contoh: Materi pertemuan" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-violet-600 focus:outline-none focus:ring-2 focus:ring-violet-100">
                            </div>
                            <div>
                                <label for="tanggal-pertemuan" class="mb-1 block text-xs font-medium text-slate-700">Tanggal</label>
                                <input id="tanggal-pertemuan" name="tanggal_pertemuan" type="date" value="{{ today()->toDateString() }}" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm focus:border-violet-600 focus:outline-none focus:ring-2 focus:ring-violet-100">
                            </div>
                            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 self-end rounded-md bg-violet-700 px-4 text-sm font-semibold text-white hover:bg-violet-800">
                                <i class="fa-solid fa-plus"></i> Buat Pertemuan {{ ($selectedJadwal->pertemuan->max('pertemuan_ke') ?? 0) + 1 }}
                            </button>
                        </form>
                        @error('pertemuan_ke')<p class="mt-2 text-xs text-rose-700">{{ $message }}</p>@enderror
                        @error('topik')<p class="mt-2 text-xs text-rose-700">{{ $message }}</p>@enderror
                        @error('tanggal_pertemuan')<p class="mt-2 text-xs text-rose-700">{{ $message }}</p>@enderror
                    </details>
                    @endif
                </section>
            @else
                <p class="rounded-md border border-dashed border-slate-300 bg-white px-5 py-8 text-center text-sm text-slate-500">
                    Pilih kelas untuk melihat daftar pertemuan.
                </p>
            @endif
        </section>
    @endif
</div>
@endsection
