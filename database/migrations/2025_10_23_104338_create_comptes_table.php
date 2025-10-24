<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->notNull();
            $table->string('type');
            $table->decimal('solde', 15, 2)->default(0);
            $table->timestamps();

            // (clé étrangère ajoutée dans une migration séparée)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
