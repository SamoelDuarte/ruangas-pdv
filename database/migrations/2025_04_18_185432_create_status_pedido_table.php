<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_pedido', function (Blueprint $table) {
            $table->id();
            $table->string('descricao')->unique(); // Descrição do status
            $table->string('cor')->default('#6c757d'); // Nova coluna para a cor (default: cinza Bootstrap)
            $table->timestamps();
        });
    
        // Inserir os status padrão com cores
        DB::table('status_pedido')->insert([
            ['descricao' => 'pendente',     'cor' => '#ffc107', 'created_at' => now(), 'updated_at' => now()], // amarelo
            ['descricao' => 'em andamento', 'cor' => '#0d6efd', 'created_at' => now(), 'updated_at' => now()], // azul
            ['descricao' => 'finalizado',   'cor' => '#198754', 'created_at' => now(), 'updated_at' => now()], // verde
            ['descricao' => 'cancelado',    'cor' => '#dc3545', 'created_at' => now(), 'updated_at' => now()], // vermelho
            ['descricao' => 'atrasado',     'cor' => '#fd7e14', 'created_at' => now(), 'updated_at' => now()], // laranja
            ['descricao' => 'aguardando',   'cor' => '#6f42c1', 'created_at' => now(), 'updated_at' => now()], // roxo
            ['descricao' => 'recusado',     'cor' => '#6c757d', 'created_at' => now(), 'updated_at' => now()], // cinza
        ]);
    }
    public function down(): void
    {
        Schema::dropIfExists('status_pedido');
    }
};
