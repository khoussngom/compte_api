<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code_transaction')->unique();
            $table->enum('type', ['depot', 'retrait', 'transfert']);
            $table->decimal('montant', 15, 2);
            $table->text('description')->nullable();
            $table->uuid('compte_id');
            $table->uuid('agent_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('compte_id')->references('id')->on('comptes')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
