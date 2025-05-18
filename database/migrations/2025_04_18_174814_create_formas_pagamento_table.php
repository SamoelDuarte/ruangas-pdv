<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('formas_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->timestamps();
        });
        
        // Inserindo as formas de pagamento padrão
        DB::table('formas_pagamento')->insert([
            ['descricao' => 'Dinheiro', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Cartão de Crédito', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Cartão de Débito', 'created_at' => now(), 'updated_at' => now()],
            ['descricao' => 'Pix', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formas_pagamento');
    }
};
