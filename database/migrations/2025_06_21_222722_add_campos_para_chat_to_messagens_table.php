<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messagens', function (Blueprint $table) {
            $table->foreignId('pedido_id')->nullable()->after('id')->constrained('pedidos')->onDelete('set null');
            $table->foreignId('usuario_id')->nullable()->after('device_id')->constrained('entregadores')->onDelete('set null');
            $table->enum('direcao', ['enviado', 'recebido'])->default('enviado')->after('messagem');
            $table->boolean('enviado')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('messagens', function (Blueprint $table) {
            $table->dropForeign(['pedido_id']);
            $table->dropColumn('pedido_id');

            $table->dropForeign(['usuario_id']);
            $table->dropColumn('usuario_id');

            $table->dropColumn('direcao');
            $table->dropColumn('enviado');
        });
    }
};
