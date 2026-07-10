<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carro_id')->nullable()->constrained('carros')->nullOnDelete();
            $table->string('imei', 32)->index();
            $table->string('command_name', 50);
            $table->boolean('target_blocked')->default(false);
            $table->text('command_payload');
            $table->string('status', 20)->default('pending')->index();
            $table->text('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_commands');
    }
};
