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
            'menus'
        ];

        foreach ($tables as $table) {

            /*
             * 1. Copy struktur
             */
            DB::statement("
                CREATE TABLE IF NOT EXISTS zhpicture.{$table}
                (LIKE public.{$table} INCLUDING ALL)
            ");

            /*
             * 2. Copy seluruh data
             */
            DB::statement("
                INSERT INTO zhpicture.{$table}
                SELECT *
                FROM public.{$table}
            ");
        }
    }

    public function down(): void
    {
        /*
         * Hapus tabel Finance ZH Picture
         */
        $tables = [
            'accounting_journal_details',
            'accounting_journal_enclosures',
            'accounting_journals',
            'accounting_closing_balances',
            'opening_balances',
            'accounting_periods',
            'accounting_accounts',
        ];

        foreach ($tables as $table) {
            DB::statement("
                DROP TABLE IF EXISTS zhpicture.{$table} CASCADE
            ");
        }
    }
};