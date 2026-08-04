<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('department');
            $table->geography('location', subtype: 'point', srid: 4326);
            $table->timestamps();

            $table->unique(['name', 'department']);
            $table->spatialIndex('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
