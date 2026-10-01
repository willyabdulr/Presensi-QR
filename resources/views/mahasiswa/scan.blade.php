@extends('layouts.app')

@section('title', 'Scan QR Presensi Mahasiswa')

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <div class="mb-5 border-b border-slate-200 pb-4">
        <h1 class="text-2xl font-bold text-[#17385f]">Scan Absensi</h1>
        <p class="mt-1 text-sm text-slate-500">Verifikasi kehadiran melalui QR code dan geolokasi.</p>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-4 transition-all sm:p-5" id="gpsCard">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div id="gpsIconContainer" class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-location-crosshairs animate-pulse" id="gpsIcon"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Status Geolokasi Mahasiswa</h3>
                    <p class="text-xs text-slate-500" id="gpsStatusText">Mendeteksi koordinat GPS perangkat Anda...</p>
                </div>
            </div>
            <button onclick="requestGeolocation()" class="px-3 py-1.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition" id="btnRetryGps">
                <i class="fa-solid fa-arrows-rotate mr-1"></i> Refresh GPS
            </button>
        </div>

        <div id="gpsDetail" class="hidden mt-4 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs">
            <div class="bg-slate-50 p-2 rounded-lg">
                <span class="text-slate-400 block">Latitude:</span>
                <span id="latDisplay" class="font-mono font-bold text-slate-700">-</span>
            </div>
            <div class="bg-slate-50 p-2 rounded-lg">
                <span class="text-slate-400 block">Longitude:</span>
                <span id="lonDisplay" class="font-mono font-bold text-slate-700">-</span>
            </div>
            <div class="col-span-2 text-[11px] text-emerald-600 flex items-center mt-1">
                <i class="fa-solid fa-circle-check mr-1.5"></i>
                <span>Akurasi GPS: <span id="accDisplay" class="font-semibold">-</span> meter</span>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-md border border-slate-200 bg-white">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800 flex items-center">
                <i class="fa-solid fa-camera mr-2 text-blue-600"></i> Kamera Scanner QR
            </h2>
            <span id="scannerStatusBadge" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                Menunggu GPS...
            </span>
        </div>

        <div class="p-4 flex flex-col items-center">
            <div id="scannerWrapper" class="relative flex min-h-[300px] w-full max-w-sm items-center justify-center overflow-hidden rounded-md border-2 border-slate-200 bg-slate-900">
                <div id="reader" class="w-full"></div>

                <div id="scannerOverlayWaitingGps" class="absolute inset-0 bg-slate-900/90 text-white flex flex-col items-center justify-center p-6 text-center z-10">
                    <i class="fa-solid fa-location-dot text-4xl text-amber-400 mb-3 animate-bounce"></i>
                    <p class="font-bold text-sm mb-1">Menunggu Izin Lokasi (GPS)</p>
                    <p class="text-xs text-slate-300 mb-4">Mohon izinkan akses lokasi (GPS) pada browser Anda.</p>
                    <button onclick="requestGeolocation()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow">
                        Aktifkan GPS Sekarang
                    </button>
                </div>

                <div id="scannerOverlayProcessing" class="hidden absolute inset-0 bg-slate-900/85 text-white flex flex-col items-center justify-center p-6 text-center z-20">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-indigo-400 mb-3"></i>
                    <p class="font-bold text-sm">Memvalidasi Presensi...</p>
                    <p class="text-xs text-slate-300">Menghitung jarak Haversine ke titik kelas</p>
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-xs text-slate-500">Arahkan kamera ke layar QR Code dinamis yang ditampilkan oleh dosen di kelas.</p>
            </div>
        </div>
    </div>

    <div class="rounded-md border border-slate-200 bg-white p-4 text-xs">
        <details class="cursor-pointer group">
            <summary class="font-bold text-slate-700 hover:text-indigo-600 flex items-center justify-between">
                <span><i class="fa-solid fa-keyboard mr-1.5 text-slate-400"></i> Kamera bermasalah? Masukkan Token Manual</span>
                <i class="fa-solid fa-chevron-down text-slate-400 group-open:rotate-180 transition-transform"></i>
            </summary>
            <div class="mt-3 pt-3 border-t border-slate-100">
                <form id="manualTokenForm" onsubmit="handleManualSubmit(event)" class="space-y-2">
                    <input type="text" id="manualTokenInput" placeholder="Masukkan 40 karakter token..." class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold transition">
                        Kirim Presensi dengan Token
                    </button>
                </form>
            </div>
        </details>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let userLat = null;
    let userLon = null;
    let userAcc = null;
    let isProcessing = false;
    let html5QrCode = null;

    function requestGeolocation() {
        const gpsStatusText = document.getElementById('gpsStatusText');
        const gpsIconContainer = document.getElementById('gpsIconContainer');
        const gpsIcon = document.getElementById('gpsIcon');

        gpsStatusText.innerText = "Mengakses GPS perangkat...";
        if (!navigator.geolocation) {
            gpsStatusText.innerText = "Browser tidak mendukung HTML5 Geolocation.";
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLat = position.coords.latitude;
                userLon = position.coords.longitude;
                userAcc = position.coords.accuracy;

                document.getElementById('latDisplay').innerText = userLat.toFixed(6);
                document.getElementById('lonDisplay').innerText = userLon.toFixed(6);
                document.getElementById('accDisplay').innerText = Math.round(userAcc);
                document.getElementById('gpsDetail').classList.remove('hidden');

                gpsStatusText.innerText = "Lokasi GPS berhasil terkunci.";
                gpsIconContainer.className = "w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg";
                gpsIcon.className = "fa-solid fa-location-dot";

                document.getElementById('scannerOverlayWaitingGps').classList.add('hidden');
                document.getElementById('scannerStatusBadge').className = "text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700";
                document.getElementById('scannerStatusBadge').innerText = "Kamera Siap Scan";

                if (!html5QrCode) startScanner();
            },
            (error) => {
                gpsStatusText.innerText = "Izin lokasi GPS ditolak/tidak tersedia.";
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    function startScanner() {
        html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 220, height: 220 } },
            (decodedText) => onScanSuccess(decodedText),
            () => {}
        ).catch(() => {
            document.getElementById('scannerStatusBadge').innerText = "Kamera Tidak Terdeteksi";
        });
    }

    async function onScanSuccess(qrToken) {
        if (isProcessing) return;
        isProcessing = true;
        document.getElementById('scannerOverlayProcessing').classList.remove('hidden');

        if (userLat === null || userLon === null) {
            isProcessing = false;
            document.getElementById('scannerOverlayProcessing').classList.add('hidden');
            Swal.fire('GPS Belum Siap', 'Aktifkan GPS terlebih dahulu.', 'warning');
            return;
        }

        await submitPresensi(qrToken);
    }

    async function handleManualSubmit(e) {
        e.preventDefault();
        const token = document.getElementById('manualTokenInput').value.trim();
        if (!token) return;

        if (userLat === null || userLon === null) {
            Swal.fire('GPS Belum Siap', 'Aktifkan GPS terlebih dahulu.', 'warning');
            return;
        }

        document.getElementById('scannerOverlayProcessing').classList.remove('hidden');
        await submitPresensi(token);
    }

    async function submitPresensi(token) {
        try {
            const response = await fetch("{{ route('mahasiswa.presensi.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    qr_token: token,
                    latitude: userLat,
                    longitude: userLon
                })
            });

            const result = await response.json();
            document.getElementById('scannerOverlayProcessing').classList.add('hidden');

            if (response.status === 200) {
                Swal.fire({
                    icon: 'success',
                    title: 'Presensi Berhasil!',
                    html: `
                        <div class="text-left text-sm space-y-1 bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <div><strong>Mata Kuliah:</strong> ${result.data.mata_kuliah}</div>
                            <div><strong>Pertemuan:</strong> Ke-${result.data.pertemuan_ke}</div>
                            <div><strong>Waktu:</strong> ${result.data.waktu_presensi} WIB</div>
                            <div><strong>Jarak:</strong> <span class="text-emerald-600 font-bold">${result.data.jarak_meter} m</span></div>
                        </div>
                    `,
                    confirmButtonText: 'Lihat Riwayat'
                }).then(() => {
                    window.location.href = "{{ route('mahasiswa.riwayat') }}";
                });
            } else {
                Swal.fire({
                    icon: response.status === 422 ? 'error' : 'warning',
                    title: 'Presensi Ditolak',
                    text: result.message || 'Terjadi kesalahan.',
                }).then(() => {
                    isProcessing = false;
                });
            }
        } catch (error) {
            document.getElementById('scannerOverlayProcessing').classList.add('hidden');
            Swal.fire('Kesalahan', 'Gagal menghubungi server.', 'error').then(() => {
                isProcessing = false;
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        requestGeolocation();
    });
</script>
@endpush
