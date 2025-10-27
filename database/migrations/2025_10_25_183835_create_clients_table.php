<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // avoid running this migration inside a transaction on managed Postgres (Neon)
    public $withinTransaction = false;

    public function up(): void
    {
        // Avoid creating the table if it already exists (some environments include an earlier migration)
        if (Schema::hasTable('clients')) {
            return;
        }

        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // store user_id as uuid to match users.id
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('adresse')->nullable();
            $table->date('date_naissance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
