<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel Sanctum sudah punya migration bawaan untuk personal_access_tokens.
     * Jalankan `php artisan install:api` dulu (ini akan generate tabel aslinya),
     * lalu jalankan migration TAMBAHAN ini untuk menambah kolom active_role_id.
     *
     * Kenapa active_role di token, bukan di users?
     * Supaya 1 username bisa login di beberapa device/sesi dengan
     * active role yang berbeda-beda tanpa saling menimpa.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreignId('active_role_id')
                ->nullable()
                ->after('tokenable_id')
                ->constrained('roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['active_role_id']);
            $table->dropColumn('active_role_id');
        });
    }
};