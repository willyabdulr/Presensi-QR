@extends('layouts.app')

@section('title', 'Riwayat Presensi Mahasiswa')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-black tracking-tight text-slate-900">Riwayat Presensi</h1>
        <p class="text-sm text-slate-500">Rekap kehadiran Anda berdasarkan mata kuliah dan pertemuan.</p>
    </div>

    <a href="{{ route('mahasiswa.scan') }}" class="inline-flex min-h-10 items-center self-start rounded-md bg-blue-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">
        <i class="fa-solid fa-camera mr-2"></i> Buka Scanner Presensi
    </a>
</div>

<section class="mb-6 rounded-md border border-slate-200 bg-white p-5" aria-labelledby="attendance-summary-title">
    <h2 id="attendance-summary-title" class="mb-3 text-sm font-bold text-slate-900">Ringkasan Presensi</h2>
    <div class="flex flex-wrap gap-3">
        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-bold text-emerald-700">
            <i class="fa-solid fa-circle-check"></i>{{ $ringkasanPresensi['Hadir'] }} Hadir
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-sm font-bold text-rose-700">
            <i class="fa-solid fa-circle-xmark"></i>{{ $ringkasanPresensi['Tidak Hadir'] }} Tidak Hadir
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-sm font-bold text-amber-700">
            <i class="fa-solid fa-clock"></i>{{ $ringkasanPresensi['Menunggu'] }} Menunggu
        </span>
    </div>
</section>

<div class="space-y-4">
    @forelse($riwayatPerkuliahan as $mataKuliah)
        <details class="overflow-hidden rounded-md border border-slate-200 bg-white" {{ $loop->first ? 'open' : '' }}>
            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-5 py-4 transition hover:bg-slate-50">
                <div>
                    <h2 class="font-bold text-slate-900">{{ $mataKuliah['mata_kuliah']->nama_mk }}</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        <span class="font-mono">{{ $mataKuliah['mata_kuliah']->kode_mk }}</span>
                        <span class="mx-1 text-slate-300">·</span>
                        Kelas {{ $mataKuliah['kode_kelas'] }}
                    </p>
                </div>
                <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
            </summary>

            <div class="border-t border-slate-100">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Pertemuan</th>
                                <th class="px-5 py-3">Topik</th>
                                <th class="px-5 py-3">Tanggal</th>
                                <th class="px-5 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($mataKuliah['pertemuan'] as $item)
                                @php
                                    $meeting = $item['pertemuan'];
                                    $statusStyles = match ($item['status']) {
                                        'Hadir' => 'bg-emerald-100 text-emerald-800',
                                        'Tidak Hadir' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                    $statusIcons = match ($item['status']) {
                                        'Hadir' => 'fa-circle-check',
                                        'Tidak Hadir' => 'fa-circle-xmark',
                                        default => 'fa-clock',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3.5 font-semibold text-slate-700">Pertemuan {{ $meeting->pertemuan_ke }}</td>
                                    <td class="px-5 py-3.5 text-slate-600">{{ $meeting->topik }}</td>
                                    <td class="px-5 py-3.5 text-slate-600">
                                        {{ $meeting->tanggal_pertemuan?->locale('id')->translatedFormat('d M Y') ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $statusStyles }}">
                                            <i class="fa-solid {{ $statusIcons }} text-[10px]"></i>{{ $item['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    @empty
        <div class="rounded-md border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <i class="fa-solid fa-book-open mb-3 text-2xl text-slate-300"></i>
            <p class="font-semibold text-slate-600">Belum ada pertemuan untuk kelas Anda.</p>
            <p class="mt-1 text-sm text-slate-500">Riwayat presensi akan muncul di sini setelah jadwal perkuliahan tersedia.</p>
        </div>
    @endforelse
</div>
@endsection
