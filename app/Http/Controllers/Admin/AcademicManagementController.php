<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AcademicManagementController extends Controller
{
    public function storeCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_mk' => ['required', 'string', 'max:32', 'unique:mata_kuliah,kode_mk'],
            'nama_mk' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated): void {
            $mataKuliah = MataKuliah::create($validated);
            AdminActivity::record(
                'course_added',
                "Mata kuliah ditambahkan: {$mataKuliah->nama_mk} ({$mataKuliah->kode_mk})"
            );
        });

        return redirect()->route('admin.courses')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_kelas' => ['required', 'string', 'max:20', 'unique:kelas,kode_kelas'],
        ]);

        DB::transaction(function () use ($validated): void {
            $kelas = Kelas::create($validated);
            AdminActivity::record('class_added', "Kelas ditambahkan: {$kelas->kode_kelas}");
        });

        return redirect()->route('admin.courses')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateClass(Request $request, Kelas $kelas): RedirectResponse
    {
        $validated = $request->validate([
            'kode_kelas' => ['required', 'string', 'max:20', Rule::unique('kelas', 'kode_kelas')->ignore($kelas->id)],
        ]);

        $kelas->update($validated);
        JadwalKuliah::query()->where('kelas_id', $kelas->id)->update(['kelas' => $validated['kode_kelas']]);

        return redirect()->route('admin.courses')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mata_kuliah_id' => ['required', 'integer', Rule::exists('mata_kuliah', 'id')],
            'kelas' => ['nullable', 'string', 'max:20'],
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
            'is_substitute' => ['sometimes', 'boolean'],
            'substitute_note' => ['nullable', 'string'],
        ]);

        $validated['is_substitute'] = $request->boolean('is_substitute');
        $validated['substitute_note'] = $validated['is_substitute']
            ? ($validated['substitute_note'] ?? null)
            : null;

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

        $validated['kelas_id'] = filled($validated['kelas'] ?? null)
            ? Kelas::firstOrCreate(['kode_kelas' => $validated['kelas']])->id
            : null;

        JadwalKuliah::create([
            ...$validated,
            'radius_meter' => JadwalKuliah::MAX_RADIUS_METERS,
        ]);

        return redirect()->route('admin.courses')
            ->with('success', 'Jadwal berhasil dibuat dan ditugaskan kepada dosen.');
    }

    public function updateScheduleLecturer(Request $request, JadwalKuliah $jadwalKuliah): RedirectResponse
    {
        $validated = $request->validate([
            'dosen_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('role', 'dosen')
                    ->where('is_approved', true)),
            ],
        ]);

        if ((int) $validated['dosen_id'] !== $jadwalKuliah->dosen_id) {
            $dosenBaru = User::query()->findOrFail($validated['dosen_id']);
            DB::transaction(function () use ($jadwalKuliah, $dosenBaru): void {
                $jadwalKuliah->update(['dosen_id' => $dosenBaru->id]);
                AdminActivity::record(
                    'lecturer_changed',
                    "Dosen pengampu {$jadwalKuliah->mataKuliah->nama_mk} · {$jadwalKuliah->kelas}: {$dosenBaru->name}"
                );
            });
        }

        return redirect()->route('admin.courses')
            ->with('success', 'Dosen pengampu jadwal berhasil diperbarui.');
    }

    public function updateScheduleClass(Request $request, JadwalKuliah $jadwalKuliah): RedirectResponse
    {
        $validated = $request->validate([
            'kelas' => ['present', 'nullable', 'string', 'max:20'],
        ]);

        $kelas = filled($validated['kelas'] ?? null)
            ? Kelas::firstOrCreate(['kode_kelas' => $validated['kelas']])
            : null;

        $jadwalKuliah->update([
            'kelas' => $validated['kelas'],
            'kelas_id' => $kelas?->id,
        ]);

        return redirect()->route('admin.courses')
            ->with('success', 'Kelas jadwal berhasil diperbarui.');
    }

    public function updateScheduleTime(Request $request, JadwalKuliah $jadwalKuliah): RedirectResponse
    {
        $validated = $request->validate([
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ]);

        $hasTimeConflict = JadwalKuliah::query()
            ->where('id', '<>', $jadwalKuliah->id)
            ->where('dosen_id', $jadwalKuliah->dosen_id)
            ->where('hari', $jadwalKuliah->hari)
            ->where('jam_mulai', '<', $validated['jam_selesai'])
            ->where('jam_selesai', '>', $validated['jam_mulai'])
            ->exists();

        if ($hasTimeConflict) {
            return back()
                ->withErrors(['jam_mulai' => 'Jadwal dosen bentrok dengan kelas lain pada hari dan waktu tersebut.'])
                ->withInput();
        }

        $jadwalKuliah->update($validated);

        return redirect()->route('admin.courses')
            ->with('success', 'Jam jadwal berhasil diperbarui.');
    }

    public function storeMeeting(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jadwal_kuliah_id' => ['required', 'integer', Rule::exists('jadwal_kuliah', 'id')],
            'pertemuan_ke' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('pertemuan', 'pertemuan_ke')
                    ->where('jadwal_kuliah_id', $request->integer('jadwal_kuliah_id')),
            ],
            'topik' => ['required', 'string', 'max:255'],
            'tanggal_pertemuan' => ['required', 'date'],
        ]);

        $jadwalKuliah = JadwalKuliah::findOrFail($validated['jadwal_kuliah_id']);

        Pertemuan::create([
            'pertemuan_ke' => $validated['pertemuan_ke'],
            'topik' => $validated['topik'],
            'tanggal_pertemuan' => $validated['tanggal_pertemuan'],
            'jadwal_kuliah_id' => $jadwalKuliah->id,
            'status_pertemuan' => 'Terjadwal',
            'qr_token' => Str::random(40),
            'qr_expires_at' => now(),
            'is_active' => false,
        ]);

        return redirect()->route('admin.courses')->with('success', 'Pertemuan berhasil ditambahkan.');
    }

    public function updateMeeting(Request $request, Pertemuan $pertemuan): RedirectResponse
    {
        $validated = $request->validate([
            'pertemuan_ke' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('pertemuan', 'pertemuan_ke')
                    ->where('jadwal_kuliah_id', $pertemuan->jadwal_kuliah_id)
                    ->ignore($pertemuan->id),
            ],
            'topik' => ['required', 'string', 'max:255'],
            'tanggal_pertemuan' => ['required', 'date'],
        ]);

        $pertemuan->update($validated);

        return redirect()->route('admin.courses')->with('success', 'Data pertemuan berhasil diperbarui.');
    }

    public function updateSubstituteLecturer(Request $request, Pertemuan $pertemuan): RedirectResponse
    {
        $validated = $request->validate([
            'dosen_pengganti_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query): Builder => $query
                    ->where('role', 'dosen')
                    ->where('is_approved', true)),
            ],
        ]);

        $substituteId = $validated['dosen_pengganti_id'] ?? null;
        if ($substituteId !== null && (int) $substituteId === $pertemuan->jadwalKuliah->dosen_id) {
            return back()->withErrors([
                'dosen_pengganti_id' => 'Dosen pengganti harus berbeda dari dosen utama.',
            ])->withInput();
        }

        $pertemuan->update(['dosen_pengganti_id' => $substituteId]);

        return redirect()->route('admin.courses')->with('success', 'Dosen pengganti pertemuan berhasil diperbarui.');
    }
}
