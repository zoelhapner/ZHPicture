<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Pastikan schema ZH Picture tersedia
         */
        DB::statement('CREATE SCHEMA IF NOT EXISTS zhpicture');

        /*
         * Urutan penting karena ada Foreign Key.
         * Parent dibuat dan diisi terlebih dahulu.
         */
        $tables = [
            'menus',
        ];

        foreach ($tables as $table) {

            /*
             * 1. Copy struktur
             */
            DB::statement("
                CREATE TABLE IF NOT EXISTS zhpicture.{$table}
                (LIKE lebihtersistem.{$table} INCLUDING ALL)
            ");

            /*
             * 2. Copy seluruh data
             */
            DB::statement("
                INSERT INTO zhpicture.{$table}
                SELECT *
                FROM lebihtersistem.{$table}
            ");
        }
    }

    public function down(): void
    {
        /*
         * Hapus tabel Finance ZH Picture
         */
        $tables = [
            'menus',
        ];

        foreach ($tables as $table) {
            DB::statement("
                DROP TABLE IF EXISTS zhpicture.{$table} CASCADE
            ");
        }
    }
};