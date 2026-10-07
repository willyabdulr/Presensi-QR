<?php

namespace Database\Seeders;

use App\Models\JadwalKuliah;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pertemuan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $dosen = User::updateOrCreate(
            ['nomor_induk' => '198005122005011002'],
            [
                'email' => 'dosen@kampus.ac.id',
                'name' => 'Dr. Ir. Hendra Wijaya, M.T.',
                'role' => 'dosen',
                'password' => Hash::make('Andri@11'),
            ]
        );

        User::updateOrCreate(
            ['nomor_induk' => '142555781123'],
            [
                'email' => 'admin@unpam.ac.id',
                'name' => 'Admin Kampus',
                'role' => 'admin',
                'password' => Hash::make('Admin1@11'),
            ]
        );

        $kelasList = [
            '04SIFE001' => [
                ['Andi Pratama', '241011700101'],
                ['Siti Aulia Rahma', '241011700102'],
                ['Rizky Maulana', '241011700103'],
                ['Nabila Putri', '241011700104'],
                ['Fajar Ramadhan', '241011700105'],
                ['Dinda Maharani', '241011700106'],
                ['Reza Kurniawan', '241011700107'],
                ['Alya Safitri', '241011700108'],
                ['Budi Santoso', '220101001'],
                ['Siti Nurhaliza', '220101002'],
                ['Dimas Prasetyo', '220101003'],
                ['Rina Anggraini', '220101004'],
                ['Ahmad Fauzi', '220101005'],
                ['Dewi Lestari', '220101006'],
                ['Willy Abdul', '241011700486'],
            ],
            '04SIFE002' => [
                ['Raka Aditya', '241011700201'],
                ['Citra Lestari', '241011700202'],
                ['Farhan Akbar', '241011700203'],
                ['Nadya Amalia', '241011700204'],
                ['Bagas Saputra', '241011700205'],
                ['Tiara Anindya', '241011700206'],
                ['Dimas Prakoso', '241011700207'],
                ['Salsa Oktaviani', '241011700208'],
                ['Rizky Ramadhan', '220101007'],
                ['Nabila Putri', '220101008'],
                ['Fajar Nugroho', '220101009'],
                ['Tiara Maharani', '220101010'],
                ['Kevin Sanjaya', '220101011'],
                ['Anisa Rahmawati', '220101012'],
            ],
        ];
        $existingStudentEmails = [
            '220101001' => 'budi@kampus.ac.id',
            '220101002' => 'siti@kampus.ac.id',
            '220101003' => 'dimas@kampus.ac.id',
            '220101004' => 'rina@kampus.ac.id',
            '220101005' => 'ahmad@kampus.ac.id',
            '220101006' => 'dewi@kampus.ac.id',
            '220101007' => 'rizky@kampus.ac.id',
            '220101008' => 'nabila@kampus.ac.id',
            '220101009' => 'fajar@kampus.ac.id',
            '220101010' => 'tiara@kampus.ac.id',
            '220101011' => 'kevin@kampus.ac.id',
            '220101012' => 'anisa@kampus.ac.id',
        ];
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
            '241011700486' => 'Abdulr13',
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

        $kelasModels = [];
        foreach ($kelasList as $kodeKelas => $mahasiswaList) {
            $kelas = Kelas::updateOrCreate(['kode_kelas' => $kodeKelas]);
            $kelasModels[] = $kelas;

            foreach ($mahasiswaList as [$nama, $nim]) {
                $email = $nim === '241011700486'
                    ? 'willy04@gmail.ac.id'
                    : ($existingStudentEmails[$nim] ?? $nim.'@students.kampus.ac.id');

                User::updateOrCreate(
                    ['nomor_induk' => $nim],
                    [
                        'email' => $email,
                        'name' => $nama,
                        'role' => 'mahasiswa',
                        'kelas_id' => $kelas->id,
                        'password' => Hash::make($studentPasswords[$nim]),
                    ]
                );
            }
        }

        $mataKuliahData = [
            'IF3101' => [
                'nama' => 'Pemrograman Web',
                'topik' => [
                    'Pengenalan Pemrograman Web',
                    'HTML dan Struktur Halaman',
                    'CSS dan Tata Letak',
                    'JavaScript Dasar',
                    'Form dan Validasi Input',
                    'Manipulasi DOM',
                    'Integrasi API',
                    'PHP dan Pemrosesan Server',
                    'Koneksi Database',
                    'Proyek CRUD Web',
                ],
            ],
            'IF3102' => [
                'nama' => 'Basis Data',
                'topik' => [
                    'Konsep Dasar Basis Data',
                    'Entity Relationship Diagram',
                    'Normalisasi Data',
                    'SQL DDL dan Perancangan Tabel',
                    'SQL DML dan Query',
                    'Relasi dan JOIN',
                    'Index dan Optimasi Query',
                    'Transaksi dan Integritas Data',
                    'Keamanan Basis Data',
                    'Proyek Perancangan Basis Data',
                ],
            ],
            'IF3103' => [
                'nama' => 'Analisis & Perancangan Sistem',
                'topik' => [
                    'Pengantar Analisis Sistem',
                    'Siklus Hidup Pengembangan Sistem',
                    'Teknik Pengumpulan Kebutuhan',
                    'Pemodelan Use Case',
                    'Activity Diagram',
                    'Class Diagram',
                    'Sequence Diagram',
                    'Perancangan Arsitektur Sistem',
                    'Prototipe dan Validasi',
                    'Dokumentasi Rancangan Sistem',
                ],
            ],
            'IF3104' => [
                'nama' => 'Interaksi Manusia dan Komputer',
                'topik' => [
                    'Prinsip Interaksi Manusia dan Komputer',
                    'Riset Pengguna dan Persona',
                    'Arsitektur Informasi',
                    'Prinsip Usability',
                    'Wireframe Antarmuka',
                    'Prototipe Interaktif',
                    'Evaluasi Heuristik',
                    'Pengujian Usability',
                    'Aksesibilitas Antarmuka',
                    'Evaluasi dan Penyempurnaan Desain',
                ],
            ],
        ];

        $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Senin', 'Selasa'];
        $attendancePatterns = [
            ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Tidak Hadir'],
            ['Hadir', 'Hadir', 'Tidak Hadir', 'Hadir', 'Tidak Hadir', 'Hadir', 'Hadir', 'Tidak Hadir'],
            ['Hadir', 'Tidak Hadir', 'Hadir', 'Hadir', 'Tidak Hadir', 'Hadir', 'Tidak Hadir', 'Hadir'],
            ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Tidak Hadir', 'Hadir', 'Hadir', 'Hadir'],
        ];

        $scheduleIndex = 0;
        foreach ($mataKuliahData as $kodeMataKuliah => $data) {
            $mataKuliah = MataKuliah::updateOrCreate(
                ['kode_mk' => $kodeMataKuliah],
                ['nama_mk' => $data['nama']]
            );

            foreach ($kelasModels as $kelasIndex => $kelas) {
                $jadwal = JadwalKuliah::query()
                    ->where('dosen_id', $dosen->id)
                    ->where('mata_kuliah_id', $mataKuliah->id)
                    ->where('kelas_id', $kelas->id)
                    ->first();

                if (! $jadwal && $kelasIndex === 0) {
                    $jadwal = JadwalKuliah::query()
                        ->where('dosen_id', $dosen->id)
                        ->where('mata_kuliah_id', $mataKuliah->id)
                        ->whereNull('kelas_id')
                        ->first();
                }

                $jadwal ??= new JadwalKuliah;
                $jadwal->fill([
                    'dosen_id' => $dosen->id,
                    'mata_kuliah_id' => $mataKuliah->id,
                    'kelas_id' => $kelas->id,
                    'kelas' => $kelas->kode_kelas,
                    'hari' => $hari[$scheduleIndex],
                    'jam_mulai' => '08:00:00',
                    'jam_selesai' => '10:30:00',
                    'latitude_kelas' => -6.1753924,
                    'longitude_kelas' => 106.8271528,
                    'radius_meter' => JadwalKuliah::MAX_RADIUS_METERS,
                ])->save();

                foreach ($data['topik'] as $topicIndex => $topic) {
                    $meetingNumber = $topicIndex + 1;
                    $meetingDate = Carbon::parse('2026-10-01')->addWeeks($topicIndex);
                    $pertemuan = Pertemuan::firstOrCreate(
                        [
                            'jadwal_kuliah_id' => $jadwal->id,
                            'pertemuan_ke' => $meetingNumber,
                        ],
                        [
                            'topik' => $topic,
                            'tanggal_pertemuan' => $meetingDate->toDateString(),
                            'status_pertemuan' => $meetingNumber <= 4 ? 'Selesai' : 'Terjadwal',
                            'qr_token' => Str::random(40),
                            'qr_expires_at' => now(),
                            'is_active' => false,
                        ]
                    );

                    if ($meetingNumber <= 4) {
                        $roster = $kelas->mahasiswa()->orderBy('nomor_induk')->get();

                        foreach ($roster as $studentIndex => $mahasiswa) {
                            $status = $attendancePatterns[$topicIndex][($studentIndex + $kelasIndex) % 8];
                            $presensiTime = $status === 'Hadir'
                                ? $meetingDate->copy()->setTime(8, 10 + ($studentIndex % 40))
                                : null;

                            Presensi::firstOrCreate(
                                [
                                    'pertemuan_id' => $pertemuan->id,
                                    'mahasiswa_id' => $mahasiswa->id,
                                ],
                                [
                                    'status' => $status,
                                    'waktu_presensi' => $presensiTime,
                                ]
                            );
                        }
                    }
                }

                $scheduleIndex++;
            }
        }

        $this->mergeLegacyCourseRecord('IF3101', '01');
    }

    private function mergeLegacyCourseRecord(string $canonicalCourseCode, string $legacyCourseCode): void
    {
        $canonicalCourse = MataKuliah::query()
            ->where('kode_mk', $canonicalCourseCode)
            ->firstOrFail();
        $legacyCourse = MataKuliah::query()
            ->where('kode_mk', $legacyCourseCode)
            ->where('nama_mk', $canonicalCourse->nama_mk)
            ->first();

        if ($legacyCourse === null) {
            return;
        }

        DB::transaction(function () use ($canonicalCourse, $legacyCourse): void {
            foreach ($legacyCourse->jadwalKuliah as $legacySchedule) {
                $canonicalSchedule = null;
                $canonicalScheduleQuery = JadwalKuliah::query()
                    ->where('mata_kuliah_id', $canonicalCourse->id)
                    ->where('dosen_id', $legacySchedule->dosen_id);

                if ($legacySchedule->kelas_id !== null) {
                    $canonicalSchedule = (clone $canonicalScheduleQuery)
                        ->where('kelas_id', $legacySchedule->kelas_id)
                        ->first();
                }

                if ($canonicalSchedule === null && $legacySchedule->kelas !== null) {
                    $canonicalSchedule = (clone $canonicalScheduleQuery)
                        ->where('kelas', $legacySchedule->kelas)
                        ->first();
                }

                if ($canonicalSchedule === null) {
                    $legacySchedule->update(['mata_kuliah_id' => $canonicalCourse->id]);

                    continue;
                }

                foreach ($legacySchedule->pertemuan as $legacyMeeting) {
                    $canonicalMeeting = $canonicalSchedule->pertemuan()
                        ->where('pertemuan_ke', $legacyMeeting->pertemuan_ke)
                        ->first();

                    if ($canonicalMeeting === null) {
                        $legacyMeeting->update(['jadwal_kuliah_id' => $canonicalSchedule->id]);

                        continue;
                    }

                    foreach ($legacyMeeting->presensi as $legacyAttendance) {
                        $canonicalAttendance = $canonicalMeeting->presensi()
                            ->where('mahasiswa_id', $legacyAttendance->mahasiswa_id)
                            ->first();

                        if ($canonicalAttendance === null) {
                            $legacyAttendance->update(['pertemuan_id' => $canonicalMeeting->id]);

                            continue;
                        }

                        if ($legacyAttendance->status === 'Hadir' && $canonicalAttendance->status !== 'Hadir') {
                            $canonicalAttendance->fill($legacyAttendance->only([
                                'status',
                                'waktu_presensi',
                                'latitude_mahasiswa',
                                'longitude_mahasiswa',
                                'jarak_meter',
                            ]))->save();
                        }

                        $legacyAttendance->delete();
                    }

                    $legacyMeeting->delete();
                }

                $legacySchedule->delete();
            }

            $legacyCourse->delete();
        });
    }
}
