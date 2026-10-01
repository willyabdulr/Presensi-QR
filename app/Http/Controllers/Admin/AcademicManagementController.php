<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalKuliah;
use App\Models\MataKuliah;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicManagementController extends Controller
{
    public function storeCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_mk' => ['required', 'string', 'max:32', 'unique:mata_kuliah,kode_mk'],
            'nama_mk' => ['required', 'string', 'max:255'],
        ]);

        MataKuliah::create($validated);

        return redirect()->route('admin.courses')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mata_kuliah_id' => ['required', 'integer', Rule::exists('mata_kuliah', 'id')],
            'dosen_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('role', 'dosen')
                    ->where('is_approved', true)),
            ],
            'hari' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'latitude_kelas' => ['required', 'numeric', 'between:-90,90'],
            'longitude_kelas' => ['required', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required', 'integer', 'between:1,1000'],
        ]);

        $hasTimeConflict = JadwalKuliah::query()
            ->where('dosen_id', $validated['dosen_id'])
            ->where('hari', $validated['hari'])
            ->where('jam_mulai', '<', $validated['jam_selesai'])
            ->where('jam_selesai', '>', $validated['jam_mulai'])
            ->exists();

        if ($hasTimeConflict) {
            return back()
                ->withErrors(['jam_mulai' => 'Jadwal dosen bentrok dengan kelas lain pada hari dan waktu tersebut.'])
                ->withInput();
        }

        JadwalKuliah::create($validated);

        return redirect()->route('admin.courses')
            ->with('success', 'Jadwal berhasil dibuat dan ditugaskan kepada dosen.');
    }
}
