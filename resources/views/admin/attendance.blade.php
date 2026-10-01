@extends('layouts.app')

@section('title', 'Rekap Presensi')

@section('content')
<div class="mb-5">
    <p class="text-sm font-semibold text-teal-700">Laporan Akademik</p>
    <h1 class="mt-1 text-2xl font-bold text-[#17385f]">Rekap Presensi</h1>
    <p class="mt-1 text-sm text-slate-500">Ringkasan kehadiran tercatat per sesi perkuliahan.</p>
</div>

<div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
    <div class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Total sesi pertemuan</p>
        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($totalMeetings) }}</p>
    </div>
    <div class="rounded-md border border-slate-200 bg-white p-4">
        <p class="text-sm text-slate-500">Total presensi hadir</p>
        <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($totalAttendance) }}</p>
    </div>
</div>

<section class="overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-semibold">Mata Kuliah</th>
                    <th class="px-4 py-3 font-semibold">Dosen</th>
                    <th class="px-4 py-3 font-semibold">Pertemuan</th>
                    <th class="px-4 py-3 font-semibold">Tanggal sesi</th>
                    <th class="px-4 py-3 text-right font-semibold">Hadir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($meetingList as $meeting)
                    <tr class="hover:bg-teal-50/30">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ $meeting->jadwalKuliah->mataKuliah->nama_mk ?? '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $meeting->jadwalKuliah->mataKuliah->kode_mk ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $meeting->jadwalKuliah->dosen->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">Pertemuan {{ $meeting->pertemuan_ke }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $meeting->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ $meeting->total_hadir }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">Belum ada sesi yang dapat direkap.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($meetingList->hasPages())
        <div class="border-t border-slate-200 px-4 py-3">{{ $meetingList->links() }}</div>
    @endif
</section>
@endsection
