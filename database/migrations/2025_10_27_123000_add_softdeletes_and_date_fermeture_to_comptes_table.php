<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // run outside transactions on managed Postgres
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            // add deleted_at for SoftDeletes if not exists
            if (! Schema::hasColumn('comptes', 'deleted_at')) {
                $table->softDeletes();
            }

            if (! Schema::hasColumn('comptes', 'date_fermeture')) {
                $table->timestamp('date_fermeture')->nullable()->after('statut_compte');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            if (Schema::hasColumn('comptes', 'date_fermeture')) {
                $table->dropColumn('date_fermeture');
            }
            if (Schema::hasColumn('comptes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
