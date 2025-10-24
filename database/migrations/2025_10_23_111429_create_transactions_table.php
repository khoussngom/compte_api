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
            // utiliser bigint ici (même type que comptes.id). La contrainte FK est ajoutée dans une migration séparée
            $table->bigInteger('compte_id')->notNull();
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('type')->default('debit');
            $table->timestamps();
            // la clé étrangère est ajoutée par 2025_10_24_000003_add_foreign_key_to_transactions_table.php
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
