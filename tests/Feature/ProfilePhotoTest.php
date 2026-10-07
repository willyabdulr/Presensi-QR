<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_profile_displays_assigned_class(): void
    {
        $kelas = Kelas::factory()->create(['kode_kelas' => '04SIFE002']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'kelas_id' => $kelas->id,
            'nomor_induk' => '220101103',
        ]);

        $this->actingAs($student, 'mahasiswa')
            ->get(route('mahasiswa.profile'))
            ->assertOk()
            ->assertSee('Kelas')
            ->assertSee('04SIFE002');
    }

    public function test_each_role_can_update_profile_details_and_store_a_photo(): void
    {
        Storage::fake('public');

        foreach ([
            'admin' => null,
            'dosen' => '198501101',
            'mahasiswa' => '220101101',
        ] as $role => $nomorInduk) {
            $user = User::factory()->create([
                'role' => $role,
                'nomor_induk' => $nomorInduk,
            ]);

            $this->actingAs($user, $role)
                ->get(route($role.'.profile.edit'))
                ->assertOk()
                ->assertSee('Foto profil');

            $payload = [
                'name' => 'Profil '.ucfirst($role),
                'email' => $user->email,
                'profile_photo' => UploadedFile::fake()->image($role.'.png', 300, 300),
            ];

            if ($role !== 'admin') {
                $payload['nomor_induk'] = $nomorInduk;
            }

            $this->patch(route($role.'.profile.update'), $payload)
                ->assertRedirect(route($role.'.profile'));

            $user->refresh();

            $this->assertSame('Profil '.ucfirst($role), $user->name);
            $this->assertNotNull($user->profile_photo_path);
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'profile_photo_path' => $user->profile_photo_path,
            ]);
            Storage::disk('public')->assertExists($user->profile_photo_path);

            $this->get(route($role.'.profile'))
                ->assertOk()
                ->assertSee(Storage::disk('public')->url($user->profile_photo_path), false);
        }
    }

    public function test_replacing_a_photo_deletes_the_old_file_and_non_images_are_rejected(): void
    {
        Storage::fake('public');

        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nomor_induk' => '220101102',
        ]);
        $oldPhotoPath = UploadedFile::fake()->image('old.png')->store('profile-photos', 'public');
        $student->forceFill(['profile_photo_path' => $oldPhotoPath])->save();
        $payload = [
            'name' => $student->name,
            'email' => $student->email,
            'nomor_induk' => $student->nomor_induk,
        ];

        $this->actingAs($student, 'mahasiswa')
            ->patch(route('mahasiswa.profile.update'), [
                ...$payload,
                'profile_photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('profile_photo');

        Storage::disk('public')->assertExists($oldPhotoPath);
        $this->assertSame($oldPhotoPath, $student->fresh()->profile_photo_path);

        $this->patch(route('mahasiswa.profile.update'), [
            ...$payload,
            'profile_photo' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect(route('mahasiswa.profile'));

        Storage::disk('public')->assertMissing($oldPhotoPath);
        Storage::disk('public')->assertExists($student->fresh()->profile_photo_path);
    }
}
