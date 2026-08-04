<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('truck_type_id')->constrained()->restrictOnDelete();
            $table->string('plate_number')->unique();
            $table->unsignedInteger('capacity_kg');
            $table->unsignedInteger('length_cm')->nullable();
            $table->unsignedInteger('width_cm')->nullable();
            $table->unsignedInteger('height_cm')->nullable();
            $table->string('availability')->default('available');
            $table->date('insurance_expires_at')->nullable();
            $table->timestamps();

            $table->index(['truck_type_id', 'availability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
