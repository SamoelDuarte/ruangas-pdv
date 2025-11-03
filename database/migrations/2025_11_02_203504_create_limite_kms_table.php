<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('limite_kms', function (Blueprint $table) {
            $table->id();
            $table->decimal('km_limite', 10, 2); // Limite de KM global
            $table->timestamps();
        });

        Schema::create('troca_oleos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carro_id')->constrained('carros')->onDelete('cascade');
            $table->decimal('km_na_troca', 10, 2); // KM quando foi feita a troca
            $table->timestamp('data_troca')->useCurrent();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('troca_oleos');
        Schema::dropIfExists('limite_kms');
    }
};
