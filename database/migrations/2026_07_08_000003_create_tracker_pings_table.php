<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_pings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carro_id')->nullable()->constrained('carros')->nullOnDelete();
            $table->foreignId('tracker_address_stay_id')->nullable()->constrained('tracker_address_stays')->nullOnDelete();
            $table->string('imei', 32)->index();
            $table->string('packet_type', 20)->nullable()->index();
            $table->string('packet_origin', 20)->nullable();
            $table->string('protocol', 20)->nullable();
            $table->string('device_name')->nullable();
            $table->text('raw_message');
            $table->decimal('latitude', 10, 7)->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable()->index();
            $table->decimal('altitude', 10, 2)->nullable();
            $table->decimal('speed', 10, 2)->nullable();
            $table->boolean('ignition')->nullable();
            $table->boolean('in_motion')->nullable();
            $table->text('address_line')->nullable();
            $table->string('geocode_source', 40)->nullable();
            $table->timestamp('gps_at')->nullable()->index();
            $table->timestamp('received_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['imei', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_pings');
    }
};
