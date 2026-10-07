@extends('layouts.app')

@section('title', 'Rekap Presensi')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-teal-700">Monitoring Akademik</p>
    <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Rekap Presensi</h1>
    <p class="mt-1 text-sm text-slate-500">Pilih mata kuliah dan kelas untuk memantau status pertemuan. Halaman ini hanya untuk melihat data.</p>
</div>

<section class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2" aria-label="Ringkasan presensi">
    <div class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Total sesi pertemuan</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalMeetings) }}</p>
    </div>
    <div class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Total presensi hadir</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($totalAttendance) }}</p>
    </div>
</section>

@if($mataKuliahList->isEmpty())
    <section class="rounded-md border border-slate-200 bg-white px-5 py-12 text-center">
        <h2 class="font-bold text-slate-800">Belum ada mata kuliah untuk dimonitor</h2>
    </section>
@else
    <section class="space-y-5">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Mata Kuliah</h2>
            <p class="mt-1 text-sm text-slate-500">Pilih mata kuliah untuk melihat kelas terkait.</p>
        </div>

        <nav class="flex gap-3 overflow-x-auto pb-2" aria-label="Pilih mata kuliah">
            @foreach($mataKuliahList as $mataKuliah)
                <a
                    href="{{ route('admin.attendance', ['mata_kuliah' => $mataKuliah->id]) }}"
                    @class([
                        'flex min-w-56 shrink-0 flex-col gap-2 rounded-md border p-4 transition',
                        'border-teal-700 bg-teal-700 text-white' => $selectedMataKuliah?->id === $mataKuliah->id,
                        'border-slate-200 bg-white text-slate-800 hover:border-teal-300' => $selectedMataKuliah?->id !== $mataKuliah->id,
                    ])
                    aria-current="{{ $selectedMataKuliah?->id === $mataKuliah->id ? 'page' : 'false' }}"
                >
                    <span class="font-mono text-xs font-bold {{ $selectedMataKuliah?->id === $mataKuliah->id ? 'text-teal-100' : 'text-teal-700' }}">{{ $mataKuliah->kode_mk }}</span>
                    <span class="font-semibold">{{ $mataKuliah->nama_mk }}</span>
                </a>
            @endforeach
        </nav>

        @if($selectedMataKuliah)
            <section class="rounded-md border border-slate-200 bg-white p-4 sm:p-5" aria-label="Pilih kelas">
                <div class="mb-4">
                    <h3 class="font-bold text-slate-900">Kelas · {{ $selectedMataKuliah->nama_mk }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Pilih kelas untuk melihat status setiap pertemuan.</p>
                </div>
                <nav class="flex gap-2 overflow-x-auto pb-2" aria-label="Pilih kelas">
                    @forelse($kelasList as $jadwal)
                        <a
                            href="{{ route('admin.attendance', ['mata_kuliah' => $selectedMataKuliah->id, 'jadwal' => $jadwal->id]) }}"
                            @class([
                                'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition',
                                'border-blue-700 bg-blue-700 text-white' => $selectedJadwal?->id === $jadwal->id,
                                'border-slate-200 bg-slate-50 text-slate-700 hover:border-blue-300 hover:text-blue-800' => $selectedJadwal?->id !== $jadwal->id,
                            ])
                            aria-current="{{ $selectedJadwal?->id === $jadwal->id ? 'page' : 'false' }}"
                        >
                            <i class="fa-solid fa-users text-xs"></i>{{ $jadwal->kelas ?: $jadwal->kelasData?->kode_kelas ?: 'Kelas belum ditentukan' }}
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada kelas terjadwal untuk mata kuliah ini.</p>
                    @endforelse
                </nav>
            </section>
        @endif

        @if($selectedJadwal)
            <section class="overflow-hidden rounded-md border border-slate-200 bg-white">
                <div class="border-b border-slate-200 p-4 sm:px-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded bg-teal-50 px-2 py-1 font-mono text-xs font-bold text-teal-800">{{ $selectedJadwal->mataKuliah->kode_mk }}</span>
                        <span class="text-xs text-slate-500">{{ $selectedJadwal->hari }}, {{ substr($selectedJadwal->jam_mulai, 0, 5) }}–{{ substr($selectedJadwal->jam_selesai, 0, 5) }}</span>
                    </div>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $selectedJadwal->mataKuliah->nama_mk }} · {{ $selectedJadwal->kelas ?: $selectedJadwal->kelasData?->kode_kelas ?: 'Kelas belum ditentukan' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Dosen utama: {{ $selectedJadwal->dosen->name ?? 'Belum ditentukan' }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Pertemuan</th>
                                <th class="px-4 py-3 font-semibold">Topik</th>
                                <th class="px-4 py-3 font-semibold">Tanggal</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pertemuanList as $pertemuan)
                                <tr class="hover:bg-teal-50/30">
                                    <td class="px-4 py-3 font-semibold text-slate-800">Pertemuan {{ $pertemuan->pertemuan_ke }}</td>
                                    <td class="px-4 py-3">
                                        <span class="block text-slate-800">{{ $pertemuan->topik }}</span>
                                        @if($pertemuan->dosenPengganti)
                                            <span class="mt-1 inline-flex rounded bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">Dosen pengganti: {{ $pertemuan->dosenPengganti->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $pertemuan->tanggal_pertemuan?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-slate-100 text-slate-700' => $pertemuan->status_pertemuan === 'Terjadwal',
                                            'bg-blue-50 text-blue-800' => $pertemuan->status_pertemuan === 'Berlangsung',
                                            'bg-emerald-50 text-emerald-800' => $pertemuan->status_pertemuan === 'Selesai',
                                            'bg-rose-50 text-rose-800' => $pertemuan->status_pertemuan === 'Dibatalkan',
                                        ])>{{ $pertemuan->status_pertemuan }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                        @if($pertemuan->status_pertemuan === 'Terjadwal')
                                            -
                                        @else
                                            {{ $pertemuan->total_hadir }} / {{ $jumlahMahasiswa }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada pertemuan untuk kelas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </section>
@endif
@endsection
