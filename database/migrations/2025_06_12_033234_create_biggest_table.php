<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->year('year');

            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_closed')->default(false);

            $table->timestamp('closed_at')->nullable();
            $table->uuid('closed_by')->nullable();
            $table->uuid('license_id')->nullable();
            $table->timestamps();

            // optional tapi recommended
            $table->unique(['year']);
        });

        Schema::create('opening_balances', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('account_id');

            $table->year('year'); // tahun tujuan (misal 2027)

            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);

            $table->timestamps();

            $table->unique(['account_id', 'year']);
        });

        Schema::create('accounting_closing_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('license_id');
            // $table->foreign('license_id')->references('id')->on('licenses')->onDelete('cascade');
            $table->uuid('account_id');
            $table->foreign('account_id')->references('id')->on('accounting_accounts')->onDelete('cascade');
            $table->uuid('period_id');
            $table->foreign('period_id')->references('id')->on('accounting_periods')->onDelete('cascade');
            $table->decimal('closing_balance', 15, 2);
        });

        Schema::create('accounting_journal_enclosures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_id')
                ->constrained('accounting_journals')
                ->cascadeOnDelete();

            $table->string('file_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('opening_balances');
        Schema::dropIfExists('accounting_closing_balances');
        Schema::dropIfExists('accounting_journal_enclosures');
    }
};