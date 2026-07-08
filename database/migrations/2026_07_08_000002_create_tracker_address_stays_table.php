<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_address_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carro_id')->nullable()->constrained('carros')->nullOnDelete();
            $table->string('imei', 32)->index();
            $table->text('address_line');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('arrived_at')->nullable()->index();
            $table->timestamp('left_at')->nullable()->index();
            $table->unsignedInteger('permanence_seconds')->default(0);
            $table->timestamps();

            $table->index(['imei', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_address_stays');
    }
};
