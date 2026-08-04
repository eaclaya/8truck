<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('transporter_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_amount', 10, 2);
            $table->decimal('rate', 5, 4)->default(0);
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->char('currency', 3)->default('HNL');
            $table->string('status')->default('recorded');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
