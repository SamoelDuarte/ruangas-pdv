<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateEntregadoresTable extends Migration
{
    public function up()
    {
        Schema::create('entregadores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha'); // você pode usar 'password' se preferir
            $table->string('telefone')->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('trabalhando')->default(false);
            $table->timestamps();
        });

        // Inserindo entregadores fictícios
        DB::table('entregadores')->insert([
            [
                'nome' => 'João Silva',
                'email' => 'joao@example.com',
                'senha' => bcrypt('senha123'),
                'telefone' => '11999990001',
                'ativo' => true,
                'trabalhando' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Maria Oliveira',
                'email' => 'maria@example.com',
                'senha' => bcrypt('senha123'),
                'telefone' => '11999990002',
                'ativo' => true,
                'trabalhando' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Carlos Souza',
                'email' => 'carlos@example.com',
                'senha' => bcrypt('senha123'),
                'telefone' => '11999990003',
                'ativo' => false,
                'trabalhando' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Ana Costa',
                'email' => 'ana@example.com',
                'senha' => bcrypt('senha123'),
                'telefone' => '11999990004',
                'ativo' => true,
                'trabalhando' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('entregadores');
    }
}
