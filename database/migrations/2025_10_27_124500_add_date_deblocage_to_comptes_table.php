<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            if (! Schema::hasColumn('comptes', 'date_deblocage')) {
                $table->timestamp('date_deblocage')->nullable()->after('date_fin_blocage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            if (Schema::hasColumn('comptes', 'date_deblocage')) {
                $table->dropColumn('date_deblocage');
            }
        });
    }
};
