<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_pings', function (Blueprint $table) {
            $table->decimal('tensao_bateria', 8, 3)->nullable()->after('speed');
            $table->decimal('tensao_veiculo', 8, 3)->nullable()->after('tensao_bateria');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_pings', function (Blueprint $table) {
            $table->dropColumn(['tensao_bateria', 'tensao_veiculo']);
        });
    }
};
