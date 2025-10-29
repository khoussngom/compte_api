<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // avoid wrapping in transaction on some PG hosts
    public $withinTransaction = false;

    public function up(): void
    {
        // If admins.user_id exists as an integer, convert it to uuid by recreating the column.
        // This migration assumes you don't need to preserve numeric IDs. If you do, run a
        // custom data migration to map integers -> uuids before applying.

        if (Schema::hasTable('admins')) {
            // Drop existing column if present
            if (Schema::hasColumn('admins', 'user_id')) {
                // ensure we don't error when the foreign constraint is missing
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE IF EXISTS admins DROP CONSTRAINT IF EXISTS admins_user_id_foreign');
                Schema::table('admins', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
            }

            // Recreate as uuid and add FK to users(id)
            Schema::table('admins', function (Blueprint $table) {
                $table->uuid('user_id')->after('id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'user_id')) {
            // drop FK if exists then the column, then recreate as bigint
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE IF EXISTS admins DROP CONSTRAINT IF EXISTS admins_user_id_foreign');
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('user_id');
                // revert to bigint user_id (no data preserved)
                $table->bigInteger('user_id')->notNull();
            });
        }
    }
};
