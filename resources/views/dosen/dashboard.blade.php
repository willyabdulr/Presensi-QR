@extends('layouts.app')

@section('title', 'Dashboard Dosen')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-violet-700">Dashboard Dosen</p>
            <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Halo, {{ auth('dosen')->user()->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola sesi QR dan pantau kehadiran dari kelas yang Anda ampu.</p>
        </div>
        <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-500">
            <i class="fa-regular fa-calendar text-violet-700"></i>{{ now()->translatedFormat('l, d F Y') }}
        </span>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Ringkasan dosen">
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Kelas diampu</span><span class="flex h-9 w-9 items-center justify-center rounded-md bg-violet-50 text-violet-700"><i class="fa-solid fa-book-open"></i></span></div>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['kelas'] }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Sesi pertemuan</span><span class="flex h-9 w-9 items-center justify-center rounded-md bg-blue-50 text-blue-700"><i class="fa-solid fa-qrcode"></i></span></div>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['pertemuan'] }}</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between"><span class="text-sm text-slate-500">Total mahasiswa hadir</span><span class="flex h-9 w-9 items-center justify-center rounded-md bg-emerald-50 text-emerald-700"><i class="fa-solid fa-user-check"></i></span></div>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['hadir'] }}</p>
        </div>
    </section>

    <section id="kelas" class="space-y-4">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Kelas Saya</h2>
                <p class="mt-1 text-sm text-slate-500">Sesi QR, daftar hadir, dan rekap tersedia pada setiap jadwal.</p>
            </div>
        </div>

        <div id="rekap" class="space-y-4">
        @forelse($jadwalList as $jadwal)
            <article class="overflow-hidden rounded-md border border-slate-200 bg-white">
                <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
                    <div class="min-w-0">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span class="rounded bg-violet-50 px-2 py-1 font-mono text-xs font-bold text-violet-800">{{ $jadwal->mataKuliah->kode_mk }}</span>
                            <span class="text-xs text-slate-500"><i class="fa-regular fa-clock mr-1"></i>{{ $jadwal->hari }}, {{ $jadwal->jam_mulai }}–{{ $jadwal->jam_selesai }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">{{ $jadwal->mataKuliah->nama_mk }}</h3>
                        <p class="mt-1 text-xs text-slate-500"><i class="fa-solid fa-location-dot mr-1 text-rose-600"></i>Radius presensi {{ $jadwal->radius_meter }} meter</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <form method="POST" action="{{ route('dosen.pertemuan.store', $jadwal) }}">
                            @csrf
                            <input type="hidden" name="pertemuan_ke" value="{{ ($jadwal->pertemuan->max('pertemuan_ke') ?? 0) + 1 }}">
                            <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-violet-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-violet-700">
                                <i class="fa-solid fa-plus"></i> Buat & Buka Pertemuan {{ ($jadwal->pertemuan->max('pertemuan_ke') ?? 0) + 1 }}
                            </button>
                        </form>
                        <a href="{{ route('dosen.jadwal.export_rekap', $jadwal) }}" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="fa-solid fa-file-arrow-down text-emerald-700"></i> Ekspor CSV
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Pertemuan</th>
                                <th class="px-4 py-2.5 font-semibold">Status QR</th>
                                <th class="px-4 py-2.5 font-semibold">Total hadir</th>
                                <th class="px-4 py-2.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($jadwal->pertemuan->sortByDesc('pertemuan_ke') as $pertemuan)
                                <tr class="hover:bg-violet-50/30">
                                    <td class="px-4 py-3 font-semibold text-slate-800">Pertemuan {{ $pertemuan->pertemuan_ke }}</td>
                                    <td class="px-4 py-3">
                                        @if($pertemuan->is_active && !$pertemuan->isExpired())
                                            <span class="text-xs font-semibold text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i>QR aktif</span>
                                        @else
                                            <span class="text-xs font-medium text-slate-500"><i class="fa-regular fa-clock mr-1"></i>QR kedaluwarsa</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $pertemuan->presensi->where('status', 'Hadir')->count() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('dosen.pertemuan.qr', $pertemuan) }}" class="inline-flex items-center gap-1.5 font-semibold text-violet-700 hover:text-violet-900">
                                            Lihat QR & daftar hadir <i class="fa-solid fa-arrow-right text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada sesi pertemuan untuk kelas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @empty
            <div class="rounded-md border border-slate-200 bg-white px-5 py-12 text-center">
                <i class="fa-solid fa-chalkboard-user text-3xl text-slate-300"></i>
                <h3 class="mt-3 font-bold text-slate-800">Belum ada jadwal kuliah</h3>
                <p class="mt-1 text-sm text-slate-500">Jadwal kelas yang ditugaskan kepada Anda akan tampil di sini.</p>
            </div>
        @endforelse
        </div>
    </section>
</div>
@endsection
