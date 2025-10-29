<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'force_password_change')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('force_password_change')->default(false)->after('activation_expires_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'force_password_change')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('force_password_change');
            });
        }
    }
};
