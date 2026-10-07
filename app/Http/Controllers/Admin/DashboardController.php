<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'mahasiswa' => User::query()->where('role', 'mahasiswa')->count(),
            'dosen' => User::query()->where('role', 'dosen')->count(),
            'pending' => User::query()->whereIn('role', ['mahasiswa', 'dosen'])->where('is_approved', false)->count(),
            'mata_kuliah' => MataKuliah::query()->count(),
            'pertemuan' => Pertemuan::query()->count(),
            'presensi' => Presensi::query()->where('status', 'Hadir')->count(),
            'presensi_hari_ini' => Presensi::query()->where('status', 'Hadir')->whereDate('waktu_presensi', today())->count(),
        ];

        $pendingUsers = User::query()
            ->whereIn('role', ['mahasiswa', 'dosen'])
            ->where('is_approved', false)
            ->latest()
            ->limit(6)
            ->get();

        $recentActivities = AdminActivity::query()
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'pendingUsers', 'recentActivities'));
    }

    public function courses(): View
    {
        $mataKuliahList = MataKuliah::query()
            ->withCount('jadwalKuliah')
            ->orderBy('nama_mk')
            ->get();
        $dosenList = User::query()
            ->where('role', 'dosen')
            ->where('is_approved', true)
            ->orderBy('name')
            ->get();
        $mataKuliahList = MataKuliah::query()
            ->withCount('jadwalKuliah')
            ->with(['jadwalKuliah' => function ($query): void {
                $query->with([
                    'dosen',
                    'kelasData',
                    'pertemuan' => fn ($query) => $query->orderBy('pertemuan_ke'),
                    'pertemuan.dosenPengganti',
                ])->orderBy('hari')->orderBy('jam_mulai');
            }])
            ->orderBy('nama_mk')
            ->get();
        $kelasList = Kelas::query()
            ->withCount(['mahasiswa', 'jadwalKuliah'])
            ->orderBy('kode_kelas')
            ->get();

        return view('admin.courses', compact('mataKuliahList', 'dosenList', 'kelasList'));
    }

    public function attendance(): View
    {
        $jadwalList = JadwalKuliah::query()
            ->with(['mataKuliah', 'kelasData'])
            ->orderBy('mata_kuliah_id')
            ->orderBy('kelas')
            ->get();

        $totalAttendance = Presensi::query()->where('status', 'Hadir')->count();
        $totalMeetings = Pertemuan::query()->count();

        $mataKuliahList = MataKuliah::query()
            ->orderBy('nama_mk')
            ->get();
        $selectedMataKuliah = $mataKuliahList->firstWhere('id', request()->integer('mata_kuliah'))
            ?? $mataKuliahList->first();
        $kelasList = $jadwalList
            ->where('mata_kuliah_id', $selectedMataKuliah?->id)
            ->values();
        $selectedJadwal = $kelasList->firstWhere('id', request()->integer('jadwal'))
            ?? $kelasList->first();
        $pertemuanList = $selectedJadwal
            ?->pertemuan()
            ->with(['jadwalKuliah.mataKuliah', 'jadwalKuliah.dosen', 'dosenPengganti'])
            ->withCount([
                'presensi as total_hadir' => fn ($query) => $query->where('status', 'Hadir'),
                'presensi as total_presensi',
            ])
            ->orderBy('pertemuan_ke')
            ->get() ?? collect();
        $jumlahMahasiswa = $selectedJadwal?->kelasData?->mahasiswa()->count()
            ?? $pertemuanList->max('total_presensi')
            ?? 0;

        return view('admin.attendance', compact(
            'jadwalList',
            'mataKuliahList',
            'selectedMataKuliah',
            'kelasList',
            'selectedJadwal',
            'pertemuanList',
            'jumlahMahasiswa',
            'totalAttendance',
            'totalMeetings'
        ));
    }
}
