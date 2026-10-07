@extends('layouts.app')

@section('title', 'Dashboard Mahasiswa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-blue-700">Dashboard Mahasiswa</p>
            <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Halo, {{ auth('mahasiswa')->user()->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">Pantau presensi kuliah dan aktivitas terbaru Anda.</p>
        </div>
        <a href="{{ route('mahasiswa.scan') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
            <i class="fa-solid fa-camera"></i> Scan Presensi
        </a>
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3" aria-label="Ringkasan presensi">
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">Total kehadiran</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-blue-50 text-blue-700"><i class="fa-solid fa-calendar-check"></i></span>
            </div>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Presensi berhasil tercatat</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">Minggu ini</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-emerald-50 text-emerald-700"><i class="fa-solid fa-calendar-week"></i></span>
            </div>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['minggu_ini'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Kehadiran pada minggu berjalan</p>
        </div>
        <div class="rounded-md border border-slate-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">Mata kuliah</span>
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-violet-50 text-violet-700"><i class="fa-solid fa-book-open"></i></span>
            </div>
            <p class="mt-3 text-3xl font-bold text-slate-900">{{ $stats['mata_kuliah'] }}</p>
            <p class="mt-1 text-xs text-slate-500">Mata kuliah dengan presensi tercatat</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h2 class="font-bold text-slate-900">Presensi Terbaru</h2>
                <p class="mt-0.5 text-xs text-slate-500">Aktivitas kehadiran yang paling baru tercatat.</p>
            </div>
            <a href="{{ route('mahasiswa.riwayat') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Lihat riwayat <i class="fa-solid fa-arrow-right ml-1 text-xs"></i></a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[650px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Mata Kuliah</th>
                        <th class="px-4 py-3 font-semibold">Pertemuan</th>
                        <th class="px-4 py-3 font-semibold">Dosen</th>
                        <th class="px-4 py-3 font-semibold">Waktu</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentPresensi as $item)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $item->pertemuan->jadwalKuliah->mataKuliah->nama_mk ?? '-' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $item->pertemuan->jadwalKuliah->mataKuliah->kode_mk ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->pertemuan->pertemuan_ke ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->pertemuan->jadwalKuliah->dosen->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->waktu_presensi?->format('d M Y, H:i') ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700"><i class="fa-solid fa-circle-check"></i>{{ $item->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">Belum ada presensi yang tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
