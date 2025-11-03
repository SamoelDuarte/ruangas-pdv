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
        Schema::table('limite_kms', function (Blueprint $table) {
            $table->integer('km_limite')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('limite_kms', function (Blueprint $table) {
            $table->decimal('km_limite', 10, 2)->change();
        });
    }
};
