<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('conversas');
    }

    public function down()
    {
        // Opcionalmente você pode recriar a tabela no down(), se quiser reversibilidade
        Schema::create('conversas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('ativa')->default(true);
            $table->timestamps();
            $table->unique('pedido_id');
        });
    }
};
