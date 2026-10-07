<?php

namespace Tests\Feature;

use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_list_shows_only_view_action_for_students_and_lecturers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101209',
        ]);
        User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501209',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertSeeText('Lihat')
            ->assertDontSeeText('Edit')
            ->assertDontSeeText('Hapus')
            ->assertDontSee('admin.users.destroy', false);
    }

    public function test_student_account_list_filters_by_database_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $selectedClass = Kelas::factory()->create(['kode_kelas' => '04SIFE001']);
        $otherClass = Kelas::factory()->create(['kode_kelas' => '04SIFE002']);
        User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $selectedClass->id,
            'name' => 'Mahasiswa Kelas Terpilih',
            'nomor_induk' => '220101210',
        ]);
        User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $otherClass->id,
            'name' => 'Mahasiswa Kelas Lain',
            'nomor_induk' => '220101211',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index', [
                'role' => 'mahasiswa',
                'class_id' => $selectedClass->id,
            ]))
            ->assertSeeText('Mahasiswa Kelas Terpilih')
            ->assertSeeText('04SIFE001')
            ->assertSee(route('admin.users.export-csv', ['class_id' => $selectedClass->id]), false)
            ->assertDontSeeText('Mahasiswa Kelas Lain');
    }

    public function test_admin_can_export_csv_for_the_selected_student_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $selectedClass = Kelas::factory()->create(['kode_kelas' => '04SIFE001']);
        $otherClass = Kelas::factory()->create(['kode_kelas' => '04SIFE002']);
        User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $selectedClass->id,
            'name' => 'Andi Pratama',
            'email' => 'andi@kampus.test',
            'nomor_induk' => '241011700101',
            'is_approved' => true,
        ]);
        User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $otherClass->id,
            'name' => 'Siti Aulia Rahma',
            'email' => 'siti@kampus.test',
            'nomor_induk' => '241011700102',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.users.export-csv', ['class_id' => $selectedClass->id]));

        $response->assertDownload('data-mahasiswa.csv')
            ->assertStreamed();

        $rows = array_map('str_getcsv', explode("\r\n", trim($response->streamedContent())));

        $this->assertSame(['NIM', 'Nama', 'Email', 'Kelas', 'Status'], $rows[0]);
        $this->assertSame([
            '241011700101',
            'Andi Pratama',
            'andi@kampus.test',
            '04SIFE001',
            'Disetujui',
        ], $rows[1]);
        $this->assertCount(2, $rows);
    }

    public function test_admin_can_export_all_student_classes_when_no_class_is_selected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstClass = Kelas::factory()->create(['kode_kelas' => '04SIFE001']);
        $secondClass = Kelas::factory()->create(['kode_kelas' => '05SIFE002']);
        User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $firstClass->id,
            'name' => 'Mahasiswa Kelas Empat',
            'email' => 'kelas-empat@kampus.test',
            'nomor_induk' => '241011700101',
            'is_approved' => false,
        ]);
        User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $secondClass->id,
            'name' => 'Mahasiswa Kelas Lima',
            'email' => 'kelas-lima@kampus.test',
            'nomor_induk' => '251011700201',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.users.export-csv'));

        $response->assertDownload('data-mahasiswa.csv')
            ->assertStreamed();

        $rows = array_map('str_getcsv', explode("\r\n", trim($response->streamedContent())));

        $this->assertSame(['NIM', 'Nama', 'Email', 'Kelas', 'Status'], $rows[0]);
        $this->assertSame([
            '241011700101',
            'Mahasiswa Kelas Empat',
            'kelas-empat@kampus.test',
            '04SIFE001',
            'Menunggu approval',
        ], $rows[1]);
        $this->assertSame([
            '251011700201',
            'Mahasiswa Kelas Lima',
            'kelas-lima@kampus.test',
            '05SIFE002',
            'Menunggu approval',
        ], $rows[2]);
        $this->assertCount(3, $rows);
    }

    public function test_csv_export_escapes_spreadsheet_formulas_in_student_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'role' => 'mahasiswa',
            'name' => '=HYPERLINK("https://example.test","open")',
            'email' => 'formula@kampus.test',
            'nomor_induk' => '241011700103',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.users.export-csv'));

        $rows = array_map('str_getcsv', explode("\r\n", trim($response->streamedContent())));

        $this->assertSame("'=HYPERLINK(\"https://example.test\",\"open\")", $rows[1][1]);
    }

    public function test_csv_export_rejects_a_class_that_does_not_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.export-csv', ['class_id' => 999999]))
            ->assertSessionHasErrors('class_id');
    }

    public function test_non_admin_cannot_export_student_csv(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($lecturer, 'dosen')
            ->get(route('admin.users.export-csv'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_edit_student_details_and_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $oldClass = Kelas::factory()->create(['kode_kelas' => '04SIFE001']);
        $newClass = Kelas::factory()->create(['kode_kelas' => '04SIFE002']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $oldClass->id,
            'nomor_induk' => '220101201',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.show', $student))
            ->assertOk()
            ->assertSee(route('admin.users.update', $student), false)
            ->assertSee(route('admin.users.destroy', $student), false);

        $this->patch(route('admin.users.update', $student), [
            'name' => 'Mahasiswa Diperbarui',
            'email' => 'student-updated@kampus.test',
            'nomor_induk' => '220101202',
            'kelas_id' => $newClass->id,
        ])
            ->assertRedirect(route('admin.users.show', $student))
            ->assertSessionHas('success', 'Data akun Mahasiswa berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Mahasiswa Diperbarui',
            'email' => 'student-updated@kampus.test',
            'nomor_induk' => '220101202',
            'kelas_id' => $newClass->id,
        ]);
    }

    public function test_admin_can_edit_lecturer_details_without_changing_role_or_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'is_approved' => true,
            'email' => 'lecturer-before@kampus.test',
            'nomor_induk' => '198501207',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.users.update', $lecturer), [
                'name' => 'Dosen Diperbarui',
                'email' => 'lecturer-after@kampus.test',
                'nomor_induk' => '198501208',
            ])
            ->assertRedirect(route('admin.users.show', $lecturer))
            ->assertSessionHas('success', 'Data akun Dosen berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id' => $lecturer->id,
            'name' => 'Dosen Diperbarui',
            'email' => 'lecturer-after@kampus.test',
            'nomor_induk' => '198501208',
            'role' => 'dosen',
            'is_approved' => true,
            'email_verified_at' => null,
        ]);
    }

    public function test_admin_cannot_change_account_identifiers_to_values_used_by_another_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101203',
        ]);
        User::factory()->create([
            'role' => 'dosen',
            'email' => 'lecturer@kampus.test',
            'nomor_induk' => '198501203',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.users.update', $student), [
                'name' => 'Nama Baru',
                'email' => 'lecturer@kampus.test',
                'nomor_induk' => '198501203',
            ])
            ->assertSessionHasErrors(['email', 'nomor_induk']);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'nomor_induk' => '220101203',
        ]);
    }

    public function test_admin_can_delete_student_and_related_attendance_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101204',
        ]);
        $meeting = $this->createMeeting($lecturer);
        $attendance = Presensi::create([
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $student->id,
            'status' => 'Hadir',
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.users.destroy', $student))
            ->assertRedirect(route('admin.users.index', ['role' => 'mahasiswa']))
            ->assertSessionHas('success', 'Akun mahasiswa dan data terkait berhasil dihapus.');

        $this->assertModelMissing($student);
        $this->assertModelMissing($attendance);
        $this->assertModelExists($meeting);
    }

    public function test_admin_can_delete_lecturer_and_related_schedule_meeting_and_attendance_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501204',
        ]);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101205',
        ]);
        $meeting = $this->createMeeting($lecturer);
        $schedule = $meeting->jadwalKuliah;
        $attendance = Presensi::create([
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $student->id,
            'status' => 'Hadir',
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.users.destroy', $lecturer))
            ->assertRedirect(route('admin.users.index', ['role' => 'dosen']))
            ->assertSessionHas('success', 'Akun dosen dan data terkait berhasil dihapus.');

        $this->assertModelMissing($lecturer);
        $this->assertModelMissing($schedule);
        $this->assertModelMissing($meeting);
        $this->assertModelMissing($attendance);
    }

    public function test_non_admin_cannot_edit_or_delete_accounts(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101206',
        ]);

        $this->actingAs($lecturer, 'dosen')
            ->patch(route('admin.users.update', $student), [
                'name' => 'Nama Baru',
                'email' => $student->email,
                'nomor_induk' => $student->nomor_induk,
            ])
            ->assertRedirect(route('login'));

        $this->delete(route('admin.users.destroy', $student))
            ->assertRedirect(route('login'));

        $this->assertModelExists($student);
    }

    public function test_admin_cannot_delete_another_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anotherAdmin = User::factory()->create([
            'role' => 'admin',
            'nomor_induk' => '990000002',
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.users.destroy', $anotherAdmin))
            ->assertNotFound();

        $this->assertModelExists($anotherAdmin);
    }

    private function createMeeting(User $lecturer): Pertemuan
    {
        $course = MataKuliah::create([
            'kode_mk' => 'IF9001',
            'nama_mk' => 'Pengelolaan Akun',
        ]);
        $schedule = JadwalKuliah::create([
            'dosen_id' => $lecturer->id,
            'mata_kuliah_id' => $course->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'latitude_kelas' => -6.1753924,
            'longitude_kelas' => 106.8271528,
            'radius_meter' => 5,
        ]);

        return Pertemuan::create([
            'jadwal_kuliah_id' => $schedule->id,
            'pertemuan_ke' => 1,
            'status_pertemuan' => 'Selesai',
            'qr_token' => Str::random(40),
            'qr_expires_at' => now(),
            'is_active' => false,
        ]);
    }
}
