<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // Avoid running DDL inside a transaction on managed Postgres (Neon)
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            // Use UUID primary key for comptes to align with User and Client UUIDs
            $table->uuid('id')->primary();
            // user_id stored as UUID to match users.id
            $table->uuid('user_id');
            // créer la FK inline pour éviter des migrations séparées sur PG managé
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('type');
            $table->decimal('solde', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
