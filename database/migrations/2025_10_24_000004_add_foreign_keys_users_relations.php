<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('clients', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('comptes', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('account_transactions', function ($table) {
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function ($table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('clients', function ($table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('comptes', function ($table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('account_transactions', function ($table) {
            $table->dropForeign(['compte_id']);
        });
    }
};
