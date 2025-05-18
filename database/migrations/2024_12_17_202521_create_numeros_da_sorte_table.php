<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('numeros_da_sorte', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('sorteio_id');
            $table->integer('numero');
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');
            $table->foreign('sorteio_id')->references('id')->on('sorteios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('numeros_da_sorte');
    }
};
