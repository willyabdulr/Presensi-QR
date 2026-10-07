@extends('layouts.app')

@section('title', 'Sesi QR Presensi - Pertemuan ' . $pertemuan->pertemuan_ke)

@section('content')
@php($isSubstituteLecturer = $pertemuan->dosen_pengganti_id === auth('dosen')->id())
<section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter jadwal">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label for="courseFilter" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Mata Kuliah</label>
            <select id="courseFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @foreach($jadwalList->unique('mata_kuliah_id') as $jadwal)
                    <option value="{{ $jadwal->mata_kuliah_id }}" @selected($jadwal->mata_kuliah_id === $pertemuan->jadwalKuliah->mata_kuliah_id)>{{ $jadwal->mataKuliah->nama_mk }} ({{ $jadwal->mataKuliah->kode_mk }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="classFilter" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Kelas</label>
            <select id="classFilter" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                @foreach($jadwalList as $jadwal)
                    @php($jadwalSession = $jadwal->pertemuan->first())
                    <option
                        value="{{ $jadwal->id }}"
                        data-course-id="{{ $jadwal->mata_kuliah_id }}"
                        data-url="{{ $jadwalSession ? route('dosen.pertemuan.qr', $jadwalSession) : '' }}"
                        @disabled($jadwalSession === null)
                        @selected($jadwal->id === $pertemuan->jadwal_kuliah_id)
                    >{{ $jadwal->kelas ?: 'Kelas belum ditentukan' }} · {{ $jadwal->hari }} ({{ substr($jadwal->jam_mulai, 0, 5) }})</option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <nav class="mb-1 flex text-xs text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('dosen.dashboard') }}" class="hover:text-blue-600">Dashboard</a>
            <span class="mx-2">/</span>
            <span class="font-medium text-slate-700">Pertemuan {{ $pertemuan->pertemuan_ke }}</span>
        </nav>
        <h1 class="text-2xl font-black tracking-tight text-slate-900">
            {{ $pertemuan->jadwalKuliah->mataKuliah->nama_mk ?? 'Mata Kuliah' }} ({{ $pertemuan->jadwalKuliah->mataKuliah->kode_mk ?? '-' }})
        </h1>
        <p class="text-sm text-slate-500">
            Pertemuan {{ $pertemuan->pertemuan_ke }} — {{ $pertemuan->topik }}
        </p>
        <p class="mt-1 text-sm text-slate-500">
            Tanggal: {{ $pertemuan->tanggal_pertemuan?->translatedFormat('d F Y') ?? 'Belum diatur' }}
            <span class="px-1">·</span>{{ $pertemuan->jadwalKuliah->kelas ?: 'Kelas belum ditentukan' }}
            <span class="px-1">·</span>{{ $pertemuan->status_pertemuan }}
        </p>
        <p class="mt-1 text-sm text-slate-500">
            Dosen: {{ $pertemuan->dosenPengganti->name ?? $pertemuan->jadwalKuliah->dosen->name }}
            @if($pertemuan->dosenPengganti)
                <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-800">Dosen Pengganti</span>
            @endif
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if($pertemuan->status_pertemuan === 'Berlangsung')
        <button type="button" onclick="openQrProjection()" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
            <i class="fa-solid fa-expand"></i> Buka Layar QR Code
        </button>
        <form method="POST" action="{{ route('dosen.pertemuan.end', $pertemuan) }}" onsubmit="return confirm('Akhiri pertemuan dan tutup presensi?')">
            @csrf
            <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-rose-700 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-rose-800">
                <i class="fa-solid fa-stop"></i> Akhiri Pertemuan
            </button>
        </form>
        @endif
        @unless($isSubstituteLecturer)
        <a href="{{ route('dosen.jadwal.export_rekap', $pertemuan->jadwal_kuliah_id) }}" class="inline-flex min-h-10 items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 hover:shadow">
            <i class="fa-solid fa-file-csv mr-2"></i> Ekspor Rekap CSV
        </a>
        @endunless
        <a href="{{ route('dosen.dashboard') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali
        </a>
    </div>
</div>

<nav class="mb-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-3 shadow-sm" aria-label="Pilih pertemuan">
    <div class="flex min-w-max gap-2">
        @for($meetingNumber = 1; $meetingNumber <= 16; $meetingNumber++)
            @php($meeting = $pertemuan->jadwalKuliah->pertemuan->firstWhere('pertemuan_ke', $meetingNumber))
            @if($meeting)
                <a href="{{ route('dosen.pertemuan.qr', $meeting) }}" @class([
                    'inline-flex h-10 min-w-12 items-center justify-center rounded-full px-4 text-sm font-bold transition',
                    'bg-blue-600 text-white shadow-sm' => $meeting->id === $pertemuan->id,
                    'bg-slate-100 text-slate-700 hover:bg-blue-50 hover:text-blue-700' => $meeting->id !== $pertemuan->id,
                ]) aria-current="{{ $meeting->id === $pertemuan->id ? 'page' : 'false' }}">P{{ $meetingNumber }}</a>
            @else
                <button type="button" disabled class="inline-flex h-10 min-w-12 items-center justify-center rounded-full bg-slate-50 px-4 text-sm font-semibold text-slate-300" title="Pertemuan belum dibuat">P{{ $meetingNumber }}</button>
            @endif
        @endfor
    </div>
</nav>

<div class="space-y-6">
    @if($pertemuan->status_pertemuan === 'Berlangsung')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Card QR Code --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col items-center text-center relative overflow-hidden">
            <div class="w-full flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">QR CODE DINAMIS</span>
                <span id="qrStatusBadge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    <span class="w-2 h-2 mr-1.5 rounded-full bg-emerald-500 animate-pulse"></span> AKTIF
                </span>
            </div>

            <div class="mb-4">
                <div class="text-xs text-slate-500 uppercase tracking-widest font-semibold mb-1">Sisa Masa Berlaku Token</div>
                <div id="countdownTimer" class="text-4xl font-black font-mono tracking-tight text-indigo-600 bg-indigo-50/70 border border-indigo-100 px-6 py-2 rounded-2xl shadow-inner">
                    --:--
                </div>
                <p class="text-xs text-slate-400 mt-1">Berlaku tepat 20 menit per generasi QR</p>
            </div>

            <div class="relative p-4 bg-white border-2 border-dashed border-slate-200 rounded-2xl shadow-sm mb-5 group">
                <div id="qrcodeWrapper" class="flex justify-center items-center">
                    <div id="qrcodeCanvas"></div>
                </div>

                <div id="expiredOverlay" class="hidden absolute inset-0 bg-slate-900/80 backdrop-blur-sm rounded-2xl flex flex-col items-center justify-center p-4 text-white">
                    <i class="fa-solid fa-clock-rotate-left text-3xl text-rose-400 mb-2"></i>
                    <p class="font-bold text-sm">QR Code Kedaluwarsa</p>
                    <p class="text-xs text-slate-300 mb-3">Masa berlaku 20 menit telah habis</p>
                    <button onclick="regenerateQrCode()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-lg transition">
                        Perbarui Sekarang
                    </button>
                </div>
            </div>

            <div class="w-full space-y-3">
                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-mono">Token:</span>
                    <span id="tokenPreview" class="text-slate-700 font-mono font-bold truncate max-w-[200px]" title="{{ $pertemuan->qr_token }}">
                        {{ $pertemuan->qr_token }}
                    </span>
                    <button onclick="copyToken()" class="text-indigo-600 hover:text-indigo-800 p-1" title="Salin Token">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </div>

                <button id="btnRegenerate" onclick="regenerateQrCode()" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-slate-900 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl transition shadow">
                    <i class="fa-solid fa-arrows-rotate mr-2" id="iconRotate"></i> Generate QR Baru (Reset 20 Menit)
                </button>
            </div>
        </div>

        {{-- Card Manajemen Geolokasi & Sinkronisasi GPS --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-location-crosshairs text-indigo-600 mr-2 text-base"></i> Pengaturan Geolokasi Kelas
                </h3>
                <span id="gpsDosenStatus" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i> Mencari GPS...
                </span>
            </div>

            {{-- Status Banner Peringatan Selisih Jarak --}}
            <div id="distanceAlertBox" class="p-3 rounded-xl text-xs space-y-1 bg-amber-50 border border-amber-200 text-amber-900">
                <div class="font-bold flex items-center">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5 text-amber-600"></i>
                    <span id="alertTitle">Memeriksa kesesuaian lokasi...</span>
                </div>
                <p id="alertDesc" class="text-[11px] text-amber-800">
                    Izinkan akses GPS browser agar sistem dapat mencocokkan titik kelas dengan lokasi tempat Anda mengajar saat ini.
                </p>
            </div>

            {{-- Tombol Utama Sinkronisasi --}}
            <button type="button" onclick="syncClassLocationToMyGps(true)" id="btnSyncGps" class="w-full inline-flex items-center justify-center px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition">
                <i class="fa-solid fa-location-dot mr-2 text-sm" id="iconSyncGps"></i>
                <span id="textBtnSync">📍 SINKRONKAN TITIK KELAS KE GPS SAYA</span>
            </button>
            <p class="text-[11px] text-slate-400 text-center -mt-2">
                Klik tombol di atas agar mahasiswa yang berada di dekat Anda berhasil presensi!
            </p>

            {{-- Detail Koordinat Aktif --}}
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 space-y-2 text-xs">
                <div class="font-semibold text-slate-700 text-[11px] uppercase tracking-wider mb-1 flex items-center justify-between">
                    <span>Titik Koordinat Acuan Kelas</span>
                    <a id="linkGmaps" href="https://www.google.com/maps?q={{ $pertemuan->jadwalKuliah->latitude_kelas }},{{ $pertemuan->jadwalKuliah->longitude_kelas }}" target="_blank" class="text-indigo-600 hover:underline font-normal text-[10px]">
                        Buka di Maps <i class="fa-solid fa-arrow-up-right-from-square ml-0.5"></i>
                    </a>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 font-mono">
                    <span class="text-slate-500 font-sans">Latitude:</span>
                    <span class="font-bold text-slate-800" id="displayLat">{{ $pertemuan->jadwalKuliah->latitude_kelas }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-200/60 font-mono">
                    <span class="text-slate-500 font-sans">Longitude:</span>
                    <span class="font-bold text-slate-800" id="displayLon">{{ $pertemuan->jadwalKuliah->longitude_kelas }}</span>
                </div>

                {{-- Pengaturan Toleransi Radius --}}
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-1">
                        <label for="radiusSelector" class="text-slate-600 font-medium">Toleransi Radius Mahasiswa:</label>
                        <span id="currentRadiusBadge" class="font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded text-[11px]">
                            {{ min($pertemuan->jadwalKuliah->radius_meter, \App\Models\JadwalKuliah::MAX_RADIUS_METERS) }} Meter
                        </span>
                    </div>
                    <select id="radiusSelector" onchange="changeRadius(this.value)" class="w-full mt-1 px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @for($radiusMeter = 1; $radiusMeter <= \App\Models\JadwalKuliah::MAX_RADIUS_METERS; $radiusMeter++)
                            <option value="{{ $radiusMeter }}" @selected(min($pertemuan->jadwalKuliah->radius_meter, \App\Models\JadwalKuliah::MAX_RADIUS_METERS) == $radiusMeter)>{{ $radiusMeter }} Meter</option>
                        @endfor
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Batas jarak maksimal scan QR: {{ \App\Models\JadwalKuliah::MAX_RADIUS_METERS }} meter.</p>
                </div>
            </div>

            {{-- Detail Posisi Live Dosen --}}
            <div class="pt-1 text-xs text-slate-500 space-y-1">
                <div class="flex justify-between">
                    <span>GPS Perangkat Anda:</span>
                    <span id="dosenGpsCoords" class="font-mono text-slate-700 font-medium">Mencari...</span>
                </div>
                <div class="flex justify-between">
                    <span>Akurasi Sinyal GPS:</span>
                    <span id="dosenGpsAcc" class="font-mono text-slate-700 font-medium">-</span>
                </div>
                <div class="flex justify-between">
                    <span>Selisih ke Titik Kelas:</span>
                    <span id="dosenDistToClass" class="font-mono font-bold text-slate-900">-</span>
                </div>
            </div>
        </div>
    </div>

    </div>

    @else
        <div class="rounded-md border border-slate-200 bg-white px-5 py-6 text-center text-sm text-slate-600">
            Pertemuan {{ strtolower($pertemuan->status_pertemuan) }}. QR dan perubahan presensi tidak tersedia.
        </div>
    @endif

    {{-- Live Monitoring Presensi --}}
    <div class="flex flex-col">
        <div class="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900 text-lg flex items-center">
                        <i class="fa-solid fa-users-viewfinder text-blue-600 mr-2"></i> Rekap Presensi Mahasiswa
                    </h2>
                    <p class="text-xs text-slate-500">Status kehadiran seluruh mahasiswa di kelas ini</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-slate-400">Total Hadir:</span>
                    <span id="badgeTotalHadir" class="px-3 py-1 bg-indigo-100 text-indigo-800 font-extrabold text-sm rounded-xl">
                        {{ $pertemuan->presensi->where('status', 'Hadir')->count() }}
                    </span>
                    <span class="text-xs text-slate-400">Tidak Hadir:</span>
                    <span id="badgeTotalTidakHadir" class="px-3 py-1 bg-rose-100 text-rose-800 font-extrabold text-sm rounded-xl">
                        {{ $pertemuan->presensi->where('status', 'Tidak Hadir')->count() }}
                    </span>
                    <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-ping ml-1" title="Realtime Polling Aktif"></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">NIM</th>
                            <th class="px-4 py-3">Nama Mahasiswa</th>
                            <th class="px-4 py-3 text-center">Status Presensi</th>
                            <th class="px-4 py-3 text-center">{{ $pertemuan->status_pertemuan === 'Berlangsung' ? 'Absensi Manual' : 'Aksi' }}</th>
                        </tr>
                    </thead>
                    <tbody
                        id="attendanceTableBody"
                        class="divide-y divide-slate-100"
                        data-manual-url="{{ route('dosen.pertemuan.manual_attendance', ['pertemuan' => $pertemuan, 'presensi' => '__PRESENSI_ID__']) }}"
                    >
                        @forelse($pertemuan->presensi->sortBy(fn ($presensi) => $presensi->mahasiswa?->nomor_induk) as $item)
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="px-4 py-3 text-center text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->mahasiswa->nomor_induk ?? '-' }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->mahasiswa->name ?? 'Mahasiswa' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-emerald-100 text-emerald-800' => $item->status === 'Hadir',
                                        'bg-rose-100 text-rose-800' => $item->status !== 'Hadir',
                                    ])>{{ $item->status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($pertemuan->status_pertemuan === 'Berlangsung')
                                    <div class="flex justify-center gap-2">
                                        <form method="POST" action="{{ route('dosen.pertemuan.manual_attendance', [$pertemuan, $item]) }}">
                                            @csrf
                                            <button type="submit" name="status" value="Hadir" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">
                                                <i class="fa-solid fa-check"></i> Hadir
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('dosen.pertemuan.manual_attendance', [$pertemuan, $item]) }}">
                                            @csrf
                                            <button type="submit" name="status" value="Tidak Hadir" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-rose-700">
                                                <i class="fa-solid fa-xmark"></i> Tidak Hadir
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                        <span class="block text-center text-xs text-slate-400">Read-only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="5" class="py-12 text-center text-sm text-slate-400">
                                    <i class="fa-regular fa-clipboard text-3xl mb-2 text-slate-300 block"></i>
                                    Belum ada mahasiswa yang melakukan presensi pada pertemuan ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl flex items-center justify-between text-xs text-slate-400">
                <span id="lastUpdatedTime">Terakhir diperbarui: Baru saja</span>
                <span class="flex items-center">
                    <i class="fa-solid fa-arrows-rotate mr-1 animate-spin text-slate-400 text-[10px]"></i> Polling otomatis per 4 detik
                </span>
            </div>
        </div>
    </div>
</div>

@if($pertemuan->status_pertemuan === 'Berlangsung')
<dialog id="qrProjectionModal" class="m-auto max-h-[95vh] w-[min(95vw,900px)] max-w-none overflow-y-auto rounded-3xl bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/90">
    <div class="relative flex min-h-[80vh] flex-col items-center justify-center gap-5 p-6 text-center sm:p-10">
        <button type="button" onclick="closeQrProjection()" class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200" aria-label="Tutup layar QR Code">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.2em] text-blue-700">EduAttend · Presensi</p>
            <h2 class="mt-2 text-2xl font-black sm:text-3xl">{{ $pertemuan->jadwalKuliah->mataKuliah->nama_mk }} · P{{ $pertemuan->pertemuan_ke }} — {{ $pertemuan->topik }}</h2>
                <p class="mt-1 text-slate-500">{{ $pertemuan->jadwalKuliah->kelas ?: 'Kelas' }} · {{ $pertemuan->tanggal_pertemuan?->translatedFormat('d F Y') ?? 'Tanggal belum diatur' }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-lg sm:p-6">
            <div id="projectionQrCodeCanvas" class="flex min-h-[280px] min-w-[280px] items-center justify-center sm:min-h-[420px] sm:min-w-[420px]" aria-label="QR Code presensi"></div>
        </div>
        <div class="space-y-2">
            <p class="text-lg font-bold text-slate-800">QR Code diperbarui otomatis dalam <span id="projectionRefreshTimer" class="font-mono text-blue-700">15 detik</span></p>
            <p class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800">
                <i class="fa-solid fa-location-dot"></i> Batas Validasi Jarak: {{ \App\Models\JadwalKuliah::MAX_RADIUS_METERS }} Meter
            </p>
        </div>
    </div>
</dialog>
@endif
@endsection

@if($pertemuan->status_pertemuan === 'Berlangsung')
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let currentToken = "{{ $pertemuan->qr_token }}";
    let remainingSeconds = {{ $pertemuan->remainingSeconds() }};
    let currentClassLat = {{ (float) $pertemuan->jadwalKuliah->latitude_kelas }};
    let currentClassLon = {{ (float) $pertemuan->jadwalKuliah->longitude_kelas }};
    let currentRadius = {{ min($pertemuan->jadwalKuliah->radius_meter, \App\Models\JadwalKuliah::MAX_RADIUS_METERS) }};
    let dosenCurrentLat = null;
    let dosenCurrentLon = null;
    let dosenCurrentAcc = null;
    let countdownInterval = null;
    let pollingInterval = null;
    let projectionRefreshInterval = null;
    let projectionCountdownInterval = null;
    let projectionRefreshSeconds = 15;

    function initQrCode(token) {
        [
            { id: 'qrcodeCanvas', size: 230 },
            { id: 'projectionQrCodeCanvas', size: 390 }
        ].forEach(({ id, size }) => {
            const container = document.getElementById(id);
            container.innerHTML = '';
            new QRCode(container, {
                text: token,
                width: size,
                height: size,
                colorDark: "#111827",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });
    }

    function openQrProjection() {
        document.getElementById('qrProjectionModal').showModal();
        startProjectionRefresh();
    }

    function closeQrProjection() {
        document.getElementById('qrProjectionModal').close();
    }

    function startProjectionRefresh() {
        clearTimeout(projectionRefreshInterval);
        clearInterval(projectionCountdownInterval);
        if (remainingSeconds <= 0) {
            document.getElementById('projectionRefreshTimer').innerText = 'Sesi QR berakhir';
            return;
        }

        projectionRefreshSeconds = 15;
        document.getElementById('projectionRefreshTimer').innerText = `${projectionRefreshSeconds} detik`;
        projectionCountdownInterval = setInterval(() => {
            projectionRefreshSeconds--;
            document.getElementById('projectionRefreshTimer').innerText = `${projectionRefreshSeconds} detik`;

            if (projectionRefreshSeconds <= 0 || remainingSeconds <= 0) {
                clearInterval(projectionCountdownInterval);
                if (remainingSeconds <= 0) {
                    document.getElementById('projectionRefreshTimer').innerText = 'Sesi QR berakhir';
                    return;
                }

                projectionRefreshInterval = setTimeout(async () => {
                    await regenerateQrCode(false, true);
                    if (document.getElementById('qrProjectionModal').open && remainingSeconds > 0) {
                        startProjectionRefresh();
                    }
                }, 0);
            }
        }, 1000);
    }

    function stopProjectionRefresh() {
        clearTimeout(projectionRefreshInterval);
        clearInterval(projectionCountdownInterval);
    }

    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    function updateTimerDisplay() {
        const timerElem = document.getElementById('countdownTimer');
        const overlay = document.getElementById('expiredOverlay');
        const statusBadge = document.getElementById('qrStatusBadge');

        if (remainingSeconds <= 0) {
            timerElem.innerText = "00:00";
            timerElem.className = "text-4xl font-black font-mono tracking-tight text-rose-600 bg-rose-50 border border-rose-200 px-6 py-2 rounded-2xl shadow-inner";
            overlay.classList.remove('hidden');
            statusBadge.className = "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800";
            statusBadge.innerHTML = '<span class="w-2 h-2 mr-1.5 rounded-full bg-rose-500"></span> KEDALUWARSA';
            return;
        }

        timerElem.innerText = formatTime(remainingSeconds);
        overlay.classList.add('hidden');
        statusBadge.className = "inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800";
        statusBadge.innerHTML = '<span class="w-2 h-2 mr-1.5 rounded-full bg-emerald-500 animate-pulse"></span> AKTIF';
    }

    function startCountdown() {
        if (countdownInterval) clearInterval(countdownInterval);
        updateTimerDisplay();
        countdownInterval = setInterval(() => {
            if (remainingSeconds > 0) {
                remainingSeconds--;
                updateTimerDisplay();
            } else {
                clearInterval(countdownInterval);
                updateTimerDisplay();
            }
        }, 1000);
    }

    async function regenerateQrCode(showSuccess = true, autoRefresh = false) {
        const btn = document.getElementById('btnRegenerate');
        const icon = document.getElementById('iconRotate');
        btn.disabled = true;
        icon.classList.add('animate-spin');

        try {
            const response = await fetch("{{ route('dosen.pertemuan.regenerate_qr', $pertemuan->id) }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: JSON.stringify(autoRefresh ? { auto_refresh: true } : {})
            });

            const result = await response.json();
            if (response.ok && result.status === 'success') {
                currentToken = result.qr_token;
                remainingSeconds = result.remaining_seconds;
                document.getElementById('tokenPreview').innerText = currentToken;
                initQrCode(currentToken);
                startCountdown();
                if (showSuccess) {
                    Swal.fire({
                        icon: 'success',
                        title: 'QR Code Diperbarui',
                        text: 'Token baru aktif untuk 20 menit ke depan.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else if (autoRefresh && response.status === 410) {
                remainingSeconds = 0;
                updateTimerDisplay();
                stopProjectionRefresh();
                document.getElementById('projectionRefreshTimer').innerText = 'Sesi QR berakhir';
            } else {
                Swal.fire('Error', result.message || 'Gagal memperbarui QR code.', 'error');
            }
        } catch (error) {
            Swal.fire('Kesalahan', 'Gagal menghubungi server.', 'error');
        } finally {
            btn.disabled = false;
            icon.classList.remove('animate-spin');
        }
    }

    // Rumus Haversine di Javascript
    function haversineDistance(lat1, lon1, lat2, lon2) {
        const toRad = x => (x * Math.PI) / 180;
        const R = 6371000; // Radius Bumi dalam meter
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    // Deteksi Otomatis Lokasi GPS Dosen
    function trackLecturerGeolocation() {
        const statusBadge = document.getElementById('gpsDosenStatus');
        if (!navigator.geolocation) {
            statusBadge.className = "inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800";
            statusBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i> GPS Tidak Didukung';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                dosenCurrentLat = pos.coords.latitude;
                dosenCurrentLon = pos.coords.longitude;
                dosenCurrentAcc = pos.coords.accuracy;

                statusBadge.className = "inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800";
                statusBadge.innerHTML = '<i class="fa-solid fa-check mr-1"></i> GPS Terkunci';

                document.getElementById('dosenGpsCoords').innerText = `${dosenCurrentLat.toFixed(5)}, ${dosenCurrentLon.toFixed(5)}`;
                document.getElementById('dosenGpsAcc').innerText = `±${Math.round(dosenCurrentAcc)} meter`;

                updateDistanceEvaluation();
            },
            (err) => {
                statusBadge.className = "inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800";
                statusBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-1"></i> Izin Ditolak';
                document.getElementById('alertTitle').innerText = "Izin GPS Browser Ditolak";
                document.getElementById('alertDesc').innerText = "Harap izinkan akses lokasi (GPS) pada browser Anda agar sistem dapat mendeteksi koordinat Anda.";
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    function updateDistanceEvaluation() {
        if (dosenCurrentLat === null || dosenCurrentLon === null) return;

        const dist = haversineDistance(dosenCurrentLat, dosenCurrentLon, currentClassLat, currentClassLon);
        const distRounded = Math.round(dist);
        document.getElementById('dosenDistToClass').innerText = `${distRounded} meter`;

        const alertBox = document.getElementById('distanceAlertBox');
        const alertTitle = document.getElementById('alertTitle');
        const alertDesc = document.getElementById('alertDesc');

        if (dist > currentRadius) {
            alertBox.className = "p-3 rounded-xl text-xs space-y-1 bg-amber-50 border border-amber-300 text-amber-900";
            alertTitle.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1.5 text-amber-600"></i> Lokasi Belum Sesuai (Selisih: ${distRounded} meter)`;
            alertDesc.innerHTML = `Lokasi GPS Anda saat ini berbeda <strong>${distRounded} meter</strong> dari titik kelas. Mahasiswa di dekat Anda akan <strong>GAGAL ABSEN</strong> karena melebihi toleransi ${currentRadius}m. Silakan klik tombol <strong>"SINKRONKAN TITIK KELAS KE GPS SAYA"</strong> di bawah!`;

            // Jika selisihnya jauh (misal > 500m karena masih default Monas), tawarkan sinkronisasi otomatis
            if (dist > 500 && !window.promptedSync) {
                window.promptedSync = true;
                Swal.fire({
                    title: 'Sinkronkan Titik Kelas?',
                    html: `Koordinat kelas saat ini berada sejauh <b>${(dist / 1000).toFixed(1)} km</b> dari lokasi Anda.<br><br>Apakah Anda ingin menyinkronkan titik kelas ke lokasi Anda saat ini agar mahasiswa bisa presensi?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Sinkronkan Sekarang!',
                    cancelButtonText: 'Nanti Saja'
                }).then((res) => {
                    if (res.isConfirmed) {
                        syncClassLocationToMyGps(false);
                    }
                });
            }
        } else {
            alertBox.className = "p-3 rounded-xl text-xs space-y-1 bg-emerald-50 border border-emerald-300 text-emerald-900";
            alertTitle.innerHTML = `<i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i> Lokasi Kelas Sudah Sinkron!`;
            alertDesc.innerHTML = `Titik kelas sudah cocok dengan posisi GPS Anda (selisih hanya <strong>${distRounded} meter</strong>). Mahasiswa di ruangan Anda dapat melakukan presensi dengan lancar.`;
        }
    }

    // Fungsi Sinkronisasi Titik Kelas ke GPS Dosen
    async function syncClassLocationToMyGps(isManualClick = true) {
        const btn = document.getElementById('btnSyncGps');
        const icon = document.getElementById('iconSyncGps');

        if (!navigator.geolocation) {
            Swal.fire('GPS Tidak Didukung', 'Browser tidak mendukung Geolocation API.', 'error');
            return;
        }

        btn.disabled = true;
        icon.className = 'fa-solid fa-spinner fa-spin mr-2 text-sm';

        navigator.geolocation.getCurrentPosition(
            async (pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                dosenCurrentLat = lat;
                dosenCurrentLon = lon;
                dosenCurrentAcc = pos.coords.accuracy;

                try {
                    const response = await fetch("{{ route('dosen.jadwal.update_lokasi', $pertemuan->jadwal_kuliah_id) }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({
                            latitude: lat,
                            longitude: lon,
                            radius: currentRadius
                        })
                    });

                    const res = await response.json();
                    if (response.ok && res.status === 'success') {
                        currentClassLat = lat;
                        currentClassLon = lon;
                        document.getElementById('displayLat').innerText = lat.toFixed(7);
                        document.getElementById('displayLon').innerText = lon.toFixed(7);
                        document.getElementById('linkGmaps').href = `https://www.google.com/maps?q=${lat},${lon}`;
                        
                        updateDistanceEvaluation();

                        Swal.fire({
                            icon: 'success',
                            title: 'Titik Kelas Berhasil Diperbarui!',
                            html: `Koordinat kelas berhasil disinkronkan ke lokasi GPS Anda:<br><b>${lat.toFixed(6)}, ${lon.toFixed(6)}</b><br><br>Sekarang mahasiswa yang berada di dekat Anda dapat melakukan presensi!`,
                            confirmButtonColor: '#059669'
                        });
                    } else {
                        Swal.fire('Gagal', res.message || 'Gagal memperbarui lokasi.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Gagal menghubungi server.', 'error');
                } finally {
                    btn.disabled = false;
                    icon.className = 'fa-solid fa-location-dot mr-2 text-sm';
                }
            },
            (err) => {
                btn.disabled = false;
                icon.className = 'fa-solid fa-location-dot mr-2 text-sm';
                Swal.fire('Izin GPS Ditolak', 'Pastikan izin akses lokasi aktif pada browser Anda.', 'warning');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    // Fungsi Mengubah Toleransi Radius
    async function changeRadius(newRadius) {
        currentRadius = parseFloat(newRadius);
        try {
            const response = await fetch("{{ route('dosen.jadwal.update_lokasi', $pertemuan->jadwal_kuliah_id) }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    radius: currentRadius
                })
            });

            const res = await response.json();
            if (response.ok && res.status === 'success') {
                document.getElementById('currentRadiusBadge').innerText = `${currentRadius} Meter`;
                updateDistanceEvaluation();
                Swal.fire({
                    icon: 'success',
                    title: 'Radius Diperbarui',
                    text: `Toleransi radius mahasiswa diset ke ${currentRadius} meter.`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2500
                });
            } else {
                Swal.fire('Gagal', res.message || 'Gagal mengubah radius.', 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        }
    }

    async function pollLiveAttendance() {
        try {
            const response = await fetch("{{ route('dosen.pertemuan.live_attendance', $pertemuan->id) }}", {
                headers: { "Accept": "application/json" }
            });
            if (!response.ok) return;
            const res = await response.json();
            document.getElementById('badgeTotalHadir').innerText = res.total_hadir;
            document.getElementById('badgeTotalTidakHadir').innerText = res.total_tidak_hadir;

            const tbody = document.getElementById('attendanceTableBody');
            if (res.presensi.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 5;
                cell.className = 'py-12 text-center text-sm text-slate-400';
                cell.innerText = 'Belum ada mahasiswa yang melakukan presensi pada pertemuan ini.';
                row.appendChild(cell);
                tbody.replaceChildren(row);
            } else {
                const manualUrl = tbody.dataset.manualUrl;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const rows = res.presensi.map((item) => {
                    const row = document.createElement('tr');
                    row.className = 'transition hover:bg-blue-50/40';

                    const numberCell = document.createElement('td');
                    numberCell.className = 'px-4 py-3 text-center text-slate-500';
                    numberCell.innerText = item.no;

                    const nimCell = document.createElement('td');
                    nimCell.className = 'px-4 py-3 font-mono text-xs text-slate-600';
                    nimCell.innerText = item.nomor_induk;

                    const nameCell = document.createElement('td');
                    nameCell.className = 'px-4 py-3 font-semibold text-slate-900';
                    nameCell.innerText = item.nama;

                    const statusCell = document.createElement('td');
                    statusCell.className = 'px-4 py-3 text-center';
                    const badge = document.createElement('span');
                    badge.className = item.status === 'Hadir'
                        ? 'inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800'
                        : 'inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800';
                    badge.innerText = item.status;
                    statusCell.appendChild(badge);

                    const actionsCell = document.createElement('td');
                    actionsCell.className = 'px-4 py-3';
                    const actions = document.createElement('div');
                    actions.className = 'flex justify-center gap-2';

                    [
                        { status: 'Hadir', label: 'Hadir', color: 'bg-emerald-600 hover:bg-emerald-700', icon: 'fa-check' },
                        { status: 'Tidak Hadir', label: 'Tidak Hadir', color: 'bg-rose-600 hover:bg-rose-700', icon: 'fa-xmark' }
                    ].forEach(({ status, label, color, icon }) => {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = manualUrl.replace('__PRESENSI_ID__', encodeURIComponent(item.id));
                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        tokenInput.value = csrfToken;
                        const button = document.createElement('button');
                        button.type = 'submit';
                        button.name = 'status';
                        button.value = status;
                        button.className = `inline-flex min-h-9 items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-bold text-white transition ${color}`;
                        const buttonIcon = document.createElement('i');
                        buttonIcon.className = `fa-solid ${icon}`;
                        button.append(buttonIcon, document.createTextNode(label));
                        form.append(tokenInput, button);
                        actions.appendChild(form);
                    });

                    actionsCell.appendChild(actions);
                    row.append(numberCell, nimCell, nameCell, statusCell, actionsCell);
                    return row;
                });
                tbody.replaceChildren(...rows);
            }
            const now = new Date();
            document.getElementById('lastUpdatedTime').innerText = `Terakhir diperbarui: ${now.toLocaleTimeString()}`;
        } catch (err) {
            console.error('Polling error:', err);
        }
    }

    function copyToken() {
        navigator.clipboard.writeText(currentToken).then(() => {
            Swal.fire({
                icon: 'info',
                title: 'Token Tersalin',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initQrCode(currentToken);
        startCountdown();
        trackLecturerGeolocation();
        pollingInterval = setInterval(pollLiveAttendance, 4000);

        const courseFilter = document.getElementById('courseFilter');
        const classFilter = document.getElementById('classFilter');
        const classOptions = Array.from(classFilter.options);

        function filterClasses(navigateToSelectedClass) {
            const availableOptions = classOptions.filter((option) => option.dataset.courseId === courseFilter.value);
            const selectableOptions = availableOptions.filter((option) => !option.disabled);
            classOptions.forEach((option) => {
                option.hidden = option.dataset.courseId !== courseFilter.value;
            });

            if (!selectableOptions.some((option) => option.value === classFilter.value)) {
                classFilter.value = selectableOptions[0]?.value ?? '';
            }

            if (navigateToSelectedClass) {
                const selectedUrl = classFilter.selectedOptions[0]?.dataset.url;
                if (selectedUrl) {
                    window.location.assign(selectedUrl);
                }
            }
        }

        filterClasses(false);
        courseFilter.addEventListener('change', () => filterClasses(true));
        classFilter.addEventListener('change', () => filterClasses(true));

        const projectionModal = document.getElementById('qrProjectionModal');
        projectionModal.addEventListener('close', stopProjectionRefresh);
        projectionModal.addEventListener('click', (event) => {
            if (event.target === projectionModal) {
                closeQrProjection();
            }
        });
    });
</script>
@endpush
@endif