<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversas', function (Blueprint $table) {
            $table->id();

            // Pedido relacionado
            $table->foreignId('pedido_id')->constrained()->onDelete('cascade');

            // Dispositivo fixo para envio das mensagens
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('set null');

            // Para saber se a conversa está ativa (pode ser útil futuramente)
            $table->boolean('ativa')->default(true);

            $table->timestamps();

            // Garante que um pedido tenha no máximo uma conversa
            $table->unique('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversas');
    }
};

