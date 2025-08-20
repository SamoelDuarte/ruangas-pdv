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
        Schema::create('abastecimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carro_id')->constrained('carros')->onDelete('cascade');
            $table->decimal('litros_abastecido', 8, 2);
            $table->decimal('preco_por_litro', 8, 2);
            $table->decimal('km_atual', 10, 2);
            $table->date('data_abastecimento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abastecimentos');
    }
};
