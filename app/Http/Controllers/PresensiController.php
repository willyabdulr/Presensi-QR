<?php

namespace App\Http\Controllers;

use App\Models\Pertemuan;
use App\Models\Presensi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PresensiController extends Controller
{
    public function scanPage()
    {
        return view('mahasiswa.scan');
    }

    public function dashboard(): View
    {
        $mahasiswaId = auth('mahasiswa')->id();
        $weekStart = now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $attendanceQuery = Presensi::query()
            ->where('mahasiswa_id', $mahasiswaId)
            ->where('status', 'Hadir');

        $stats = [
            'total' => (clone $attendanceQuery)->count(),
            'minggu_ini' => (clone $attendanceQuery)->whereBetween('waktu_presensi', [$weekStart, $weekEnd])->count(),
            'mata_kuliah' => DB::table('presensi')
                ->join('pertemuan', 'presensi.pertemuan_id', '=', 'pertemuan.id')
                ->join('jadwal_kuliah', 'pertemuan.jadwal_kuliah_id', '=', 'jadwal_kuliah.id')
                ->where('presensi.mahasiswa_id', $mahasiswaId)
                ->where('presensi.status', 'Hadir')
                ->distinct()
                ->count('jadwal_kuliah.mata_kuliah_id'),
        ];

        $recentPresensi = Presensi::query()
            ->with(['pertemuan.jadwalKuliah.mataKuliah', 'pertemuan.jadwalKuliah.dosen'])
            ->where('mahasiswa_id', $mahasiswaId)
            ->latest('waktu_presensi')
            ->limit(6)
            ->get();

        return view('mahasiswa.dashboard', compact('stats', 'recentPresensi'));
    }

    public function riwayat()
    {
        $mahasiswaId = auth()->id();
        $riwayatPresensi = Presensi::with(['pertemuan.jadwalKuliah.mataKuliah', 'pertemuan.jadwalKuliah.dosen'])
            ->where('mahasiswa_id', $mahasiswaId)
            ->orderBy('waktu_presensi', 'desc')
            ->paginate(15);

        return view('mahasiswa.riwayat', compact('riwayatPresensi'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'qr_token' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $mahasiswaId = auth()->id();

        $pertemuan = Pertemuan::with('jadwalKuliah.mataKuliah')
            ->where('qr_token', $request->qr_token)
            ->first();

        if (! $pertemuan) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR Code tidak valid atau tidak dikenali oleh sistem.',
            ], 404);
        }

        if (! $pertemuan->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi presensi untuk pertemuan ini telah ditutup.',
            ], 400);
        }

        if ($pertemuan->isExpired()) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR Code telah kedaluwarsa (batas waktu 20 menit berakhir). Silakan minta dosen untuk memperbarui QR Code.',
            ], 410);
        }

        $existingPresensi = Presensi::where('pertemuan_id', $pertemuan->id)
            ->where('mahasiswa_id', $mahasiswaId)
            ->first();

        if ($existingPresensi) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Anda sudah melakukan presensi pada pertemuan ini pada '.
                    $existingPresensi->waktu_presensi->format('H:i:s').' WIB.',
            ], 409);
        }

        $jadwal = $pertemuan->jadwalKuliah;
        $jarakMeter = $this->calculateHaversineDistance(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $jadwal->latitude_kelas,
            (float) $jadwal->longitude_kelas
        );

        $radiusMaksimal = (float) $jadwal->radius_meter;

        if ($jarakMeter > $radiusMaksimal) {
            return response()->json([
                'status' => 'error',
                'message' => sprintf(
                    'Presensi ditolak! Posisi Anda berada di luar radius kelas. Jarak Anda: %0.1f meter (Batas radius izin: %0.1f meter).',
                    $jarakMeter,
                    $radiusMaksimal
                ),
            ], 422);
        }

        $presensi = Presensi::create([
            'pertemuan_id' => $pertemuan->id,
            'mahasiswa_id' => $mahasiswaId,
            'status' => 'Hadir',
            'waktu_presensi' => Carbon::now(),
            'latitude_mahasiswa' => $request->latitude,
            'longitude_mahasiswa' => $request->longitude,
            'jarak_meter' => round($jarakMeter, 2),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Presensi berhasil dicatat! Status: Hadir.',
            'data' => [
                'mata_kuliah' => $jadwal->mataKuliah->nama_mk ?? 'Mata Kuliah',
                'pertemuan_ke' => $pertemuan->pertemuan_ke,
                'waktu_presensi' => $presensi->waktu_presensi->format('H:i:s'),
                'jarak_meter' => round($jarakMeter, 1),
            ],
        ], 200);
    }

    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
