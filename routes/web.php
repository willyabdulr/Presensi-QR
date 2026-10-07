<?php

use App\Http\Controllers\Admin\AcademicManagementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DosenPertemuanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    }

    if (Auth::guard('dosen')->check()) {
        return redirect()->route('dosen.dashboard');
    }

    if (Auth::guard('mahasiswa')->check()) {
        return redirect()->route('mahasiswa.dashboard');
    }

    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/login/{role}', [AuthController::class, 'showLoginForm'])
    ->where('role', 'admin|dosen|mahasiswa')
    ->name('login.role');
Route::post('/login', [AuthController::class, 'login'])->name('login.authenticate');
Route::post('/login/{role}', [AuthController::class, 'login'])
    ->where('role', 'admin|dosen|mahasiswa')
    ->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [RegistrationController::class, 'create'])->name('register');
Route::get('/register/{role}', [RegistrationController::class, 'create'])
    ->where('role', 'dosen|mahasiswa')
    ->name('register.role');
Route::post('/register/{role}', [RegistrationController::class, 'store'])
    ->where('role', 'dosen|mahasiswa')
    ->middleware('throttle:5,1')
    ->name('register.store');

Route::middleware(['cache.headers:no_store;private;no_cache;must_revalidate;max_age=0', 'auth:admin', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/export-csv', [UserManagementController::class, 'exportStudents'])->name('users.export-csv');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/approve', [UserManagementController::class, 'approve'])->name('users.approve');
    Route::patch('/users/{user}/kelas', [UserManagementController::class, 'updateClass'])->name('users.update-class');
    Route::get('/mata-kuliah', [AdminDashboardController::class, 'courses'])->name('courses');
    Route::post('/mata-kuliah', [AcademicManagementController::class, 'storeCourse'])->name('courses.store');
    Route::post('/kelas', [AcademicManagementController::class, 'storeClass'])->name('classes.store');
    Route::patch('/kelas/{kelas}', [AcademicManagementController::class, 'updateClass'])->name('classes.update');
    Route::post('/jadwal-kuliah', [AcademicManagementController::class, 'storeSchedule'])->name('schedules.store');
    Route::patch('/jadwal-kuliah/{jadwalKuliah}/kelas', [AcademicManagementController::class, 'updateScheduleClass'])->name('schedules.update-class');
    Route::patch('/jadwal-kuliah/{jadwalKuliah}/jam', [AcademicManagementController::class, 'updateScheduleTime'])->name('schedules.update-time');
    Route::patch('/jadwal-kuliah/{jadwalKuliah}/dosen', [AcademicManagementController::class, 'updateScheduleLecturer'])->name('schedules.update-lecturer');
    Route::post('/pertemuan', [AcademicManagementController::class, 'storeMeeting'])->name('meetings.store');
    Route::patch('/pertemuan/{pertemuan}', [AcademicManagementController::class, 'updateMeeting'])->name('meetings.update');
    Route::patch('/pertemuan/{pertemuan}/dosen-pengganti', [AcademicManagementController::class, 'updateSubstituteLecturer'])->name('meetings.update-substitute');
    Route::get('/rekap-presensi', [AdminDashboardController::class, 'attendance'])->name('attendance');
    Route::get('/profil/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
});

Route::middleware(['cache.headers:no_store;private;no_cache;must_revalidate;max_age=0', 'auth:dosen', 'role:dosen'])->prefix('dosen')->name('dosen.')->group(function () {
    Route::get('/dashboard', [DosenPertemuanController::class, 'index'])->name('dashboard');
    Route::get('/rekap-absensi', [DosenPertemuanController::class, 'rekapAbsensi'])->name('attendance');
    Route::post('/jadwal/{jadwalKuliah}/pertemuan', [DosenPertemuanController::class, 'storePertemuan'])->name('pertemuan.store');
    Route::get('/pertemuan/{pertemuan}/qr', [DosenPertemuanController::class, 'showQr'])->name('pertemuan.qr');
    Route::post('/pertemuan/{pertemuan}/mulai', [DosenPertemuanController::class, 'startMeeting'])->name('pertemuan.start');
    Route::post('/pertemuan/{pertemuan}/akhiri', [DosenPertemuanController::class, 'endMeeting'])->name('pertemuan.end');
    Route::post('/pertemuan/{pertemuan}/regenerate-qr', [DosenPertemuanController::class, 'regenerateQr'])->name('pertemuan.regenerate_qr');
    Route::post('/pertemuan/{pertemuan}/presensi/{presensi}/manual', [DosenPertemuanController::class, 'setManualAttendance'])->name('pertemuan.manual_attendance');
    Route::get('/pertemuan/{pertemuan}/live-attendance', [DosenPertemuanController::class, 'liveAttendance'])->name('pertemuan.live_attendance');
    Route::get('/jadwal/{jadwalKuliah}/export-rekap', [DosenPertemuanController::class, 'exportRekap'])->name('jadwal.export_rekap');
    Route::post('/jadwal/{jadwalKuliah}/update-lokasi', [DosenPertemuanController::class, 'updateLokasi'])->name('jadwal.update_lokasi');
    Route::get('/profil/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
});

Route::middleware(['cache.headers:no_store;private;no_cache;must_revalidate;max_age=0', 'auth:mahasiswa', 'role:mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/dashboard', [PresensiController::class, 'dashboard'])->name('dashboard');
    Route::get('/scan', [PresensiController::class, 'scanPage'])->name('scan');
    Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');
    Route::get('/riwayat', [PresensiController::class, 'riwayat'])->name('riwayat');
    Route::get('/profil/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
});
