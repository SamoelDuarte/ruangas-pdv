<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_forma_pagamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            $table->foreignId('forma_pagamento_id')->constrained('formas_pagamento')->onDelete('cascade');
            $table->decimal('valor', 10, 2); // valor que foi usado dessa forma de pagamento

            // Campos adicionais para controle de troco (usado principalmente quando a forma for dinheiro)
            $table->decimal('valor_recebido', 10, 2)->nullable(); // quanto o cliente entregou
            $table->decimal('troco', 10, 2)->nullable(); // quanto foi o troco

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_forma_pagamento');
    }
};
