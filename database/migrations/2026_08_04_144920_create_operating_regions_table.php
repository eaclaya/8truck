<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operating_regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->geography('center', subtype: 'point', srid: 4326);
            $table->unsignedInteger('radius_m')->default(50000);
            $table->timestamps();

            $table->spatialIndex('center');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operating_regions');
    }
};
