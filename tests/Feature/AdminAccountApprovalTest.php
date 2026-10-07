<?php

namespace Tests\Feature;

use App\Models\JadwalKuliah;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_dosen_and_mahasiswa_wait_for_approval_before_login(): void
    {
        foreach (['dosen', 'mahasiswa'] as $role) {
            $email = "{$role}@kampus.test";
            $nomorInduk = Str::random(10);
            $response = $this->post(route('register.store', $role), [
                'name' => 'Akun '.ucfirst($role),
                'email' => $email,
                'nomor_induk' => $nomorInduk,
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
            ]);

            $response->assertRedirect(route('login.role', $role));
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('users', [
                'email' => $email,
                'role' => $role,
                'is_approved' => false,
            ]);
            $this->assertGuest($role);

            $this->post(route('login.post', $role), [
                'nomor_induk' => $nomorInduk,
                'password' => 'password-123',
            ])->assertSessionHasErrors('nomor_induk');

            $this->assertGuest($role);
        }
    }

    public function test_dosen_and_mahasiswa_can_log_in_with_their_nomor_induk(): void
    {
        foreach ([
            'dosen' => '198501005',
            'mahasiswa' => '220101006',
        ] as $role => $nomorInduk) {
            $user = User::factory()->create([
                'role' => $role,
                'nomor_induk' => $nomorInduk,
                'password' => 'password-123',
            ]);

            $this->post(route('login.post', $role), [
                'nomor_induk' => $nomorInduk,
                'password' => 'password-123',
            ])->assertRedirect(route($role.'.dashboard'));

            $this->assertAuthenticatedAs($user, $role);
            $this->post(route('logout'));
        }
    }

    public function test_login_form_uses_role_specific_account_identifiers(): void
    {
        $this->get(route('login.role', 'dosen'))
            ->assertOk()
            ->assertSee('NID / NIP')
            ->assertSee('name="nomor_induk"', false);

        $this->get(route('login.role', 'mahasiswa'))
            ->assertOk()
            ->assertSee('NIM')
            ->assertSee('name="nomor_induk"', false);

        $this->get(route('login.role', 'admin'))
            ->assertOk()
            ->assertSee('Email Admin')
            ->assertSee('name="email"', false);
    }

    public function test_api_registration_returns_pending_status_for_student(): void
    {
        $response = $this->postJson('/api/v1/register/mahasiswa', [
            'name' => 'Mahasiswa API',
            'email' => 'api-mahasiswa@kampus.test',
            'nomor_induk' => '220101001',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending_approval')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'email' => 'api-mahasiswa@kampus.test',
            'role' => 'mahasiswa',
            'is_approved' => false,
        ]);
    }

    public function test_admin_can_view_and_approve_dosen_and_mahasiswa_accounts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'nomor_induk' => 'ADM-001',
        ]);
        $dosen = $this->createPendingAccount('dosen', 'pending-dosen@kampus.test', '198501001');
        $mahasiswa = $this->createPendingAccount('mahasiswa', 'pending-mahasiswa@kampus.test', '220101002');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($dosen->email)
            ->assertSee($mahasiswa->email);

        foreach ([$dosen, $mahasiswa] as $user) {
            $this->get(route('admin.users.show', $user))
                ->assertOk()
                ->assertSee($user->email)
                ->assertSee($user->nomor_induk);

            $this->patch(route('admin.users.approve', $user))
                ->assertRedirect(route('admin.users.show', $user));

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'is_approved' => true,
            ]);
        }
    }

    public function test_admin_dashboard_lists_administrative_changes_without_attendance_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->post(route('register.store', 'mahasiswa'), [
            'name' => 'Mahasiswa Audit',
            'email' => 'audit-student@kampus.test',
            'nomor_induk' => '220109101',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('login.role', 'mahasiswa'));
        $this->post(route('register.store', 'dosen'), [
            'name' => 'Dosen Audit',
            'email' => 'audit-lecturer@kampus.test',
            'nomor_induk' => '198509101',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('login.role', 'dosen'));

        $student = User::query()->where('nomor_induk', '220109101')->firstOrFail();
        $lecturer = User::query()->where('nomor_induk', '198509101')->firstOrFail();
        $replacementMain = User::factory()->create(['role' => 'dosen']);
        $replacementMain->forceFill(['is_approved' => true])->save();
        $course = MataKuliah::create(['kode_mk' => 'IF9901', 'nama_mk' => 'Mata Kuliah Audit']);
        $this->actingAs($admin, 'admin');
        $this->patch(route('admin.users.approve', $student))->assertRedirect();
        $this->patch(route('admin.users.approve', $lecturer))->assertRedirect();
        $this->post(route('admin.courses.store'), [
            'kode_mk' => 'IF9902',
            'nama_mk' => 'Administrasi Akademik',
        ])->assertRedirect(route('admin.courses'));
        $this->post(route('admin.classes.store'), ['kode_kelas' => 'AUDIT-01'])
            ->assertRedirect(route('admin.courses'));

        $schedule = JadwalKuliah::create([
            'dosen_id' => $lecturer->id,
            'mata_kuliah_id' => $course->id,
            'kelas' => 'AUDIT-01',
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'latitude_kelas' => -6.2,
            'longitude_kelas' => 106.8,
            'radius_meter' => 5,
        ]);
        $meeting = Pertemuan::create([
            'jadwal_kuliah_id' => $schedule->id,
            'pertemuan_ke' => 1,
            'topik' => 'Sesi Presensi Audit',
            'tanggal_pertemuan' => '2026-10-07',
            'status_pertemuan' => 'Berlangsung',
            'qr_token' => Str::random(40),
            'qr_expires_at' => now()->addMinutes(20),
            'is_active' => true,
        ]);
        Presensi::create([
            'pertemuan_id' => $meeting->id,
            'mahasiswa_id' => $student->id,
            'status' => 'Hadir',
        ]);
        $this->patch(route('admin.schedules.update-lecturer', $schedule), ['dosen_id' => $replacementMain->id])
            ->assertRedirect(route('admin.courses'));

        $this->assertDatabaseCount('admin_activities', 7);
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Aktivitas Terbaru')
            ->assertSeeText('Mahasiswa ditambahkan: Mahasiswa Audit')
            ->assertSeeText('Dosen ditambahkan: Dosen Audit')
            ->assertSeeText('Akun Mahasiswa disetujui')
            ->assertSeeText('Akun Dosen disetujui')
            ->assertSeeText('Mata kuliah ditambahkan: Administrasi Akademik')
            ->assertSeeText('Kelas ditambahkan: AUDIT-01')
            ->assertSeeText('Dosen pengampu Mata Kuliah Audit')
            ->assertDontSeeText('Sesi Presensi Audit');
    }

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101003',
        ]);

        $this->actingAs($mahasiswa, 'mahasiswa')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_pages_are_not_cached_and_require_authentication_after_logout(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'nomor_induk' => 'ADM-CACHE',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control', ''));

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_each_role_can_render_its_dashboard_and_profile_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'nomor_induk' => 'ADM-003',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Admin');

        $this->get(route('admin.users.index'))->assertOk()->assertSee('Data Dosen');
        $this->get(route('admin.courses'))->assertOk()->assertSee('Mata Kuliah');
        $this->get(route('admin.attendance'))->assertOk()->assertSee('Rekap Presensi');
        $this->get(route('admin.profile'))->assertOk()->assertSee($admin->email);

        $dosen = User::factory()->create([
            'role' => 'dosen',
            'nomor_induk' => '198501003',
        ]);

        $this->actingAs($dosen, 'dosen')
            ->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Dosen');

        $this->get(route('dosen.profile'))->assertOk()->assertSee($dosen->email);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101005',
        ]);

        $this->actingAs($mahasiswa, 'mahasiswa')
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Mahasiswa');

        $this->get(route('mahasiswa.scan'))->assertOk()->assertSee('Scan Presensi');
        $this->get(route('mahasiswa.riwayat'))->assertOk()->assertSee('Riwayat Presensi');
        $this->get(route('mahasiswa.profile'))->assertOk()->assertSee($mahasiswa->email);
    }

    public function test_admin_role_cannot_be_registered_without_bootstrap_key(): void
    {
        $this->get('/register/admin')->assertNotFound();

        config()->set('auth.admin_registration_key', 'test-admin-bootstrap-key');

        $this->postJson('/api/v1/register/admin', [
            'name' => 'Unauthorized Admin',
            'email' => 'unauthorized-admin@kampus.test',
            'nomor_induk' => 'ADM-UNAUTH',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized-admin@kampus.test']);
    }

    public function test_admin_bootstrap_registration_is_disabled_without_configured_key(): void
    {
        config()->set('auth.admin_registration_key', null);

        $this->postJson('/api/v1/register/admin', [
            'name' => 'Unconfigured Admin',
            'email' => 'unconfigured-admin@kampus.test',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ], [
            'X-Admin-Registration-Key' => 'any-key',
        ])->assertStatus(503);

        $this->assertDatabaseMissing('users', ['email' => 'unconfigured-admin@kampus.test']);
    }

    public function test_admin_bootstrap_registration_requires_secret_and_only_allows_first_admin(): void
    {
        config()->set('auth.admin_registration_key', 'test-admin-bootstrap-key');

        $payload = [
            'name' => 'Bootstrap Admin',
            'email' => 'bootstrap-admin@kampus.test',
            'nomor_induk' => 'ADM-BOOTSTRAP',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ];

        $this->postJson('/api/v1/register/admin', $payload)
            ->assertForbidden();

        $this->postJson('/api/v1/register/admin', $payload, [
            'X-Admin-Registration-Key' => 'wrong-key',
        ])->assertForbidden();

        $this->postJson('/api/v1/register/admin', $payload, [
            'X-Admin-Registration-Key' => 'test-admin-bootstrap-key',
        ])->assertCreated()
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'email' => 'bootstrap-admin@kampus.test',
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $this->postJson('/api/v1/register/admin', [
            ...$payload,
            'email' => 'second-admin@kampus.test',
            'nomor_induk' => 'ADM-SECOND',
        ], [
            'X-Admin-Registration-Key' => 'test-admin-bootstrap-key',
        ])->assertConflict();

        $this->assertDatabaseMissing('users', ['email' => 'second-admin@kampus.test']);
    }

    public function test_admin_api_login_issues_limited_token_and_logout_revokes_it(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'api-admin@kampus.test',
            'nomor_induk' => 'ADM-API',
            'password' => 'password-123',
        ]);

        $response = $this->postJson('/api/v1/login/admin', [
            'email' => $admin->email,
            'password' => 'password-123',
            'device_name' => 'Postman local',
        ])->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonMissingPath('data.user.password');

        $token = $response->json('data.access_token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/v1/admin/me', $headers)
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email);

        $this->postJson('/api/v1/admin/logout', [], $headers)
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'name' => 'Postman local',
        ]);

        Auth::forgetGuards();

        $this->getJson('/api/v1/admin/me', $headers)
            ->assertUnauthorized();
    }

    public function test_admin_api_login_rejects_non_admin_accounts(): void
    {
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
            'email' => 'api-mahasiswa-login@kampus.test',
            'nomor_induk' => '220101099',
            'password' => 'password-123',
        ]);

        $this->postJson('/api/v1/login/admin', [
            'email' => $mahasiswa->email,
            'password' => 'password-123',
        ])->assertUnauthorized();
    }

    public function test_admin_can_log_in_with_admin_role(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@kampus.test',
            'nomor_induk' => 'ADM-002',
            'password' => 'password-123',
        ]);

        $this->post(route('login.post', 'admin'), [
            'email' => $admin->email,
            'password' => 'password-123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_cli_command_can_promote_an_existing_account_to_admin(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
            'email' => 'new-admin@kampus.test',
            'nomor_induk' => '220101004',
        ]);

        $this->artisan('users:make-admin', ['email' => $user->email])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
            'is_approved' => true,
        ]);
    }

    private function createPendingAccount(string $role, string $email, string $nomorInduk): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'email' => $email,
            'nomor_induk' => $nomorInduk,
        ]);
        $user->is_approved = false;
        $user->save();

        return $user;
    }
}
