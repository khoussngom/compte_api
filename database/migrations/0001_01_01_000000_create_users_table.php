<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Use UUID primary keys to match factories and downstream relations
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->nullable(false)->unique();
            $table->string('telephone')->nullable();
            $table->string('password');
            // Flag to mark administrative users. Some test factories set 1/0 so use tinyInteger
            // to avoid strict type-casting issues with PostgreSQL boolean casting in tests.
            $table->tinyInteger('admin')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
