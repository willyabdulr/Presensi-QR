@extends('layouts.app')

@section('title', 'Rekap Absensi')

@section('content')
<div class="mb-5 flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-violet-700">Laporan Dosen</p>
        <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Rekap Absensi</h1>
        <p class="mt-1 text-sm text-slate-600">Rekap kehadiran mahasiswa di setiap kelas yang Anda ampu.</p>
    </div>
    <a href="{{ route('dosen.dashboard') }}#kelas" class="inline-flex h-10 items-center gap-2 self-start rounded-md border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Kelas
    </a>
</div>

<section class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Ringkasan rekap absensi">
    <div class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Kelas diampu</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['kelas'] }}</p>
    </div>
    <div class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Total pertemuan</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $stats['pertemuan'] }}</p>
    </div>
    <div class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Catatan hadir</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $stats['hadir'] }}</p>
    </div>
</section>

@if($reports->isEmpty())
    <section class="rounded-md border border-slate-200 bg-white px-5 py-12 text-center">
        <i class="fa-solid fa-chart-column text-3xl text-slate-300"></i>
        <h2 class="mt-3 font-bold text-slate-800">Belum ada kelas untuk direkap</h2>
        <p class="mt-1 text-sm text-slate-500">Jadwal yang ditugaskan kepada Anda akan muncul di halaman ini.</p>
    </section>
@else
    <div class="space-y-5">
        @foreach($reports as $report)
            @php
                $jadwal = $report['jadwal'];
                $pertemuanList = $report['pertemuan'];
                $rows = $report['rows'];
            @endphp

            <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
                <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded bg-violet-50 px-2 py-1 font-mono text-xs font-bold text-violet-800">{{ $jadwal->mataKuliah->kode_mk }}</span>
                            <span class="text-xs text-slate-500">{{ $jadwal->hari }}, {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</span>
                        </div>
                        <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $jadwal->mataKuliah->nama_mk }}</h2>
                    </div>
                    <a href="{{ route('dosen.jadwal.export_rekap', $jadwal) }}" class="inline-flex h-9 items-center gap-2 self-start rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto">
                        <i class="fa-solid fa-file-arrow-down text-emerald-700"></i> Ekspor CSV
                    </a>
                </div>

                @if($pertemuanList->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada pertemuan untuk kelas ini.</p>
                @elseif($rows->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada catatan presensi untuk pertemuan di kelas ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Mahasiswa</th>
                                    <th class="px-4 py-3 font-semibold">NIM</th>
                                    @foreach($pertemuanList as $pertemuan)
                                        <th class="px-3 py-3 text-center font-semibold">P{{ $pertemuan->pertemuan_ke }}</th>
                                    @endforeach
                                    <th class="px-4 py-3 text-right font-semibold">Total hadir</th>
                                    <th class="px-4 py-3 text-right font-semibold">Persentase</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($rows as $row)
                                    <tr class="hover:bg-violet-50/30">
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $row['mahasiswa']->name }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $row['mahasiswa']->nomor_induk }}</td>
                                        @foreach($pertemuanList as $pertemuan)
                                            @php $status = $row['statuses'][$pertemuan->id] ?? null; @endphp
                                            <td class="px-3 py-3 text-center">
                                                @if($status === 'Hadir')
                                                    <span class="rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800">Hadir</span>
                                                @elseif($status)
                                                    <span class="rounded bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-800">{{ $status }}</span>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ $row['total_hadir'] }} / {{ $pertemuanList->count() }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format($row['persentase'], 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500 sm:px-5">Tabel menampilkan mahasiswa yang memiliki setidaknya satu catatan presensi di kelas ini.</p>
                @endif
            </section>
        @endforeach
    </div>
@endif
@endsection
