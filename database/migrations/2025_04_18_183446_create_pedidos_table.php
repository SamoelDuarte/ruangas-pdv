<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('entregador_id')->nullable()->constrained('entregadores');
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->decimal('desconto', 10, 2)->default(0);
            $table->text('mensagem')->nullable();
            $table->enum('tipo_pedido', ['tele_entrega', 'automatico', 'pdv','portaria'])->default('tele_entrega'); // Tipo do pedido
            $table->boolean('notifica_mensagem')->default(false);
            $table->string('motivo_cancelamento')->nullable(); // Pode ser nulo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
