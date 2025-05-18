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
        Schema::table('clientes', function (Blueprint $table) {
            // Adiciona a coluna sorteio_id como chave estrangeira
            $table->unsignedBigInteger('sorteio_id')->nullable()->after('id');
            $table->foreign('sorteio_id')->references('id')->on('sorteios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Remove a chave estrangeira e a coluna
            $table->dropForeign(['sorteio_id']);
            $table->dropColumn('sorteio_id');
        });
    }
};
