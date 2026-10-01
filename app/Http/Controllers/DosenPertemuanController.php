<?php

namespace App\Http\Controllers;

use App\Models\JadwalKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DosenPertemuanController extends Controller
{
    public function index(): View
    {
        $dosenId = auth()->id();
        $jadwalList = JadwalKuliah::with(['mataKuliah', 'pertemuan.presensi'])
            ->where('dosen_id', $dosenId)
            ->get();

        $stats = [
            'kelas' => $jadwalList->count(),
            'pertemuan' => $jadwalList->sum(fn (JadwalKuliah $jadwal): int => $jadwal->pertemuan->count()),
            'hadir' => $jadwalList->sum(fn (JadwalKuliah $jadwal): int => $jadwal->pertemuan
                ->sum(fn (Pertemuan $pertemuan): int => $pertemuan->presensi->where('status', 'Hadir')->count())),
        ];

        return view('dosen.dashboard', compact('jadwalList', 'stats'));
    }

    public function rekapAbsensi(): View
    {
        $jadwalList = JadwalKuliah::query()
            ->where('dosen_id', auth('dosen')->id())
            ->with([
                'mataKuliah',
                'pertemuan' => fn ($query) => $query->orderBy('pertemuan_ke'),
                'pertemuan.presensi.mahasiswa',
            ])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        /** @var Collection<int, array{jadwal: JadwalKuliah, pertemuan: Collection<int, Pertemuan>, rows: Collection<int, array{mahasiswa: User, statuses: array<int, string|null>, total_hadir: int, persentase: float}>}> $reports */
        $reports = $jadwalList->map(function (JadwalKuliah $jadwal): array {
            $pertemuanList = $jadwal->pertemuan;
            $mahasiswaList = $pertemuanList
                ->flatMap(fn (Pertemuan $pertemuan): Collection => $pertemuan->presensi->pluck('mahasiswa'))
                ->filter()
                ->unique('id')
                ->sortBy('nomor_induk')
                ->values();

            $rows = $mahasiswaList->map(function (User $mahasiswa) use ($pertemuanList): array {
                $statuses = [];
                $totalHadir = 0;

                foreach ($pertemuanList as $pertemuan) {
                    $presensi = $pertemuan->presensi->firstWhere('mahasiswa_id', $mahasiswa->id);
                    $statuses[$pertemuan->id] = $presensi?->status;

                    if ($presensi?->status === 'Hadir') {
                        $totalHadir++;
                    }
                }

                return [
                    'mahasiswa' => $mahasiswa,
                    'statuses' => $statuses,
                    'total_hadir' => $totalHadir,
                    'persentase' => $pertemuanList->isNotEmpty()
                        ? round(($totalHadir / $pertemuanList->count()) * 100, 1)
                        : 0.0,
                ];
            });

            return [
                'jadwal' => $jadwal,
                'pertemuan' => $pertemuanList,
                'rows' => $rows,
            ];
        });

        $stats = [
            'kelas' => $reports->count(),
            'pertemuan' => $reports->sum(fn (array $report): int => $report['pertemuan']->count()),
            'hadir' => $reports->sum(fn (array $report): int => $report['rows']->sum(
                fn (array $row): int => $row['total_hadir']
            )),
        ];

        return view('dosen.attendance', compact('reports', 'stats'));
    }

    public function showQr(Pertemuan $pertemuan)
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah);

        if (empty($pertemuan->qr_token) || empty($pertemuan->qr_expires_at)) {
            $pertemuan->update([
                'qr_token' => Str::random(40),
                'qr_expires_at' => Carbon::now()->addMinutes(20),
                'is_active' => true,
            ]);
        }

        $pertemuan->load(['jadwalKuliah.mataKuliah', 'jadwalKuliah.dosen', 'presensi.mahasiswa']);

        return view('dosen.show_qr', compact('pertemuan'));
    }

    public function regenerateQr(Request $request, Pertemuan $pertemuan): JsonResponse
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah);

        $newToken = Str::random(40);
        $newExpiresAt = Carbon::now()->addMinutes(20);

        $pertemuan->update([
            'qr_token' => $newToken,
            'qr_expires_at' => $newExpiresAt,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'QR Code berhasil diperbarui dengan validitas 20 menit.',
            'qr_token' => $newToken,
            'qr_expires_at' => $newExpiresAt->toIso8601String(),
            'remaining_seconds' => 1200,
        ]);
    }

    public function liveAttendance(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah);

        $presensiList = $pertemuan->presensi()
            ->with('mahasiswa')
            ->orderBy('waktu_presensi', 'desc')
            ->get();

        $data = $presensiList->map(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->mahasiswa->name ?? 'Mahasiswa',
                'nomor_induk' => $item->mahasiswa->nomor_induk ?? '-',
                'status' => $item->status,
                'waktu_presensi' => $item->waktu_presensi ? $item->waktu_presensi->format('H:i:s') : '-',
                'latitude' => $item->latitude_mahasiswa,
                'longitude' => $item->longitude_mahasiswa,
                'jarak_meter' => $item->jarak_meter !== null ? round($item->jarak_meter, 1).' m' : '-',
            ];
        });

        return response()->json([
            'is_expired' => $pertemuan->isExpired(),
            'remaining_seconds' => $pertemuan->remainingSeconds(),
            'qr_token' => $pertemuan->qr_token,
            'total_hadir' => $presensiList->where('status', 'Hadir')->count(),
            'presensi' => $data,
        ]);
    }

    public function exportRekap(JadwalKuliah $jadwalKuliah): StreamedResponse
    {
        $this->authorizeDosen($jadwalKuliah);

        $jadwalKuliah->load(['mataKuliah', 'pertemuan' => function ($q) {
            $q->orderBy('pertemuan_ke', 'asc');
        }, 'pertemuan.presensi.mahasiswa']);

        $pertemuanList = $jadwalKuliah->pertemuan;
        $totalPertemuan = $pertemuanList->count();

        $mahasiswaIds = Presensi::whereIn('pertemuan_id', $pertemuanList->pluck('id'))
            ->pluck('mahasiswa_id')
            ->unique();

        $mahasiswaList = User::whereIn('id', $mahasiswaIds)->orderBy('nomor_induk')->get();

        $fileName = sprintf(
            'Rekap_Presensi_%s_%s.csv',
            str_replace(' ', '_', $jadwalKuliah->mataKuliah->nama_mk ?? 'MK'),
            date('Ymd_His')
        );

        return response()->streamDownload(function () use ($jadwalKuliah, $pertemuanList, $mahasiswaList, $totalPertemuan) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['REKAP PRESENSI KULIAH']);
            fputcsv($output, ['Mata Kuliah', $jadwalKuliah->mataKuliah->nama_mk ?? '-']);
            fputcsv($output, ['Kode MK', $jadwalKuliah->mataKuliah->kode_mk ?? '-']);
            fputcsv($output, ['Dosen Pengampu', auth()->user()->name]);
            fputcsv($output, ['Jadwal', $jadwalKuliah->hari.', '.$jadwalKuliah->jam_mulai.' - '.$jadwalKuliah->jam_selesai]);
            fputcsv($output, []);

            $headerColumns = ['No', 'NIM / Nomor Induk', 'Nama Mahasiswa'];
            foreach ($pertemuanList as $p) {
                $headerColumns[] = 'P-'.$p->pertemuan_ke;
            }
            $headerColumns[] = 'Total Hadir';
            $headerColumns[] = 'Persentase (%)';
            fputcsv($output, $headerColumns);

            $no = 1;
            foreach ($mahasiswaList as $mhs) {
                $row = [$no++, $mhs->nomor_induk ?? '-', $mhs->name];
                $hadirCount = 0;
                foreach ($pertemuanList as $p) {
                    $presensi = $p->presensi->firstWhere('mahasiswa_id', $mhs->id);
                    if ($presensi && $presensi->status === 'Hadir') {
                        $row[] = 'H';
                        $hadirCount++;
                    } else {
                        $row[] = 'A';
                    }
                }

                $persentase = $totalPertemuan > 0 ? round(($hadirCount / $totalPertemuan) * 100, 1).'%' : '0%';
                $row[] = $hadirCount;
                $row[] = $persentase;
                fputcsv($output, $row);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function storePertemuan(Request $request, JadwalKuliah $jadwalKuliah)
    {
        $this->authorizeDosen($jadwalKuliah);

        $request->validate([
            'pertemuan_ke' => 'required|integer|min:1',
        ]);

        $pertemuan = Pertemuan::create([
            'jadwal_kuliah_id' => $jadwalKuliah->id,
            'pertemuan_ke' => $request->pertemuan_ke,
            'qr_token' => Str::random(40),
            'qr_expires_at' => Carbon::now()->addMinutes(20),
            'is_active' => true,
        ]);

        return redirect()->route('dosen.pertemuan.qr', $pertemuan->id)
            ->with('success', "Pertemuan ke-{$pertemuan->pertemuan_ke} berhasil dibuat.");
    }

    public function updateLokasi(Request $request, JadwalKuliah $jadwalKuliah): JsonResponse
    {
        $this->authorizeDosen($jadwalKuliah);

        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1',
        ]);

        $jadwalKuliah->update([
            'latitude_kelas' => $request->latitude ?? $jadwalKuliah->latitude_kelas,
            'longitude_kelas' => $request->longitude ?? $jadwalKuliah->longitude_kelas,
            'radius_meter' => $request->radius ?? $jadwalKuliah->radius_meter,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Titik koordinat kelas berhasil disinkronkan ke lokasi GPS saat ini!',
            'latitude' => $jadwalKuliah->latitude_kelas,
            'longitude' => $jadwalKuliah->longitude_kelas,
            'radius' => $jadwalKuliah->radius_meter,
        ]);
    }

    private function authorizeDosen(JadwalKuliah $jadwal): void
    {
        if ($jadwal->dosen_id !== auth()->id()) {
            abort(403, 'Anda tidak berwenang mengelola kelas/pertemuan ini.');
        }
    }
}
