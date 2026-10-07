<?php

namespace Tests\Feature;

use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PresensiTest extends TestCase
{
    use RefreshDatabase;

    protected $dosen;

    protected $mahasiswa;

    protected $mataKuliah;

    protected $jadwal;

    protected $pertemuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501012010121001',
        ]);

        $this->mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101001',
        ]);

        $this->mataKuliah = MataKuliah::create([
            'kode_mk' => 'IF101',
            'nama_mk' => 'Pemrograman Web',
        ]);

        $this->jadwal = JadwalKuliah::create([
            'dosen_id' => $this->dosen->id,
            'mata_kuliah_id' => $this->mataKuliah->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'latitude_kelas' => -6.17539240,
            'longitude_kelas' => 106.82715280,
            'radius_meter' => 50,
        ]);

        $this->pertemuan = Pertemuan::create([
            'jadwal_kuliah_id' => $this->jadwal->id,
            'pertemuan_ke' => 1,
            'status_pertemuan' => 'Berlangsung',
            'qr_token' => Str::random(40),
            'qr_expires_at' => Carbon::now()->addMinutes(20),
            'is_active' => true,
        ]);
    }

    public function test_mahasiswa_berhasil_presensi_didalam_radius(): void
    {
        $response = $this->actingAs($this->mahasiswa, 'mahasiswa')->postJson(route('mahasiswa.presensi.store'), [
            'qr_token' => $this->pertemuan->qr_token,
            'latitude' => -6.17543730,
            'longitude' => 106.82715280,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('presensi', [
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Hadir',
        ]);
    }

    public function test_mahasiswa_gagal_presensi_jika_diluar_radius(): void
    {
        $response = $this->actingAs($this->mahasiswa, 'mahasiswa')->postJson(route('mahasiswa.presensi.store'), [
            'qr_token' => $this->pertemuan->qr_token,
            'latitude' => -6.917464,
            'longitude' => 107.619123,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_mahasiswa_mendapat_422_di_luar_lima_meter_meski_radius_jadwal_lama_lebih_besar(): void
    {
        $response = $this->actingAs($this->mahasiswa, 'mahasiswa')->postJson(route('mahasiswa.presensi.store'), [
            'qr_token' => $this->pertemuan->qr_token,
            'latitude' => -6.17545000,
            'longitude' => 106.82715280,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ])
            ->assertJsonFragment([
                'message' => 'Presensi ditolak! Posisi Anda berada di luar radius kelas. Jarak Anda: 6.4 meter (Batas radius izin: 5.0 meter).',
            ]);

        $this->assertDatabaseMissing('presensi', [
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
        ]);
    }

    public function test_dosen_tidak_dapat_mengatur_radius_di_atas_lima_meter(): void
    {
        $response = $this->actingAs($this->dosen, 'dosen')
            ->postJson(route('dosen.jadwal.update_lokasi', $this->jadwal), [
                'radius' => JadwalKuliah::MAX_RADIUS_METERS + 1,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('radius');

        $this->assertDatabaseHas('jadwal_kuliah', [
            'id' => $this->jadwal->id,
            'radius_meter' => 50,
        ]);
    }

    public function test_presensi_gagal_jika_qr_code_kedaluwarsa(): void
    {
        $this->pertemuan->update([
            'qr_expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $response = $this->actingAs($this->mahasiswa, 'mahasiswa')->postJson(route('mahasiswa.presensi.store'), [
            'qr_token' => $this->pertemuan->qr_token,
            'latitude' => -6.17539240,
            'longitude' => 106.82715280,
        ]);

        $response->assertStatus(410)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_dosen_bisa_regenerate_qr_code_20_menit(): void
    {
        $response = $this->actingAs($this->dosen, 'dosen')->postJson(route('dosen.pertemuan.regenerate_qr', $this->pertemuan->id));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertLessThanOrEqual(1200, $response->json('remaining_seconds'));
        $this->assertGreaterThan(0, $response->json('remaining_seconds'));
        $this->pertemuan->refresh();
        $this->assertTrue($this->pertemuan->qr_expires_at->isFuture());
    }

    public function test_automatic_qr_refresh_keeps_the_original_twenty_minute_expiry(): void
    {
        $originalExpiry = $this->pertemuan->qr_expires_at;
        $originalToken = $this->pertemuan->qr_token;

        $response = $this->actingAs($this->dosen, 'dosen')
            ->postJson(route('dosen.pertemuan.regenerate_qr', $this->pertemuan), [
                'auto_refresh' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('qr_expires_at', $originalExpiry->toIso8601String());

        $this->pertemuan->refresh();
        $this->assertSame($originalExpiry->toDateTimeString(), $this->pertemuan->qr_expires_at->toDateTimeString());
        $this->assertNotSame($originalToken, $this->pertemuan->qr_token);
    }

    public function test_automatic_qr_refresh_cannot_extend_an_expired_session(): void
    {
        $this->pertemuan->update([
            'qr_expires_at' => Carbon::now()->subMinute(),
        ]);
        $expiredToken = $this->pertemuan->qr_token;

        $response = $this->actingAs($this->dosen, 'dosen')
            ->postJson(route('dosen.pertemuan.regenerate_qr', $this->pertemuan), [
                'auto_refresh' => true,
            ]);

        $response->assertGone()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('remaining_seconds', 0);

        $this->pertemuan->refresh();
        $this->assertSame($expiredToken, $this->pertemuan->qr_token);
        $this->assertTrue($this->pertemuan->qr_expires_at->isPast());
    }

    public function test_dosen_can_render_session_filters_meeting_pills_and_projection_qr(): void
    {
        $presensi = Presensi::create([
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Tidak Hadir',
        ]);

        $response = $this->actingAs($this->dosen, 'dosen')
            ->get(route('dosen.pertemuan.qr', $this->pertemuan));

        $response->assertOk()
            ->assertSee('Mata Kuliah')
            ->assertSee('Kelas')
            ->assertSee('Buka Layar QR Code')
            ->assertSee('P1')
            ->assertSee('P16')
            ->assertSee('Batas Validasi Jarak: 5 Meter')
            ->assertSee($this->mahasiswa->nomor_induk)
            ->assertSee($this->mahasiswa->name)
            ->assertSee('Tidak Hadir')
            ->assertSee(route('dosen.pertemuan.manual_attendance', [$this->pertemuan, $presensi]), false);
    }

    public function test_dosen_can_override_manual_attendance_to_present(): void
    {
        $presensi = Presensi::create([
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Tidak Hadir',
        ]);

        $response = $this->actingAs($this->dosen, 'dosen')
            ->from(route('dosen.pertemuan.qr', $this->pertemuan))
            ->post(route('dosen.pertemuan.manual_attendance', [$this->pertemuan, $presensi]), [
                'status' => 'Hadir',
            ]);

        $response->assertRedirectBack();
        $this->assertDatabaseHas('presensi', [
            'id' => $presensi->id,
            'status' => 'Hadir',
        ]);
        $this->assertNotNull($presensi->fresh()->waktu_presensi);
    }

    public function test_dosen_can_override_manual_attendance_to_alpha(): void
    {
        $presensi = Presensi::create([
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Hadir',
            'waktu_presensi' => now(),
        ]);

        $response = $this->actingAs($this->dosen, 'dosen')
            ->from(route('dosen.pertemuan.qr', $this->pertemuan))
            ->post(route('dosen.pertemuan.manual_attendance', [$this->pertemuan, $presensi]), [
                'status' => 'Tidak Hadir',
            ]);

        $response->assertRedirectBack();
        $this->assertDatabaseHas('presensi', [
            'id' => $presensi->id,
            'status' => 'Tidak Hadir',
        ]);
    }

    public function test_dosen_cannot_override_attendance_for_another_meeting(): void
    {
        $otherMeeting = Pertemuan::create([
            'jadwal_kuliah_id' => $this->jadwal->id,
            'pertemuan_ke' => 2,
            'qr_token' => Str::random(40),
            'qr_expires_at' => Carbon::now()->addMinutes(20),
            'is_active' => true,
        ]);
        $presensi = Presensi::create([
            'pertemuan_id' => $otherMeeting->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Hadir',
        ]);

        $response = $this->actingAs($this->dosen, 'dosen')
            ->post(route('dosen.pertemuan.manual_attendance', [$this->pertemuan, $presensi]), [
                'status' => 'Tidak Hadir',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('presensi', [
            'id' => $presensi->id,
            'status' => 'Hadir',
        ]);
    }

    public function test_another_lecturer_cannot_override_attendance(): void
    {
        $anotherLecturer = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501019',
        ]);
        $presensi = Presensi::create([
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Hadir',
        ]);

        $response = $this->actingAs($anotherLecturer, 'dosen')
            ->post(route('dosen.pertemuan.manual_attendance', [$this->pertemuan, $presensi]), [
                'status' => 'Tidak Hadir',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('presensi', [
            'id' => $presensi->id,
            'status' => 'Hadir',
        ]);
    }

    public function test_dosen_cannot_override_manual_attendance_with_an_invalid_status(): void
    {
        $presensi = Presensi::create([
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Hadir',
        ]);

        $response = $this->actingAs($this->dosen, 'dosen')
            ->from(route('dosen.pertemuan.qr', $this->pertemuan))
            ->post(route('dosen.pertemuan.manual_attendance', [$this->pertemuan, $presensi]), [
                'status' => 'Izin',
            ]);

        $response->assertRedirectBackWithErrors(['status']);
        $this->assertDatabaseHas('presensi', [
            'id' => $presensi->id,
            'status' => 'Hadir',
        ]);
    }

    public function test_mahasiswa_tidak_dapat_mengakses_halaman_dosen(): void
    {
        $response = $this->actingAs($this->mahasiswa, 'mahasiswa')->get(route('dosen.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_mahasiswa_sees_attendance_history_grouped_by_course_with_derived_statuses(): void
    {
        $kelas = Kelas::create(['kode_kelas' => '04SIFE002']);
        $this->mahasiswa->update(['kelas_id' => $kelas->id]);
        $this->jadwal->update([
            'kelas_id' => $kelas->id,
            'kelas' => $kelas->kode_kelas,
        ]);
        $this->pertemuan->update([
            'pertemuan_ke' => 1,
            'topik' => 'Pengenalan Pemrograman Web',
            'tanggal_pertemuan' => '2026-10-07',
            'status_pertemuan' => 'Berlangsung',
        ]);

        Presensi::create([
            'pertemuan_id' => $this->pertemuan->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Hadir',
            'waktu_presensi' => Carbon::parse('2026-10-07 08:15:00'),
        ]);

        $meetingInProgress = Pertemuan::create([
            'jadwal_kuliah_id' => $this->jadwal->id,
            'pertemuan_ke' => 2,
            'topik' => 'HTML dan Struktur Halaman',
            'tanggal_pertemuan' => '2026-10-14',
            'status_pertemuan' => 'Berlangsung',
            'qr_token' => 'history-meeting-2-token',
            'qr_expires_at' => Carbon::now()->addMinutes(20),
            'is_active' => true,
        ]);

        Presensi::create([
            'pertemuan_id' => $meetingInProgress->id,
            'mahasiswa_id' => $this->mahasiswa->id,
            'status' => 'Tidak Hadir',
        ]);

        Pertemuan::create([
            'jadwal_kuliah_id' => $this->jadwal->id,
            'pertemuan_ke' => 3,
            'topik' => 'CSS dan Tata Letak',
            'tanggal_pertemuan' => '2026-10-21',
            'status_pertemuan' => 'Selesai',
            'qr_token' => 'history-meeting-3-token',
            'qr_expires_at' => Carbon::now()->subMinutes(20),
            'is_active' => false,
        ]);

        $kelasLain = Kelas::create(['kode_kelas' => '04SIFE003']);
        $mataKuliahLain = MataKuliah::create([
            'kode_mk' => 'IF102',
            'nama_mk' => 'Basis Data',
        ]);
        $jadwalKelasLain = JadwalKuliah::create([
            'dosen_id' => $this->dosen->id,
            'mata_kuliah_id' => $mataKuliahLain->id,
            'kelas_id' => $kelasLain->id,
            'kelas' => $kelasLain->kode_kelas,
            'hari' => 'Selasa',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'latitude_kelas' => -6.17539240,
            'longitude_kelas' => 106.82715280,
            'radius_meter' => 50,
        ]);
        Pertemuan::create([
            'jadwal_kuliah_id' => $jadwalKelasLain->id,
            'pertemuan_ke' => 1,
            'topik' => 'Konsep Dasar Basis Data',
            'tanggal_pertemuan' => '2026-10-07',
            'status_pertemuan' => 'Terjadwal',
            'qr_token' => 'history-other-class-token',
            'qr_expires_at' => Carbon::now()->addMinutes(20),
            'is_active' => false,
        ]);

        $this->actingAs($this->mahasiswa, 'mahasiswa')
            ->get(route('mahasiswa.riwayat'))
            ->assertOk()
            ->assertSeeText('Pemrograman Web')
            ->assertSeeText('IF101')
            ->assertSeeText('Kelas 04SIFE002')
            ->assertSeeText('Pengenalan Pemrograman Web')
            ->assertSeeText('HTML dan Struktur Halaman')
            ->assertSeeText('CSS dan Tata Letak')
            ->assertSeeText('1 Hadir')
            ->assertSeeText('1 Tidak Hadir')
            ->assertSeeText('1 Menunggu')
            ->assertDontSeeText('Basis Data')
            ->assertDontSeeText('Jarak ke Kelas')
            ->assertDontSeeText('Aktivitas Terbaru');
    }
}
