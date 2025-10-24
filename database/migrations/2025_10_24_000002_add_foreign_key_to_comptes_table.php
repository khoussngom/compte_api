<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // éviter que cette migration soit exécutée dans une transaction (Neon / PGDDL)
    public $withinTransaction = false;

    public function up(): void
    {
        // safe / idempotent: drop constraint if exists, then add it
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS comptes DROP CONSTRAINT IF EXISTS comptes_user_id_foreign');

        Schema::table('comptes', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS comptes DROP CONSTRAINT IF EXISTS comptes_user_id_foreign');
    }
};
