<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historico_status_pedido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade'); // Relacionamento com o pedido
            $table->string('status'); // Apenas o novo status
            $table->string('mudanca_por')->default('sistema'); // Se foi 'usuario' ou 'sistema'
            $table->timestamps(); // A data de criação será a data da mudança de status
        });

         Artisan::call('db:seed', [
        '--class' => 'DatabaseSeeder',
        '--force' => true,
    ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_status_pedido');
    }
};

