<?php

namespace Tests\Feature;

use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DosenAttendanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_dashboard_filters_meetings_by_selected_course_and_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mataKuliah = MataKuliah::create(['kode_mk' => 'IF5001', 'nama_mk' => 'Pemrograman Web']);
        $kelasSatu = Kelas::create(['kode_kelas' => '04SIFE001']);
        $kelasDua = Kelas::create(['kode_kelas' => '04SIFE002']);
        $jadwalSatu = $this->createJadwal($dosen, $mataKuliah, $kelasSatu);
        $jadwalDua = $this->createJadwal($dosen, $mataKuliah, $kelasDua);
        $this->createPertemuan($jadwalSatu, 1, 'HTML dan Struktur Halaman');
        $this->createPertemuan($jadwalDua, 1, 'Basis Data Relasional');

        $this->actingAs($dosen, 'dosen')
            ->get(route('dosen.dashboard', ['mata_kuliah' => $mataKuliah->id, 'jadwal' => $jadwalSatu->id]))
            ->assertSeeText('Pemrograman Web')
            ->assertSeeText('04SIFE001')
            ->assertSeeText('04SIFE002')
            ->assertSeeText('HTML dan Struktur Halaman')
            ->assertDontSeeText('Basis Data Relasional');
    }

    public function test_dosen_attendance_report_filters_by_course_and_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $pemrogramanWeb = MataKuliah::create(['kode_mk' => 'IF5101', 'nama_mk' => 'Pemrograman Web']);
        $basisData = MataKuliah::create(['kode_mk' => 'IF5102', 'nama_mk' => 'Basis Data']);
        $kelasWeb = Kelas::create(['kode_kelas' => '04SIFE001']);
        $kelasDatabase = Kelas::create(['kode_kelas' => '04SIFE002']);
        $jadwalWeb = $this->createJadwal($dosen, $pemrogramanWeb, $kelasWeb);
        $jadwalDatabase = $this->createJadwal($dosen, $basisData, $kelasDatabase);
        $meetingWeb = $this->createPertemuan($jadwalWeb, 1, 'HTML Dasar');
        $meetingDatabase = $this->createPertemuan($jadwalDatabase, 1, 'SQL Dasar');
        $mahasiswaWeb = User::factory()->create([
            'role' => 'mahasiswa',
            'name' => 'Mahasiswa Kelas Web',
            'nomor_induk' => '241011700601',
            'kelas_id' => $kelasWeb->id,
        ]);
        $mahasiswaDatabase = User::factory()->create([
            'role' => 'mahasiswa',
            'name' => 'Mahasiswa Kelas Basis Data',
            'nomor_induk' => '241011700602',
            'kelas_id' => $kelasDatabase->id,
        ]);
        Presensi::create([
            'pertemuan_id' => $meetingWeb->id,
            'mahasiswa_id' => $mahasiswaWeb->id,
            'status' => 'Hadir',
        ]);
        Presensi::create([
            'pertemuan_id' => $meetingDatabase->id,
            'mahasiswa_id' => $mahasiswaDatabase->id,
            'status' => 'Hadir',
        ]);

        $this->actingAs($dosen, 'dosen')
            ->get(route('dosen.attendance', [
                'mata_kuliah' => $pemrogramanWeb->id,
                'jadwal' => $jadwalWeb->id,
            ]))
            ->assertSeeText('Pemrograman Web')
            ->assertSeeText('04SIFE001')
            ->assertSeeText('HTML Dasar')
            ->assertSeeText($mahasiswaWeb->name)
            ->assertDontSeeText('04SIFE002')
            ->assertDontSeeText('SQL Dasar')
            ->assertDontSeeText($mahasiswaDatabase->name);
    }

    public function test_qr_page_lists_only_students_assigned_to_the_meeting_class(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mataKuliah = MataKuliah::create(['kode_mk' => 'IF5002', 'nama_mk' => 'Basis Data']);
        $kelas = Kelas::create(['kode_kelas' => '04SIFE001']);
        $kelasLain = Kelas::create(['kode_kelas' => '04SIFE002']);
        $jadwal = $this->createJadwal($dosen, $mataKuliah, $kelas);
        $meeting = $this->createPertemuan($jadwal, 3, 'SQL dan JOIN');
        $mahasiswaSatu = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700101',
            'kelas_id' => $kelas->id,
        ]);
        $mahasiswaDua = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700102',
            'kelas_id' => $kelas->id,
        ]);
        $mahasiswaLain = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700201',
            'kelas_id' => $kelasLain->id,
        ]);

        $this->actingAs($dosen, 'dosen')
            ->get(route('dosen.pertemuan.qr', $meeting))
            ->assertSeeText('Pertemuan 3')
            ->assertSeeText('SQL dan JOIN')
            ->assertSeeText($mahasiswaSatu->name)
            ->assertSeeText($mahasiswaDua->name)
            ->assertDontSeeText($mahasiswaLain->name)
            ->assertSeeText('Tidak Hadir');

        $this->assertDatabaseCount('presensi', 2);
        $this->assertDatabaseHas('presensi', [
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $mahasiswaSatu->id,
            'status' => 'Tidak Hadir',
        ]);
    }

    public function test_student_qr_scan_changes_existing_absence_to_present(): void
    {
        $kelas = Kelas::create(['kode_kelas' => '04SIFE001']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mataKuliah = MataKuliah::create(['kode_mk' => 'IF5003', 'nama_mk' => 'Analisis Sistem']);
        $jadwal = $this->createJadwal($dosen, $mataKuliah, $kelas);
        $meeting = $this->createPertemuan($jadwal, 1, 'Analisis kebutuhan');
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700301',
            'kelas_id' => $kelas->id,
        ]);
        $presensi = Presensi::create([
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => 'Tidak Hadir',
        ]);

        $this->actingAs($mahasiswa, 'mahasiswa')
            ->postJson(route('mahasiswa.presensi.store'), [
                'qr_token' => $meeting->qr_token,
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('presensi', [
            'id' => $presensi->id,
            'status' => 'Hadir',
            'latitude_mahasiswa' => -6.1753924,
            'longitude_mahasiswa' => 106.8271528,
        ]);
        $this->assertDatabaseCount('presensi', 1);
    }

    public function test_student_cannot_scan_a_meeting_for_another_class(): void
    {
        $kelasJadwal = Kelas::create(['kode_kelas' => '04SIFE001']);
        $kelasMahasiswa = Kelas::create(['kode_kelas' => '04SIFE002']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mataKuliah = MataKuliah::create(['kode_mk' => 'IF5004', 'nama_mk' => 'Interaksi Manusia Komputer']);
        $jadwal = $this->createJadwal($dosen, $mataKuliah, $kelasJadwal);
        $meeting = $this->createPertemuan($jadwal, 1, 'Usability');
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700401',
            'kelas_id' => $kelasMahasiswa->id,
        ]);

        $this->actingAs($mahasiswa, 'mahasiswa')
            ->postJson(route('mahasiswa.presensi.store'), [
                'qr_token' => $meeting->qr_token,
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
            ])
            ->assertForbidden()
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseCount('presensi', 0);
    }

    public function test_admin_can_create_and_update_meeting_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mataKuliah = MataKuliah::create(['kode_mk' => 'IF5005', 'nama_mk' => 'Pemrograman Web']);
        $kelas = Kelas::create(['kode_kelas' => '04SIFE001']);
        $jadwal = $this->createJadwal($dosen, $mataKuliah, $kelas);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.meetings.store'), [
                'jadwal_kuliah_id' => $jadwal->id,
                'pertemuan_ke' => 1,
                'topik' => 'HTML Dasar',
                'tanggal_pertemuan' => '2026-10-01',
            ])
            ->assertRedirect(route('admin.courses'));

        $meeting = Pertemuan::query()->firstOrFail();

        $this->post(route('admin.meetings.store'), [
            'jadwal_kuliah_id' => $jadwal->id,
            'pertemuan_ke' => 1,
            'topik' => 'Duplikasi nomor',
            'tanggal_pertemuan' => '2026-10-03',
        ])->assertSessionHasErrors('pertemuan_ke');
        $this->assertDatabaseCount('pertemuan', 1);

        $this->patch(route('admin.meetings.update', $meeting), [
            'pertemuan_ke' => 1,
            'topik' => 'HTML dan Struktur Halaman',
            'tanggal_pertemuan' => '2026-10-02',
            'status_pertemuan' => 'Berlangsung',
        ])->assertRedirect(route('admin.courses'));

        $this->assertDatabaseHas('pertemuan', [
            'id' => $meeting->id,
            'topik' => 'HTML dan Struktur Halaman',
            'status_pertemuan' => 'Terjadwal',
        ]);
        $this->assertSame('2026-10-02', $meeting->fresh()->tanggal_pertemuan->toDateString());
        $this->get(route('admin.attendance'))
            ->assertSeeText('Monitoring Akademik')
            ->assertDontSeeText('Tambah Pertemuan')
            ->assertDontSee('name="status_pertemuan"', false);
        $this->get(route('admin.courses'))
            ->assertSeeText('Atur dosen utama dan pertemuan')
            ->assertSee('value="HTML dan Struktur Halaman"', false);
    }

    public function test_admin_attendance_monitoring_filters_course_and_class_without_edit_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $course = MataKuliah::create(['kode_mk' => 'IF5010', 'nama_mk' => 'Pemrograman Berbasis Web']);
        $otherCourse = MataKuliah::create(['kode_mk' => 'IF5011', 'nama_mk' => 'Basis Data']);
        $firstClass = Kelas::create(['kode_kelas' => '04SIFE010']);
        $secondClass = Kelas::create(['kode_kelas' => '04SIFE011']);
        $firstSchedule = $this->createJadwal($lecturer, $course, $firstClass);
        $secondSchedule = $this->createJadwal($lecturer, $course, $secondClass);
        $otherSchedule = $this->createJadwal($lecturer, $otherCourse, $firstClass);
        $meeting = $this->createPertemuan($firstSchedule, 1, 'Pengenalan Web');
        $meeting->update(['status_pertemuan' => 'Berlangsung']);
        $this->createPertemuan($secondSchedule, 1, 'Pertemuan Kelas Lain');
        $this->createPertemuan($otherSchedule, 1, 'Pertemuan Basis Data');
        $studentPresent = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700901',
            'kelas_id' => $firstClass->id,
        ]);
        User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700902',
            'kelas_id' => $firstClass->id,
        ]);
        Presensi::create([
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $studentPresent->id,
            'status' => 'Hadir',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance', [
                'mata_kuliah' => $course->id,
                'jadwal' => $firstSchedule->id,
            ]))
            ->assertOk()
            ->assertSeeText('Pemrograman Berbasis Web')
            ->assertSeeText('04SIFE010')
            ->assertSeeText('04SIFE011')
            ->assertSeeText('Pengenalan Web')
            ->assertSeeText('Berlangsung')
            ->assertSeeText('1 / 2')
            ->assertDontSeeText('Pertemuan Kelas Lain')
            ->assertDontSeeText('Pertemuan Basis Data')
            ->assertDontSee('name="status_pertemuan"', false)
            ->assertDontSee(route('admin.meetings.update', $meeting), false);
    }

    public function test_dosen_controls_meeting_lifecycle_and_completed_attendance_is_locked(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700801',
        ]);
        $mataKuliah = MataKuliah::create(['kode_mk' => 'IF5007', 'nama_mk' => 'Sistem Terdistribusi']);
        $kelas = Kelas::create(['kode_kelas' => '04SIFE008']);
        $mahasiswa->update(['kelas_id' => $kelas->id]);
        $jadwal = $this->createJadwal($dosen, $mataKuliah, $kelas);
        $meeting = $this->createPertemuan($jadwal, 1, 'Pengenalan Sistem Terdistribusi');
        $meeting->update([
            'status_pertemuan' => 'Terjadwal',
            'is_active' => false,
            'qr_expires_at' => now(),
        ]);
        $presensi = Presensi::create([
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $mahasiswa->id,
            'status' => 'Tidak Hadir',
        ]);

        $this->actingAs($mahasiswa, 'mahasiswa')
            ->postJson(route('mahasiswa.presensi.store'), [
                'qr_token' => $meeting->qr_token,
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
            ])
            ->assertStatus(409)
            ->assertJsonPath('status', 'error');

        $this->actingAs($dosen, 'dosen')
            ->post(route('dosen.pertemuan.start', $meeting))
            ->assertRedirect(route('dosen.pertemuan.qr', $meeting));
        $this->assertDatabaseHas('pertemuan', [
            'id' => $meeting->id,
            'status_pertemuan' => 'Berlangsung',
            'is_active' => true,
        ]);
        $this->post(route('dosen.pertemuan.manual_attendance', [$meeting, $presensi]), [
            'status' => 'Hadir',
        ])->assertRedirect();
        $this->assertDatabaseHas('presensi', ['id' => $presensi->id, 'status' => 'Hadir']);

        $this->post(route('dosen.pertemuan.end', $meeting))
            ->assertRedirect(route('dosen.pertemuan.qr', $meeting));
        $this->assertDatabaseHas('pertemuan', [
            'id' => $meeting->id,
            'status_pertemuan' => 'Selesai',
            'is_active' => false,
        ]);
        $this->get(route('dosen.pertemuan.qr', $meeting))
            ->assertOk()
            ->assertSeeText('Read-only')
            ->assertDontSee('name="status"', false)
            ->assertDontSee('id="qrcodeCanvas"', false);
        $this->post(route('dosen.pertemuan.manual_attendance', [$meeting, $presensi]), [
            'status' => 'Tidak Hadir',
        ])->assertStatus(409);
        $this->actingAs($mahasiswa, 'mahasiswa')
            ->postJson(route('mahasiswa.presensi.store'), [
                'qr_token' => $meeting->fresh()->qr_token,
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
            ])
            ->assertStatus(409);
        $this->assertDatabaseHas('presensi', ['id' => $presensi->id, 'status' => 'Hadir']);
    }

    public function test_substitute_lecturer_can_manage_only_the_assigned_meeting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mainLecturer = User::factory()->create(['role' => 'dosen']);
        $substitute = User::factory()->create(['role' => 'dosen']);
        $course = MataKuliah::create(['kode_mk' => 'IF5008', 'nama_mk' => 'Interaksi Manusia Komputer']);
        $class = Kelas::create(['kode_kelas' => '04SIFE009']);
        $schedule = $this->createJadwal($mainLecturer, $course, $class);
        $assignedMeeting = $this->createPertemuan($schedule, 1, 'Prinsip Usability');
        $otherMeeting = $this->createPertemuan($schedule, 2, 'Evaluasi Antarmuka');
        $assignedMeeting->update([
            'status_pertemuan' => 'Terjadwal',
            'is_active' => false,
            'qr_expires_at' => now(),
        ]);
        $otherMeeting->update([
            'status_pertemuan' => 'Terjadwal',
            'is_active' => false,
            'qr_expires_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.meetings.update-substitute', $assignedMeeting), [
                'dosen_pengganti_id' => $substitute->id,
            ])
            ->assertRedirect(route('admin.courses'));
        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $schedule->id,
            'dosen_id' => $mainLecturer->id,
        ]);
        $this->assertDatabaseHas('pertemuan', [
            'id' => $assignedMeeting->id,
            'dosen_pengganti_id' => $substitute->id,
        ]);
        $this->assertDatabaseHas('pertemuan', [
            'id' => $otherMeeting->id,
            'dosen_pengganti_id' => null,
        ]);

        $this->actingAs($substitute, 'dosen')
            ->get(route('dosen.dashboard', ['mata_kuliah' => $course->id, 'jadwal' => $schedule->id]))
            ->assertOk()
            ->assertSeeText('Dosen Pengganti')
            ->assertSeeText('Prinsip Usability')
            ->assertDontSeeText('Evaluasi Antarmuka');
        $this->post(route('dosen.pertemuan.start', $assignedMeeting))
            ->assertRedirect(route('dosen.pertemuan.qr', $assignedMeeting));
        $this->get(route('dosen.pertemuan.qr', $otherMeeting))->assertForbidden();
    }

    public function test_admin_can_manage_class_codes_and_assign_a_student_to_a_class(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '241011700501',
        ]);
        $mataKuliah = MataKuliah::create(['kode_mk' => 'IF5006', 'nama_mk' => 'Sistem Informasi']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.classes.store'), ['kode_kelas' => '04SIFE001'])
            ->assertRedirect(route('admin.courses'));

        $kelas = Kelas::query()->firstOrFail();
        $jadwal = $this->createJadwal($dosen, $mataKuliah, $kelas);

        $this->get(route('admin.courses'))
            ->assertSeeText('Data Kelas')
            ->assertSee('value="04SIFE001"', false);

        $this->patch(route('admin.classes.update', $kelas), ['kode_kelas' => '04SIFE009'])
            ->assertRedirect(route('admin.courses'));

        $this->patch(route('admin.users.update-class', $mahasiswa), ['kelas_id' => $kelas->id])
            ->assertRedirect(route('admin.users.show', $mahasiswa));

        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'kode_kelas' => '04SIFE009',
        ]);
        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $jadwal->id,
            'kelas' => '04SIFE009',
            'kelas_id' => $kelas->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $mahasiswa->id,
            'kelas_id' => $kelas->id,
        ]);
    }

    public function test_database_seeder_creates_course_class_meetings_and_realistic_attendance(): void
    {
        $existingLecturer = User::factory()->create([
            'email' => 'andri@unpam.ac.id',
            'name' => 'Dr. Ir. Andri, M.T.',
            'role' => 'dosen',
            'nomor_induk' => '198005122005011002',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('mata_kuliah', 4);
        $this->assertDatabaseCount('jadwal_kuliah', 8);
        $this->assertDatabaseCount('pertemuan', 80);
        $this->assertDatabaseCount('kelas', 2);
        $this->assertDatabaseCount('users', 31);
        $this->assertDatabaseHas('users', [
            'id' => $existingLecturer->id,
            'email' => 'dosen@kampus.ac.id',
            'name' => 'Dr. Ir. Hendra Wijaya, M.T.',
            'role' => 'dosen',
        ]);

        $this->assertDatabaseHas('pertemuan', [
            'pertemuan_ke' => 3,
            'topik' => 'CSS dan Tata Letak',
        ]);
        $this->assertSame(
            '2026-10-15',
            Pertemuan::query()->where('pertemuan_ke', 3)->where('topik', 'CSS dan Tata Letak')->firstOrFail()->tanggal_pertemuan->toDateString()
        );
        $this->assertSame(
            15,
            Kelas::query()->where('kode_kelas', '04SIFE001')->firstOrFail()->mahasiswa()->count()
        );
        $this->assertSame(
            14,
            Kelas::query()->where('kode_kelas', '04SIFE002')->firstOrFail()->mahasiswa()->count()
        );
        $this->assertGreaterThan(0, Presensi::query()->where('status', 'Tidak Hadir')->count());
        $this->assertGreaterThan(0, Presensi::query()->where('status', 'Hadir')->count());

        $studentPasswords = [
            '241011700101' => 'Andi@11',
            '241011700102' => 'Sitiaulia@11',
            '241011700103' => 'Rizky@11',
            '241011700104' => 'Nabila@11',
            '241011700105' => 'Fajar@11',
            '241011700106' => 'Dinda@11',
            '241011700107' => 'Reza@11',
            '241011700108' => 'Alya@11',
            '220101001' => 'Budi@11',
            '220101002' => 'Sitinurhaliza@11',
            '220101003' => 'Dimasprasetyo@11',
            '220101004' => 'Rina@11',
            '220101005' => 'Ahmad@11',
            '220101006' => 'Dewi@11',
            '241011700486' => 'Willy@11',
            '241011700201' => 'Raka@11',
            '241011700202' => 'Citra@11',
            '241011700203' => 'Farhan@11',
            '241011700204' => 'Nadya@11',
            '241011700205' => 'Bagas@11',
            '241011700206' => 'Tiara@11',
            '241011700207' => 'Dimas@11',
            '241011700208' => 'Salsa@11',
            '220101007' => 'Rizkyramadhan@11',
            '220101008' => 'Nabilaputri@11',
            '220101009' => 'Fajarnugroho@11',
            '220101010' => 'Tiaramaharani@11',
            '220101011' => 'Kevin@11',
            '220101012' => 'Anisa@11',
        ];

        foreach ($studentPasswords as $nim => $password) {
            $student = User::query()->where('nomor_induk', $nim)->firstOrFail();

            $this->assertTrue(Hash::check($password, $student->password), "Seeded password for {$nim} does not match.");
            $this->assertFalse(Hash::check('password', $student->password), "Seeded password for {$nim} must not be the shared default.");
        }

        $tokens = Pertemuan::query()->pluck('qr_token');
        $this->assertCount(80, $tokens->unique());
    }

    public function test_database_seeder_merges_duplicate_course_code_without_losing_legacy_attendance(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198005122005011002',
        ]);
        $kelas04 = Kelas::create(['kode_kelas' => '04SIFE002']);
        $kelas05 = Kelas::create(['kode_kelas' => '05SIFE002']);
        $legacyStudent = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '990000001',
            'kelas_id' => $kelas05->id,
        ]);
        $legacyCourse = MataKuliah::create([
            'kode_mk' => '01',
            'nama_mk' => 'Pemrograman Web',
        ]);
        $duplicateSchedule = $this->createJadwal($lecturer, $legacyCourse, $kelas04);
        $duplicateSchedule->update([
            'hari' => 'Sabtu',
            'jam_mulai' => '20:00',
            'jam_selesai' => '20:20',
        ]);
        $additionalSchedule = $this->createJadwal($lecturer, $legacyCourse, $kelas05);
        $legacyMeeting = $this->createPertemuan($duplicateSchedule, 1, 'Materi kuliah lama');
        $duplicateMeeting = $this->createPertemuan($duplicateSchedule, 2, 'Materi duplikat');
        $legacyMeeting->update(['status_pertemuan' => 'Selesai']);
        $legacyAttendance = Presensi::create([
            'pertemuan_id' => $legacyMeeting->id,
            'mahasiswa_id' => $legacyStudent->id,
            'status' => 'Hadir',
            'waktu_presensi' => '2026-09-30 16:14:17',
            'latitude_mahasiswa' => -6.1754,
            'longitude_mahasiswa' => 106.8271,
            'jarak_meter' => 1.75,
        ]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $canonicalCourse = MataKuliah::query()->where('kode_mk', 'IF3101')->firstOrFail();
        $canonicalSchedule = JadwalKuliah::query()
            ->where('mata_kuliah_id', $canonicalCourse->id)
            ->where('kelas_id', $kelas04->id)
            ->firstOrFail();
        $canonicalMeeting = $canonicalSchedule->pertemuan()->where('pertemuan_ke', 1)->firstOrFail();

        $this->assertDatabaseMissing('mata_kuliah', ['kode_mk' => '01']);
        $this->assertSame(1, MataKuliah::query()->where('nama_mk', 'Pemrograman Web')->count());
        $this->assertDatabaseMissing('jadwal_kuliah', ['id' => $duplicateSchedule->id]);
        $this->assertDatabaseMissing('pertemuan', ['id' => $duplicateMeeting->id]);
        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $canonicalSchedule->id,
            'hari' => 'Selasa',
            'jam_mulai' => '08:00:00',
        ]);
        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $additionalSchedule->id,
            'mata_kuliah_id' => $canonicalCourse->id,
            'kelas_id' => $kelas05->id,
        ]);
        $this->assertDatabaseHas('presensi', [
            'id' => $legacyAttendance->id,
            'pertemuan_id' => $canonicalMeeting->id,
            'mahasiswa_id' => $legacyStudent->id,
            'status' => 'Hadir',
            'waktu_presensi' => '2026-09-30 16:14:17',
            'jarak_meter' => 1.75,
        ]);
    }

    private function createJadwal(User $dosen, MataKuliah $mataKuliah, Kelas $kelas): JadwalKuliah
    {
        return JadwalKuliah::create([
            'dosen_id' => $dosen->id,
            'mata_kuliah_id' => $mataKuliah->id,
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->kode_kelas,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'latitude_kelas' => -6.1753924,
            'longitude_kelas' => 106.8271528,
            'radius_meter' => JadwalKuliah::MAX_RADIUS_METERS,
        ]);
    }

    private function createPertemuan(JadwalKuliah $jadwal, int $number, string $topic): Pertemuan
    {
        return Pertemuan::create([
            'jadwal_kuliah_id' => $jadwal->id,
            'pertemuan_ke' => $number,
            'topik' => $topic,
            'tanggal_pertemuan' => '2026-10-15',
            'status_pertemuan' => 'Berlangsung',
            'qr_token' => Str::random(40),
            'qr_expires_at' => now()->addMinutes(20),
            'is_active' => true,
        ]);
    }
}
