<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // necessary for Neon / PG: don't run inside a transaction
    public $withinTransaction = false;

    public function up(): void
    {
        // ensure constraint not present (idempotent)
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS account_transactions DROP CONSTRAINT IF EXISTS account_transactions_compte_id_foreign');

        Schema::table('account_transactions', function ($table) {
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS account_transactions DROP CONSTRAINT IF EXISTS account_transactions_compte_id_foreign');
    }
};
