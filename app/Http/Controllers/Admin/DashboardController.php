<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalKuliah;
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

        $recentMeetings = Pertemuan::query()
            ->with(['jadwalKuliah.mataKuliah', 'jadwalKuliah.dosen'])
            ->withCount(['presensi as total_hadir' => fn ($query) => $query->where('status', 'Hadir')])
            ->latest()
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'pendingUsers', 'recentMeetings'));
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
        $jadwalList = JadwalKuliah::query()
            ->with(['mataKuliah', 'dosen'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(15);

        return view('admin.courses', compact('mataKuliahList', 'dosenList', 'jadwalList'));
    }

    public function attendance(): View
    {
        $meetingList = Pertemuan::query()
            ->with(['jadwalKuliah.mataKuliah', 'jadwalKuliah.dosen'])
            ->withCount(['presensi as total_hadir' => fn ($query) => $query->where('status', 'Hadir')])
            ->latest()
            ->paginate(15);

        $totalAttendance = Presensi::query()->where('status', 'Hadir')->count();
        $totalMeetings = Pertemuan::query()->count();

        return view('admin.attendance', compact('meetingList', 'totalAttendance', 'totalMeetings'));
    }
}
