<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kelas', 20)->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('role')->constrained('kelas')->nullOnDelete();
            $table->index(['kelas_id', 'role']);
        });

        Schema::table('jadwal_kuliah', function (Blueprint $table) {
            $table->foreignId('kelas_id')->nullable()->after('mata_kuliah_id')->constrained('kelas')->nullOnDelete();
            $table->index(['dosen_id', 'mata_kuliah_id', 'kelas_id']);
        });

        foreach (DB::table('jadwal_kuliah')
            ->whereNotNull('kelas')
            ->where('kelas', '<>', '')
            ->distinct()
            ->pluck('kelas') as $kodeKelas) {
            DB::table('kelas')->insertOrIgnore([
                'kode_kelas' => $kodeKelas,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $kelasId = DB::table('kelas')->where('kode_kelas', $kodeKelas)->value('id');
            DB::table('jadwal_kuliah')->where('kelas', $kodeKelas)->update(['kelas_id' => $kelasId]);
        }

        Schema::table('pertemuan', function (Blueprint $table) {
            $table->string('topik')->default('Materi kuliah');
            $table->date('tanggal_pertemuan')->nullable();
            $table->string('status_pertemuan', 20)->default('Terjadwal');
            $table->unique(['jadwal_kuliah_id', 'pertemuan_ke']);
        });

        DB::table('pertemuan')
            ->whereNull('tanggal_pertemuan')
            ->update(['tanggal_pertemuan' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('pertemuan', function (Blueprint $table) {
            $table->dropUnique(['jadwal_kuliah_id', 'pertemuan_ke']);
            $table->dropColumn(['topik', 'tanggal_pertemuan', 'status_pertemuan']);
        });

        Schema::table('jadwal_kuliah', function (Blueprint $table) {
            $table->dropIndex(['dosen_id', 'mata_kuliah_id', 'kelas_id']);
            $table->dropConstrainedForeignId('kelas_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['kelas_id', 'role']);
            $table->dropConstrainedForeignId('kelas_id');
        });

        Schema::dropIfExists('kelas');
    }
};
