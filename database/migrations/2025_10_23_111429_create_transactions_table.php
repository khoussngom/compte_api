<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('transactions');
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('compte_id')->notNull();
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('type')->default('debit');
            $table->timestamps();
            // (clé étrangère ajoutée dans une migration séparée)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
