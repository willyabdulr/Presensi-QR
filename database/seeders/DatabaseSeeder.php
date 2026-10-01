<?php

namespace Database\Seeders;

use App\Models\JadwalKuliah;
use App\Models\MataKuliah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Dosen
        $dosen = User::updateOrCreate(
            ['email' => 'dosen@kampus.ac.id'],
            [
                'name' => 'Dr. Ir. Hendra Wijaya, M.T.',
                'role' => 'dosen',
                'nomor_induk' => '198005122005011002',
                'password' => Hash::make('password'),
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@unpam.ac.id'],
            [
                'name' => 'Admin Kampus',
                'role' => 'admin',
                'nomor_induk' => '142555781123',
                'password' => Hash::make('Admin1@11'),
            ]
        );

        $dosen = User::updateOrCreate(
            ['email' => 'willy04@gmail.ac.id'],
            [
                'name' => 'Willy Abdul',
                'role' => 'mahasiswa',
                'nim' => '241011700486',
                'password' => Hash::make('Abdulr13'),
            ]
        );

        $mahasiswa = User::updateOrCreate(
            ['email' => 'dosen@kampus.ac.id'],
            [
                'name' => 'Dr. Ir. Hendra Wijaya, M.T.',
                'role' => 'dosen',
                'nomor_induk' => '198005122005011002',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Akun Mahasiswa
        $mahasiswaList = [
            ['name' => 'Budi Santoso', 'nomor_induk' => '220101001', 'email' => 'budi@kampus.ac.id'],
            ['name' => 'Siti Nurhaliza', 'nomor_induk' => '220101002', 'email' => 'siti@kampus.ac.id'],
            ['name' => 'Dimas Prasetyo', 'nomor_induk' => '220101003', 'email' => 'dimas@kampus.ac.id'],
            ['name' => 'Rina Anggraini', 'nomor_induk' => '220101004', 'email' => 'rina@kampus.ac.id'],
            ['name' => 'Ahmad Fauzi', 'nomor_induk' => '220101005', 'email' => 'ahmad@kampus.ac.id'],
            ['name' => 'Dewi Lestari', 'nomor_induk' => '220101006', 'email' => 'dewi@kampus.ac.id'],
            ['name' => 'Rizky Ramadhan', 'nomor_induk' => '220101007', 'email' => 'rizky@kampus.ac.id'],
            ['name' => 'Nabila Putri', 'nomor_induk' => '220101008', 'email' => 'nabila@kampus.ac.id'],
            ['name' => 'Fajar Nugroho', 'nomor_induk' => '220101009', 'email' => 'fajar@kampus.ac.id'],
            ['name' => 'Tiara Maharani', 'nomor_induk' => '220101010', 'email' => 'tiara@kampus.ac.id'],
            ['name' => 'Kevin Sanjaya', 'nomor_induk' => '220101011', 'email' => 'kevin@kampus.ac.id'],
            ['name' => 'Anisa Rahmawati', 'nomor_induk' => '220101012', 'email' => 'anisa@kampus.ac.id'],
        ];

        foreach ($mahasiswaList as $mhs) {
            User::updateOrCreate(
                ['email' => $mhs['email']],
                [
                    'name' => $mhs['name'],
                    'role' => 'mahasiswa',
                    'nomor_induk' => $mhs['nomor_induk'],
                    'password' => Hash::make('password'),
                ]
            );
        }

        // 3. Mata Kuliah
        $mk1 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF3101'],
            ['nama_mk' => 'Pemrograman Web Berbasis Komponen']
        );

        $mk2 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF3102'],
            ['nama_mk' => 'Rekayasa Perangkat Lunak Terdistribusi']
        );

        // 4. Jadwal Kuliah
        JadwalKuliah::updateOrCreate(
            [
                'dosen_id' => $dosen->id,
                'mata_kuliah_id' => $mk1->id,
                'hari' => 'Senin',
            ],
            [
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '10:30:00',
                'latitude_kelas' => -6.1753924,
                'longitude_kelas' => 106.8271528,
                'radius_meter' => 50,
            ]
        );

        JadwalKuliah::updateOrCreate(
            [
                'dosen_id' => $dosen->id,
                'mata_kuliah_id' => $mk2->id,
                'hari' => 'Kamis',
            ],
            [
                'jam_mulai' => '13:00:00',
                'jam_selesai' => '15:30:00',
                'latitude_kelas' => -6.1753924,
                'longitude_kelas' => 106.8271528,
                'radius_meter' => 50,
            ]
        );
    }
}
