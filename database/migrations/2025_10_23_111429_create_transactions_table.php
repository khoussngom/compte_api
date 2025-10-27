<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // execute this migration outside transactions to avoid PG "current transaction is aborted" on managed services
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::dropIfExists('transactions');
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            // créer la FK inline pour s'assurer que le type et la contrainte sont définis ensemble
            // comptes.id is UUID, so store as uuid here
            $table->uuid('compte_id');
            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('type')->default('debit');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
