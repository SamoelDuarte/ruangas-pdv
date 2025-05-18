<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNumeroMinNumeroMaxToSorteiosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sorteios', function (Blueprint $table) {
            $table->integer('numero_min')->unsigned()->after('numero_sorteio'); // Coluna numero_min
            $table->integer('numero_max')->unsigned()->after('numero_min'); // Coluna numero_max
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sorteios', function (Blueprint $table) {
            $table->dropColumn(['numero_min', 'numero_max']); // Remove as colunas
        });
    }
}
