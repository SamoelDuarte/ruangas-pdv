<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('message_queue', function (Blueprint $table) {
            $table->id();
            $table->string('sender_number');  // Número que enviou a mensagem
            $table->string('device_session'); // Sessão do dispositivo que recebeu
            $table->text('message');          // Mensagem recebida
            $table->string('message_type')->default('conversation'); // Tipo da mensagem
            $table->boolean('is_from_me')->default(false); // Se a mensagem foi enviada pelo dispositivo
            $table->string('status')->nullable(); // Status da mensagem
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('sender_number');
            $table->index('device_session');
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_queue');
    }
};
