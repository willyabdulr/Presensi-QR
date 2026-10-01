<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:approve {email}', function (string $email): int {
    $user = User::query()
        ->where('email', $email)
        ->whereIn('role', ['dosen', 'mahasiswa'])
        ->first();

    if (! $user) {
        $this->error('Akun dosen atau mahasiswa tidak ditemukan.');

        return 1;
    }

    if ($user->is_approved) {
        $this->info('Akun tersebut sudah disetujui.');

        return 0;
    }

    $user->is_approved = true;
    $user->save();

    $this->info("Akun {$user->role} {$user->email} berhasil disetujui.");

    return 0;
})->purpose('Approve a pending lecturer or student registration');

Artisan::command('users:make-admin {email}', function (string $email): int {
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error('Akun tidak ditemukan. Buat akun terlebih dahulu melalui registrasi.');

        return 1;
    }

    $user->role = 'admin';
    $user->is_approved = true;
    $user->save();

    $this->info("{$user->email} sekarang menjadi admin dan dapat login sebagai admin.");

    return 0;
})->purpose('Promote an existing account to administrator');
