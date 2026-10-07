<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_one_combined_account_identifier_without_role_tabs(): void
    {
        $this->get(route('login'))
            ->assertSee('Masuk ke akun Anda')
            ->assertSee('Email / NIM / NID / NIP')
            ->assertSee('name="identifier"', false)
            ->assertDontSee('Pilih role');
    }

    public function test_registration_requires_role_selection_before_showing_a_role_specific_form(): void
    {
        $this->get(route('register'))
            ->assertSee(route('register.role', 'mahasiswa'))
            ->assertSee(route('register.role', 'dosen'))
            ->assertDontSee('name="password"', false);

        $this->get(route('register.role', 'mahasiswa'))
            ->assertSee('Daftar Mahasiswa')
            ->assertSee('NIM')
            ->assertSee(route('register.store', 'mahasiswa'))
            ->assertDontSee('NID / NIP');

        $this->get(route('register.role', 'dosen'))
            ->assertSee('Daftar Dosen')
            ->assertSee('NID / NIP')
            ->assertSee(route('register.store', 'dosen'))
            ->assertDontSee('Daftar Mahasiswa');
    }

    public function test_login_automatically_authenticates_email_and_nomor_induk_for_each_role(): void
    {
        foreach ([
            'admin' => ['email' => 'admin@kampus.test', 'nomor_induk' => 'ADM-001'],
            'dosen' => ['email' => 'dosen@kampus.test', 'nomor_induk' => '198501001'],
            'mahasiswa' => ['email' => 'mahasiswa@kampus.test', 'nomor_induk' => '220101001'],
        ] as $role => $identity) {
            $user = User::factory()->create([
                ...$identity,
                'role' => $role,
                'is_approved' => true,
                'password' => 'password-123',
            ]);
            foreach ([$identity['email'], $identity['nomor_induk']] as $identifier) {
                $this->post(route('login.authenticate'), [
                    'identifier' => $identifier,
                    'password' => 'password-123',
                ])->assertRedirect(route($role.'.dashboard'));

                $this->assertAuthenticatedAs($user, $role);
                $this->post(route('logout'))->assertRedirect(route('login'));
            }
        }
    }

    public function test_login_shows_pending_approval_for_a_matching_unapproved_account(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@kampus.test',
            'nomor_induk' => '220101009',
            'role' => 'mahasiswa',
            'is_approved' => false,
            'password' => 'password-123',
        ]);

        $this->post(route('login.authenticate'), [
            'identifier' => $user->nomor_induk,
            'password' => 'password-123',
        ])
            ->assertSessionHasErrors([
                'identifier' => 'Akun Mahasiswa Anda masih menunggu persetujuan admin.',
            ]);

        $this->assertGuest('mahasiswa');
    }
}
