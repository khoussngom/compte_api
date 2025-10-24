<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // avoid transactional execution on PG managed services
    public $withinTransaction = false;

    public function up(): void
    {
        // drop existing constraints if any, then add (idempotent)
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS admins DROP CONSTRAINT IF EXISTS admins_user_id_foreign');
        Schema::table('admins', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS clients DROP CONSTRAINT IF EXISTS clients_user_id_foreign');
        Schema::table('clients', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS comptes DROP CONSTRAINT IF EXISTS comptes_user_id_foreign');
        Schema::table('comptes', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS account_transactions DROP CONSTRAINT IF EXISTS account_transactions_compte_id_foreign');
        Schema::table('account_transactions', function ($table) {
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS admins DROP CONSTRAINT IF EXISTS admins_user_id_foreign');
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS clients DROP CONSTRAINT IF EXISTS clients_user_id_foreign');
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS comptes DROP CONSTRAINT IF EXISTS comptes_user_id_foreign');
        \Illuminate\Support\Facades\DB::statement(/** @lang sql */ 'ALTER TABLE IF EXISTS account_transactions DROP CONSTRAINT IF EXISTS account_transactions_compte_id_foreign');
    }
};
