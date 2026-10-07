@extends('layouts.app')

@section('title', 'Rekap Presensi')

@section('content')
<div class="mb-5 flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-semibold text-violet-700">Laporan Dosen</p>
        <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Rekap Presensi</h1>
        <p class="mt-1 text-sm text-slate-600">Pilih mata kuliah dan kelas untuk melihat ringkasan kehadiran.</p>
    </div>
    <a href="{{ route('dosen.dashboard') }}#kelas" class="inline-flex h-10 items-center gap-2 self-start rounded-md border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Kelas
    </a>
</div>

<section class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Ringkasan rekap presensi">
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
    <section class="space-y-5">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Mata Kuliah</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih kategori mata kuliah untuk memfilter rekap kelas.</p>
        </div>

        <nav class="flex gap-3 overflow-x-auto pb-2" aria-label="Pilih mata kuliah">
            @foreach($mataKuliahList as $mataKuliah)
                <a
                    href="{{ route('dosen.attendance', ['mata_kuliah' => $mataKuliah->id]) }}"
                    @class([
                        'flex min-w-56 shrink-0 flex-col gap-2 rounded-md border p-4 transition',
                        'border-violet-700 bg-violet-700 text-white' => $selectedMataKuliah?->id === $mataKuliah->id,
                        'border-slate-200 bg-white text-slate-800 hover:border-violet-300' => $selectedMataKuliah?->id !== $mataKuliah->id,
                    ])
                    aria-current="{{ $selectedMataKuliah?->id === $mataKuliah->id ? 'page' : 'false' }}"
                >
                    <span class="font-mono text-xs font-bold {{ $selectedMataKuliah?->id === $mataKuliah->id ? 'text-violet-100' : 'text-violet-700' }}">{{ $mataKuliah->kode_mk }}</span>
                    <span class="font-semibold">{{ $mataKuliah->nama_mk }}</span>
                </a>
            @endforeach
        </nav>

        <section class="rounded-md border border-slate-200 bg-white p-4 sm:p-5" aria-label="Pilih kelas">
            <div class="mb-4">
                <h3 class="font-bold text-slate-900">Kelas · {{ $selectedMataKuliah->nama_mk }}</h3>
                <p class="mt-1 text-sm text-slate-500">Pilih satu kelas untuk melihat rekap seluruh pertemuannya.</p>
            </div>
            <nav class="flex gap-2 overflow-x-auto pb-2" aria-label="Pilih kelas">
                @foreach($kelasReports as $report)
                    @php
                        $jadwal = $report['jadwal'];
                    @endphp
                    <a
                        href="{{ route('dosen.attendance', ['mata_kuliah' => $selectedMataKuliah->id, 'jadwal' => $jadwal->id]) }}"
                        @class([
                            'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition',
                            'border-blue-700 bg-blue-700 text-white' => $selectedReport['jadwal']->id === $jadwal->id,
                            'border-slate-200 bg-slate-50 text-slate-700 hover:border-blue-300 hover:text-blue-800' => $selectedReport['jadwal']->id !== $jadwal->id,
                        ])
                        aria-current="{{ $selectedReport['jadwal']->id === $jadwal->id ? 'page' : 'false' }}"
                    >
                        <i class="fa-solid fa-users text-xs"></i>{{ $jadwal->kelas ?: 'Kelas belum ditentukan' }}
                    </a>
                @endforeach
            </nav>
        </section>

        @if($selectedReport)
            @php
                $jadwal = $selectedReport['jadwal'];
                $pertemuanList = $selectedReport['pertemuan'];
                $rows = $selectedReport['rows'];
            @endphp

            <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
                <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded bg-violet-50 px-2 py-1 font-mono text-xs font-bold text-violet-800">{{ $jadwal->mataKuliah->kode_mk }}</span>
                            <span class="text-xs text-slate-500">{{ $jadwal->hari }}, {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</span>
                        </div>
                        <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $jadwal->mataKuliah->nama_mk }} · {{ $jadwal->kelas ?: 'Kelas belum ditentukan' }}</h2>
                    </div>
                    <a href="{{ route('dosen.jadwal.export_rekap', $jadwal) }}" class="inline-flex h-9 items-center gap-2 self-start rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 sm:self-auto">
                        <i class="fa-solid fa-file-arrow-down text-emerald-700"></i> Ekspor CSV
                    </a>
                </div>

                @if($pertemuanList->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada pertemuan untuk kelas ini.</p>
                @elseif($rows->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada mahasiswa atau catatan presensi untuk kelas ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Mahasiswa</th>
                                    <th class="px-4 py-3 font-semibold">NIM</th>
                                    @foreach($pertemuanList as $pertemuan)
                                        <th class="px-3 py-3 text-center font-semibold">
                                            <span class="block">P{{ $pertemuan->pertemuan_ke }}</span>
                                            <span class="mt-1 block max-w-32 normal-case font-normal text-slate-500">{{ $pertemuan->topik ?: 'Pertemuan' }}</span>
                                        </th>
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
                                            @php($status = $row['statuses'][$pertemuan->id] ?? null)
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
                    <p class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500 sm:px-5">Rekap menampilkan mahasiswa terdaftar di kelas dan status tiap pertemuan.</p>
                @endif
            </section>
        @endif
    </section>
@endif
@endsection
