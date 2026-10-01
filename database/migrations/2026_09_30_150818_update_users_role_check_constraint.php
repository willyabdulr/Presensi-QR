<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('dosen', 'mahasiswa', 'admin'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (DB::table('users')->where('role', 'admin')->exists()) {
            throw new RuntimeException('Remove admin accounts before reverting the role constraint.');
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('dosen', 'mahasiswa'))");
    }
};
