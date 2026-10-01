<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('mahasiswa')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->where('role', 'admin')->exists()) {
            throw new RuntimeException('Remove admin accounts before reverting the role column migration.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['dosen', 'mahasiswa'])->default('mahasiswa')->change();
        });
    }
};
