<?php

namespace App\Http\Controllers;

use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DosenPertemuanController extends Controller
{
    public function index(Request $request): View
    {
        $dosenId = auth()->id();
        $jadwalList = JadwalKuliah::query()
            ->with(['mataKuliah', 'dosen', 'kelasData.mahasiswa', 'pertemuan.presensi', 'pertemuan.dosenPengganti'])
            ->where(function ($query) use ($dosenId): void {
                $query->where('dosen_id', $dosenId)
                    ->orWhereHas('pertemuan', fn ($meetingQuery) => $meetingQuery->where('dosen_pengganti_id', $dosenId));
            })
            ->orderBy('mata_kuliah_id')
            ->orderBy('kelas')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
        $jadwalList->each(function (JadwalKuliah $jadwal) use ($dosenId): void {
            if ($jadwal->dosen_id !== $dosenId) {
                $jadwal->setRelation(
                    'pertemuan',
                    $jadwal->pertemuan->where('dosen_pengganti_id', $dosenId)->values()
                );
            }
        });

        $stats = [
            'kelas' => $jadwalList->count(),
            'pertemuan' => $jadwalList->sum(fn (JadwalKuliah $jadwal): int => $jadwal->pertemuan->count()),
            'hadir' => $jadwalList->sum(fn (JadwalKuliah $jadwal): int => $jadwal->pertemuan
                ->sum(fn (Pertemuan $pertemuan): int => $pertemuan->presensi->where('status', 'Hadir')->count())),
        ];

        $mataKuliahList = $jadwalList->pluck('mataKuliah')->unique('id')->values();
        $selectedMataKuliah = $mataKuliahList->firstWhere('id', $request->integer('mata_kuliah'))
            ?? $mataKuliahList->first();
        $kelasList = $jadwalList
            ->where('mata_kuliah_id', $selectedMataKuliah?->id)
            ->values();
        $selectedJadwal = $kelasList->firstWhere('id', $request->integer('jadwal'))
            ?? $kelasList->first();

        return view('dosen.dashboard', compact(
            'jadwalList',
            'mataKuliahList',
            'selectedMataKuliah',
            'kelasList',
            'selectedJadwal',
            'stats'
        ));
    }

    public function rekapAbsensi(): View
    {
        $jadwalList = JadwalKuliah::query()
            ->where('dosen_id', auth('dosen')->id())
            ->with([
                'mataKuliah',
                'kelasData.mahasiswa',
                'pertemuan' => fn ($query) => $query->orderBy('pertemuan_ke'),
                'pertemuan.presensi.mahasiswa',
            ])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        /** @var Collection<int, array{jadwal: JadwalKuliah, pertemuan: Collection<int, Pertemuan>, rows: Collection<int, array{mahasiswa: User, statuses: array<int, string|null>, total_hadir: int, persentase: float}>}> $reports */
        $reports = $jadwalList->map(function (JadwalKuliah $jadwal): array {
            $pertemuanList = $jadwal->pertemuan;
            $mahasiswaList = $jadwal->kelasData?->mahasiswa ?? collect();
            $mahasiswaList = $mahasiswaList
                ->concat($pertemuanList
                    ->flatMap(fn (Pertemuan $pertemuan): Collection => $pertemuan->presensi->pluck('mahasiswa'))
                    ->filter())
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

        $mataKuliahList = $reports
            ->map(fn (array $report) => $report['jadwal']->mataKuliah)
            ->unique('id')
            ->values();
        $selectedMataKuliah = $mataKuliahList->firstWhere('id', request()->integer('mata_kuliah'))
            ?? $mataKuliahList->first();
        $kelasReports = $reports
            ->filter(fn (array $report): bool => $report['jadwal']->mata_kuliah_id === $selectedMataKuliah?->id)
            ->values();
        $selectedReport = $kelasReports->first(
            fn (array $report): bool => $report['jadwal']->id === request()->integer('jadwal')
        ) ?? $kelasReports->first();

        return view('dosen.attendance', compact(
            'reports',
            'mataKuliahList',
            'selectedMataKuliah',
            'kelasReports',
            'selectedReport',
            'stats'
        ));
    }

    public function showQr(Pertemuan $pertemuan): View
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah, $pertemuan);

        abort_unless($pertemuan->status_pertemuan !== 'Terjadwal', 409, 'Mulai pertemuan terlebih dahulu untuk membuka presensi.');

        if ($pertemuan->status_pertemuan === 'Berlangsung'
            && (empty($pertemuan->qr_token) || empty($pertemuan->qr_expires_at) || $pertemuan->isExpired() || ! $pertemuan->is_active)) {
            $pertemuan->update([
                'qr_token' => Str::random(40),
                'qr_expires_at' => Carbon::now()->addMinutes(20),
                'is_active' => true,
            ]);
        }

        if ($pertemuan->status_pertemuan === 'Berlangsung') {
            $this->ensureClassRosterAttendance($pertemuan);
        }
        $pertemuan->load([
            'jadwalKuliah.mataKuliah',
            'jadwalKuliah.dosen',
            'jadwalKuliah.pertemuan' => fn ($query) => $query->orderByDesc('pertemuan_ke'),
            'presensi.mahasiswa',
            'dosenPengganti',
        ]);
        if ($pertemuan->jadwalKuliah->dosen_id !== auth('dosen')->id()) {
            $pertemuan->jadwalKuliah->setRelation(
                'pertemuan',
                $pertemuan->jadwalKuliah->pertemuan
                    ->where('dosen_pengganti_id', auth('dosen')->id())
                    ->values()
            );
        }
        $jadwalList = JadwalKuliah::query()
            ->where(function ($query): void {
                $query->where('dosen_id', auth('dosen')->id())
                    ->orWhereHas('pertemuan', fn ($meetingQuery) => $meetingQuery
                        ->where('dosen_pengganti_id', auth('dosen')->id()));
            })
            ->with([
                'mataKuliah',
                'pertemuan' => fn ($query) => $query->orderByDesc('pertemuan_ke'),
            ])
            ->get();
        $jadwalList->each(function (JadwalKuliah $jadwal): void {
            if ($jadwal->dosen_id !== auth('dosen')->id()) {
                $jadwal->setRelation(
                    'pertemuan',
                    $jadwal->pertemuan
                        ->where('dosen_pengganti_id', auth('dosen')->id())
                        ->values()
                );
            }
        });

        return view('dosen.show_qr', compact('pertemuan', 'jadwalList'));
    }

    public function startMeeting(Pertemuan $pertemuan): RedirectResponse
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah, $pertemuan);

        DB::transaction(function () use ($pertemuan): void {
            $meeting = Pertemuan::query()->lockForUpdate()->findOrFail($pertemuan->id);
            abort_unless($meeting->status_pertemuan === 'Terjadwal', 409, 'Pertemuan ini tidak berstatus Terjadwal.');

            $meeting->update([
                'status_pertemuan' => 'Berlangsung',
                'qr_token' => Str::random(40),
                'qr_expires_at' => Carbon::now()->addMinutes(20),
                'is_active' => true,
            ]);
        });

        $this->ensureClassRosterAttendance($pertemuan->fresh());

        return redirect()->route('dosen.pertemuan.qr', $pertemuan)
            ->with('success', 'Pertemuan dimulai dan QR presensi telah dibuat.');
    }

    public function endMeeting(Pertemuan $pertemuan): RedirectResponse
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah, $pertemuan);

        DB::transaction(function () use ($pertemuan): void {
            $meeting = Pertemuan::query()->lockForUpdate()->findOrFail($pertemuan->id);
            abort_unless($meeting->status_pertemuan === 'Berlangsung', 409, 'Hanya pertemuan yang sedang berlangsung yang dapat diakhiri.');

            $meeting->update([
                'status_pertemuan' => 'Selesai',
                'is_active' => false,
                'qr_expires_at' => now(),
            ]);
        });

        return redirect()->route('dosen.pertemuan.qr', $pertemuan)
            ->with('success', 'Pertemuan selesai. Presensi telah ditutup.');
    }

    public function regenerateQr(Request $request, Pertemuan $pertemuan): JsonResponse
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah, $pertemuan);
        if ($pertemuan->status_pertemuan !== 'Berlangsung') {
            return response()->json([
                'status' => 'error',
                'message' => 'QR Code hanya dapat dibuat saat pertemuan berlangsung.',
            ], 409);
        }

        $request->validate([
            'auto_refresh' => ['sometimes', 'boolean'],
        ]);

        $isAutoRefresh = $request->boolean('auto_refresh');
        $expiresAt = $pertemuan->qr_expires_at;

        if ($isAutoRefresh && (! $expiresAt || $expiresAt->isPast())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi QR telah berakhir. Buat QR baru untuk memulai sesi berikutnya.',
                'remaining_seconds' => 0,
            ], 410);
        }

        $newToken = Str::random(40);
        $newExpiresAt = $isAutoRefresh ? $expiresAt : Carbon::now()->addMinutes(20);

        $pertemuan->update([
            'qr_token' => $newToken,
            'qr_expires_at' => $newExpiresAt,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $isAutoRefresh
                ? 'QR Code diperbarui tanpa memperpanjang batas waktu sesi.'
                : 'QR Code berhasil diperbarui dengan validitas 20 menit.',
            'qr_token' => $newToken,
            'qr_expires_at' => $newExpiresAt->toIso8601String(),
            'remaining_seconds' => $pertemuan->remainingSeconds(),
        ]);
    }

    public function liveAttendance(Pertemuan $pertemuan): JsonResponse
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah, $pertemuan);
        $this->ensureClassRosterAttendance($pertemuan);

        $presensiList = $pertemuan->presensi()
            ->with('mahasiswa')
            ->get();
        $presensiList = $presensiList
            ->sortBy(fn (Presensi $presensi): string => $presensi->mahasiswa?->nomor_induk ?? '')
            ->values();

        $data = $presensiList->map(function (Presensi $item, int $index): array {
            return [
                'id' => $item->id,
                'no' => $index + 1,
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
            'total_tidak_hadir' => $presensiList->where('status', 'Tidak Hadir')->count(),
            'presensi' => $data,
        ]);
    }

    public function setManualAttendance(Request $request, Pertemuan $pertemuan, Presensi $presensi): RedirectResponse
    {
        $this->authorizeDosen($pertemuan->jadwalKuliah, $pertemuan);
        abort_unless($pertemuan->status_pertemuan === 'Berlangsung', 409, 'Absensi manual hanya tersedia saat pertemuan berlangsung.');

        abort_unless($presensi->pertemuan_id === $pertemuan->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Hadir', 'Tidak Hadir'])],
        ]);

        $presensi->update([
            'status' => $validated['status'],
            'waktu_presensi' => $validated['status'] === 'Hadir' ? now() : null,
            'latitude_mahasiswa' => $validated['status'] === 'Hadir' ? $presensi->latitude_mahasiswa : null,
            'longitude_mahasiswa' => $validated['status'] === 'Hadir' ? $presensi->longitude_mahasiswa : null,
            'jarak_meter' => $validated['status'] === 'Hadir' ? $presensi->jarak_meter : null,
        ]);

        return back()->with('success', 'Status presensi mahasiswa berhasil diperbarui.');
    }

    public function exportRekap(JadwalKuliah $jadwalKuliah): StreamedResponse
    {
        $this->authorizeDosen($jadwalKuliah);

        $jadwalKuliah->load(['mataKuliah', 'kelasData.mahasiswa', 'pertemuan' => function ($q) {
            $q->orderBy('pertemuan_ke', 'asc');
        }, 'pertemuan.presensi.mahasiswa']);

        $pertemuanList = $jadwalKuliah->pertemuan;
        $totalPertemuan = $pertemuanList->count();

        $mahasiswaIds = Presensi::whereIn('pertemuan_id', $pertemuanList->pluck('id'))
            ->pluck('mahasiswa_id')
            ->unique();

        $mahasiswaList = User::query()
            ->whereIn('id', $mahasiswaIds)
            ->when($jadwalKuliah->kelas_id, fn ($query) => $query->orWhere('kelas_id', $jadwalKuliah->kelas_id))
            ->orderBy('nomor_induk')
            ->get();

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

        $validated = $request->validate([
            'pertemuan_ke' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('pertemuan', 'pertemuan_ke')->where('jadwal_kuliah_id', $jadwalKuliah->id),
            ],
            'topik' => ['nullable', 'string', 'max:255'],
            'tanggal_pertemuan' => ['nullable', 'date'],
        ]);

        $pertemuan = Pertemuan::create([
            'jadwal_kuliah_id' => $jadwalKuliah->id,
            'pertemuan_ke' => $validated['pertemuan_ke'],
            'topik' => $validated['topik'] ?? 'Materi pertemuan '.$validated['pertemuan_ke'],
            'tanggal_pertemuan' => $validated['tanggal_pertemuan'] ?? today(),
            'status_pertemuan' => 'Terjadwal',
            'qr_token' => Str::random(40),
            'qr_expires_at' => now(),
            'is_active' => false,
        ]);

        return redirect()->route('dosen.dashboard', [
            'mata_kuliah' => $jadwalKuliah->mata_kuliah_id,
            'jadwal' => $jadwalKuliah->id,
        ])->with('success', "Pertemuan ke-{$pertemuan->pertemuan_ke} berhasil dibuat. Mulai pertemuan saat kelas siap.");
    }

    public function updateLokasi(Request $request, JadwalKuliah $jadwalKuliah): JsonResponse
    {
        if ($jadwalKuliah->dosen_id !== auth('dosen')->id()) {
            $isAssignedToActiveMeeting = $jadwalKuliah->pertemuan()
                ->where('dosen_pengganti_id', auth('dosen')->id())
                ->where('status_pertemuan', 'Berlangsung')
                ->exists();
            abort_unless($isAssignedToActiveMeeting, 403, 'Anda tidak berwenang mengelola kelas/pertemuan ini.');
        }

        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => ['nullable', 'integer', 'between:1,'.JadwalKuliah::MAX_RADIUS_METERS],
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

    private function authorizeDosen(JadwalKuliah $jadwal, ?Pertemuan $pertemuan = null): void
    {
        $dosenId = auth('dosen')->id();
        if ($jadwal->dosen_id !== $dosenId && $pertemuan?->dosen_pengganti_id !== $dosenId) {
            abort(403, 'Anda tidak berwenang mengelola kelas/pertemuan ini.');
        }
    }

    private function ensureClassRosterAttendance(Pertemuan $pertemuan): void
    {
        $kelasId = $pertemuan->jadwalKuliah->kelas_id;

        if (! $kelasId) {
            return;
        }

        $mahasiswaIds = Kelas::query()
            ->findOrFail($kelasId)
            ->mahasiswa()
            ->pluck('id');

        foreach ($mahasiswaIds as $mahasiswaId) {
            $pertemuan->presensi()->firstOrCreate(
                ['mahasiswa_id' => $mahasiswaId],
                ['status' => 'Tidak Hadir']
            );
        }
    }
}
