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
            ->assertSee('course-list-search', false)
            ->assertSeeText('Pencarian hanya mencakup nama dan kode mata kuliah.')
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
            'kelas' => 'IF-A',
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
            'kelas' => 'IF-A',
            'radius_meter' => 5,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.courses'))
            ->assertSee('IF-A')
            ->assertSee(route('admin.schedules.update-class', JadwalKuliah::query()->firstOrFail()), false)
            ->assertSee(route('admin.schedules.update-time', JadwalKuliah::query()->firstOrFail()), false)
            ->assertSeeText('Jam mulai')
            ->assertSeeText('Jam selesai')
            ->assertDontSeeText('Hapus mata kuliah')
            ->assertDontSeeText('Edit mata kuliah');

        $this->actingAs($lecturer, 'dosen')
            ->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertSee('Pengujian Perangkat Lunak')
            ->assertSee('Buat Pertemuan 1')
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

    public function test_admin_can_update_schedule_start_and_end_times(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501011',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'IF4210',
            'nama_mk' => 'Jadwal Dapat Diedit',
        ]);
        $schedule = $this->createSchedule($course, $lecturer, [
            'hari' => 'Rabu',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.schedules.update-time', $schedule), [
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:30',
            ])
            ->assertRedirect(route('admin.courses'))
            ->assertSessionHas('success', 'Jam jadwal berhasil diperbarui.');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $schedule->id,
            'jam_mulai' => '10:00',
            'jam_selesai' => '12:30',
        ]);
    }

    public function test_schedule_time_update_rejects_invalid_order_and_lecturer_conflicts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501012',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'IF4211',
            'nama_mk' => 'Jadwal Pertama',
        ]);
        $schedule = $this->createSchedule($course, $lecturer, [
            'hari' => 'Kamis',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
        $otherCourse = MataKuliah::create([
            'kode_mk' => 'IF4212',
            'nama_mk' => 'Jadwal Kedua',
        ]);
        $this->createSchedule($otherCourse, $lecturer, [
            'hari' => 'Kamis',
            'jam_mulai' => '13:00',
            'jam_selesai' => '15:00',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.schedules.update-time', $schedule), [
                'jam_mulai' => '10:00',
                'jam_selesai' => '09:00',
            ])
            ->assertSessionHasErrors('jam_selesai');

        $this->patch(route('admin.schedules.update-time', $schedule), [
            'jam_mulai' => '12:30',
            'jam_selesai' => '14:00',
        ])
            ->assertSessionHasErrors('jam_mulai');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $schedule->id,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
    }

    public function test_admin_can_edit_or_clear_a_schedule_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501007',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'IF4106',
            'nama_mk' => 'Pemrograman Lanjut',
        ]);
        $schedule = $this->createSchedule($course, $lecturer);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.schedules.update-class', $schedule), ['kelas' => 'IF-B'])
            ->assertRedirect(route('admin.courses'));

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $schedule->id,
            'kelas' => 'IF-B',
        ]);

        $this->patch(route('admin.schedules.update-class', $schedule), ['kelas' => ''])
            ->assertRedirect(route('admin.courses'));

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $schedule->id,
            'kelas' => null,
        ]);
    }

    public function test_schedule_class_cannot_exceed_twenty_characters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501008',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'IF4107',
            'nama_mk' => 'Rekayasa Perangkat Lunak',
        ]);
        $schedule = $this->createSchedule($course, $lecturer, ['kelas' => 'IF-A']);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.schedules.update-class', $schedule), ['kelas' => str_repeat('A', 21)])
            ->assertSessionHasErrors('kelas');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $schedule->id,
            'kelas' => 'IF-A',
        ]);
    }

    public function test_admin_schedule_form_uses_the_fixed_campus_radius(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501006',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'IF4105',
            'nama_mk' => 'Sistem Operasi',
        ]);

        $this->actingAs($admin, 'admin');
        $this->get(route('admin.courses'))
            ->assertOk()
            ->assertDontSee('name="radius_meter"', false);
        $this->post(route('admin.schedules.store'), $this->schedulePayload($course, $lecturer, [
            'radius_meter' => JadwalKuliah::MAX_RADIUS_METERS + 10,
        ]))
            ->assertRedirect(route('admin.courses'));

        $this->assertDatabaseHas('jadwal_kuliah', [
            'mata_kuliah_id' => $course->id,
            'radius_meter' => JadwalKuliah::MAX_RADIUS_METERS,
        ]);
    }

    public function test_non_admin_cannot_create_or_edit_a_schedule(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501004',
        ]);
        $course = MataKuliah::create([
            'kode_mk' => 'IF4103',
            'nama_mk' => 'Jaringan Komputer',
        ]);
        $schedule = $this->createSchedule($course, $lecturer);

        $this->actingAs($lecturer, 'dosen')
            ->post(route('admin.schedules.store'), $this->schedulePayload($course, $lecturer))
            ->assertRedirect(route('login'));

        $this->patch(route('admin.schedules.update-class', $schedule), ['kelas' => 'IF-A'])
            ->assertRedirect(route('login'));

        $this->patch(route('admin.schedules.update-time', $schedule), [
            'jam_mulai' => '10:00',
            'jam_selesai' => '12:00',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $schedule->id,
            'kelas' => null,
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
        ]);
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
            ->assertSee('Rekap Presensi')
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
            'radius_meter' => 5,
            ...$overrides,
        ];
    }
}
