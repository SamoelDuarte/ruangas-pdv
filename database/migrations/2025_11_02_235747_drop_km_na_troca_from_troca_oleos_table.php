<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('troca_oleos', function (Blueprint $table) {
            $table->dropColumn('km_na_troca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('troca_oleos', function (Blueprint $table) {
            $table->integer('km_na_troca')->nullable();
        });
    }
};
