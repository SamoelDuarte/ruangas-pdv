<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carros', function (Blueprint $table) {
            $table->string('placa', 20)->nullable()->after('nome');
            $table->string('modelo')->nullable()->after('placa');
            $table->string('imei_rastreador', 32)->nullable()->after('modelo')->index();
        });
    }

    public function down(): void
    {
        Schema::table('carros', function (Blueprint $table) {
            $table->dropColumn(['placa', 'modelo', 'imei_rastreador']);
        });
    }
};
