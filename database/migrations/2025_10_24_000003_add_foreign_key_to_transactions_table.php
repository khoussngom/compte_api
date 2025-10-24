<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_transactions', function ($table) {
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('account_transactions', function ($table) {
            $table->dropForeign(['compte_id']);
        });
    }
};
