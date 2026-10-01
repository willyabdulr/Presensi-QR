@extends('layouts.app')

@section('title', 'Riwayat Presensi Mahasiswa')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Riwayat Presensi</h1>
        <p class="text-sm text-slate-500">Daftar rekaman kehadiran perkuliahan yang telah Anda lakukan.</p>
    </div>

    <div>
        <a href="{{ route('mahasiswa.scan') }}" class="inline-flex min-h-10 items-center rounded-md bg-blue-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">
            <i class="fa-solid fa-camera mr-2"></i> Buka Scanner Presensi
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-md border border-slate-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <th class="py-3 px-4">No</th>
                    <th class="py-3 px-4">Mata Kuliah</th>
                    <th class="py-3 px-4">Pertemuan</th>
                    <th class="py-3 px-4">Dosen Pengampu</th>
                    <th class="py-3 px-4">Waktu Presensi</th>
                    <th class="py-3 px-4">Jarak ke Kelas</th>
                    <th class="py-3 px-4 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($riwayatPresensi as $index => $item)
                    <tr class="transition hover:bg-blue-50/40">
                        <td class="py-3.5 px-4 text-xs text-slate-400">{{ $riwayatPresensi->firstItem() + $index }}</td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-slate-900">{{ $item->pertemuan->jadwalKuliah->mataKuliah->nama_mk ?? '-' }}</div>
                            <div class="text-xs font-mono text-slate-400">{{ $item->pertemuan->jadwalKuliah->mataKuliah->kode_mk ?? '-' }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-xs font-semibold text-slate-700">Pertemuan {{ $item->pertemuan->pertemuan_ke }}</td>
                        <td class="py-3.5 px-4 text-xs text-slate-600">{{ $item->pertemuan->jadwalKuliah->dosen->name ?? '-' }}</td>
                        <td class="py-3.5 px-4 text-xs font-mono text-slate-600">{{ $item->waktu_presensi ? $item->waktu_presensi->format('d/m/Y H:i:s') : '-' }} WIB</td>
                        <td class="py-3.5 px-4 text-xs font-mono text-slate-700">{{ $item->jarak_meter !== null ? round($item->jarak_meter, 1) . ' m' : '-' }}</td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center rounded px-2.5 py-1 text-xs font-bold bg-emerald-100 text-emerald-800">
                                <i class="fa-solid fa-check text-[10px] mr-1"></i> {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">Belum ada rekaman presensi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($riwayatPresensi->hasPages())
        <div class="p-4 border-t border-slate-100">{{ $riwayatPresensi->links() }}</div>
    @endif
</div>
@endsection
