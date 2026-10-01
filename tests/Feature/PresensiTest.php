<?php

namespace Tests\Feature;

use App\Models\JadwalKuliah;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
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
            'qr_token' => Str::random(40),
            'qr_expires_at' => Carbon::now()->addMinutes(20),
            'is_active' => true,
        ]);
    }

    public function test_mahasiswa_berhasil_presensi_didalam_radius(): void
    {
        $response = $this->actingAs($this->mahasiswa, 'mahasiswa')->postJson(route('mahasiswa.presensi.store'), [
            'qr_token' => $this->pertemuan->qr_token,
            'latitude' => -6.17545000,
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
                'remaining_seconds' => 1200,
            ]);

        $this->pertemuan->refresh();
        $this->assertTrue($this->pertemuan->qr_expires_at->isFuture());
    }

    public function test_mahasiswa_tidak_dapat_mengakses_halaman_dosen(): void
    {
        $response = $this->actingAs($this->mahasiswa, 'mahasiswa')->get(route('dosen.dashboard'));
        $response->assertRedirect(route('login'));
    }
}
