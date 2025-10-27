<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            // Use UUID primary key to align with users and other relations
            $table->uuid('id')->primary();
            // store user_id as uuid
            $table->uuid('user_id');
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->date('date_naissance')->nullable();
            $table->timestamps();
            // foreign key may be added in a separate migration
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
