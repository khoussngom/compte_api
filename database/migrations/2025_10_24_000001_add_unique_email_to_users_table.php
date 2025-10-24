<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->unique('email', 'users_email_unique_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->dropUnique('users_email_unique_index');
        });
    }
};
