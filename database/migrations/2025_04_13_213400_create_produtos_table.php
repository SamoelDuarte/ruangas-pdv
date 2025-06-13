<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->decimal('valor', 10, 2);
            $table->decimal('valor_app', 10, 2)->nullable();
            $table->decimal('valor_min_app', 10, 2)->nullable();
            $table->decimal('valor_max_app', 10, 2)->nullable();
            $table->string('foto')->nullable();
            $table->boolean('aplicativo')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        DB::table('produtos')->insert([
            [
                'nome' => 'P13',
                'valor' => 120.00,
                'valor_app' => 109.99,
                'valor_min_app' => 104.99,
                'valor_max_app' => 114.99,
                'foto' => null,
                'aplicativo' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'P13 completo',
                'valor' => 320.00,
                'valor_app' => 305.00,
                'valor_min_app' => 300.00,
                'valor_max_app' => 310.00,
                'foto' => null,
                'aplicativo' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'P13 vazio',
                'valor' => 210.00,
                'valor_app' => 210.00,
                'valor_min_app' => 205.00,
                'valor_max_app' => 215.00,
                'foto' => null,
                'aplicativo' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'P20',
                'valor' => 209.99,
                'valor_app' => 209.99,
                'valor_min_app' => 204.99,
                'valor_max_app' => 214.99,
                'foto' => null,
                'aplicativo' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'P20 Vazio',
                'valor' => 600.00,
                'valor_app' => 600.00,
                'valor_min_app' => 595.00,
                'valor_max_app' => 605.00,
                'foto' => null,
                'aplicativo' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'P45',
                'valor' => 410.00,
                'valor_app' => 410.00,
                'valor_min_app' => 405.00,
                'valor_max_app' => 415.00,
                'foto' => null,
                'aplicativo' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'P45 Vazio',
                'valor' => 600.00,
                'valor_app' => 600.00,
                'valor_min_app' => 595.00,
                'valor_max_app' => 605.00,
                'foto' => null,
                'aplicativo' => 1,
                'ativo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
