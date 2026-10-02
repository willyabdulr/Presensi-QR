<?php

namespace Tests\Feature;

use App\Models\JadwalKuliah;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcademicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_course_and_assign_it_to_an_approved_lecturer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501001',
        ]);
        $lecturer->forceFill(['is_approved' => true])->save();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.courses'))
            ->assertOk()
            ->assertSeeText('Buat Jadwal & Tugaskan Dosen')
            ->assertSeeText('Dosen pengganti')
            ->assertSeeText('Catatan dosen pengganti')
            ->assertSee($lecturer->name);

        $this->post(route('admin.courses.store'), [
            'kode_mk' => 'IF4101',
            'nama_mk' => 'Pengujian Perangkat Lunak',
        ])
            ->assertRedirect(route('admin.courses'));

        $course = MataKuliah::query()->where('kode_mk', 'IF4101')->firstOrFail();

        $this->post(route('admin.schedules.store'), [
            'mata_kuliah_id' => $course->id,
            'dosen_id' => $lecturer->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'latitude_kelas' => '-6.20000000',
            'longitude_kelas' => '106.80000000',
            'radius_meter' => 50,
            'is_substitute' => true,
            'substitute_note' => 'Menggantikan dosen berhalangan hadir.',
        ])->assertRedirect(route('admin.courses'));

        $this->assertDatabaseHas('jadwal_kuliah', [
            'dosen_id' => $lecturer->id,
            'mata_kuliah_id' => $course->id,
            'hari' => 'Senin',
            'is_substitute' => true,
            'substitute_note' => 'Menggantikan dosen berhalangan hadir.',
        ]);

        $this->get(route('admin.courses'))
            ->assertOk()
            ->assertSeeText('(Pengganti)')
            ->assertSeeText('Menggantikan dosen berhalangan hadir.');

        $this->actingAs($lecturer, 'dosen')
            ->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertSee('Pengujian Perangkat Lunak')
            ->assertSee('Buka Pertemuan 1')
            ->assertDontSee('Belum ada jadwal kuliah');
    }

    public function test_schedule_assignment_rejects_pending_lecturers_and_time_conflicts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501002',
        ]);
        $pendingLecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501003',
        ]);
        $pendingLecturer->forceFill(['is_approved' => false])->save();
        $course = MataKuliah::create([
            'kode_mk' => 'IF4102',
            'nama_mk' => 'Basis Data Lanjut',
        ]);
        $this->createSchedule($course, $lecturer);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.schedules.store'), $this->schedulePayload($course, $pendingLecturer))
            ->assertSessionHasErrors('dosen_id');

        $this->post(route('admin.schedules.store'), $this->schedulePayload($course, $lecturer, [
            'jam_mulai' => '09:30',
            'jam_selesai' => '11:00',
        ]))->assertSessionHasErrors('jam_mulai');

        $this->assertDatabaseCount('jadwal_kuliah', 1);
    }

    public function test_non_admin_cannot_create_a_schedule(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501004',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'IF4103',
            'nama_mk' => 'Jaringan Komputer',
        ]);

        $this->actingAs($lecturer, 'dosen')
            ->post(route('admin.schedules.store'), $this->schedulePayload($course, $lecturer))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('jadwal_kuliah', 0);
    }

    public function test_lecturer_attendance_report_shows_meeting_status_and_percentage(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501005',
        ]);
        $lecturer->forceFill(['is_approved' => true])->save();
        $course = MataKuliah::create([
            'kode_mk' => 'IF4104',
            'nama_mk' => 'Pemrograman Web',
        ]);
        $schedule = $this->createSchedule($course, $lecturer);
        $meeting = Pertemuan::create([
            'jadwal_kuliah_id' => $schedule->id,
            'pertemuan_ke' => 1,
            'qr_token' => Str::random(40),
            'qr_expires_at' => now()->addMinutes(20),
            'is_active' => true,
        ]);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101050',
        ]);
        Presensi::create([
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $student->id,
            'status' => 'Hadir',
            'waktu_presensi' => now(),
        ]);

        $this->actingAs($lecturer, 'dosen')
            ->get(route('dosen.attendance'))
            ->assertOk()
            ->assertSee('Rekap Absensi')
            ->assertSee('Pemrograman Web')
            ->assertSee($student->name)
            ->assertSee($student->nomor_induk)
            ->assertSee('P1')
            ->assertSee('100.0%')
            ->assertSee(route('dosen.jadwal.export_rekap', $schedule), false);
    }

    /** @param array<string, mixed> $overrides */
    private function createSchedule(MataKuliah $course, User $lecturer, array $overrides = []): JadwalKuliah
    {
        return JadwalKuliah::create($this->schedulePayload($course, $lecturer, $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function schedulePayload(MataKuliah $course, User $lecturer, array $overrides = []): array
    {
        return [
            'mata_kuliah_id' => $course->id,
            'dosen_id' => $lecturer->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'latitude_kelas' => '-6.20000000',
            'longitude_kelas' => '106.80000000',
            'radius_meter' => 50,
            ...$overrides,
        ];
    }
}
