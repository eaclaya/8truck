<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->ulid('reference')->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');
            $table->foreignId('truck_type_id')->nullable()->constrained()->nullOnDelete();

            $table->string('origin_address');
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->geography('origin', subtype: 'point', srid: 4326);
            $table->string('destination_address');
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->geography('destination', subtype: 'point', srid: 4326);

            $table->date('pickup_date');
            $table->string('cargo_type');
            $table->unsignedInteger('weight_kg')->nullable();
            $table->unsignedInteger('length_cm')->nullable();
            $table->unsignedInteger('width_cm')->nullable();
            $table->unsignedInteger('height_cm')->nullable();
            $table->text('special_instructions')->nullable();
            $table->decimal('budget_amount', 10, 2)->nullable();
            $table->char('currency', 3)->default('HNL');

            $table->foreignId('assigned_transporter_id')->nullable()->constrained('transporter_profiles')->nullOnDelete();
            $table->foreignId('assigned_truck_id')->nullable()->constrained('trucks')->nullOnDelete();
            $table->unsignedBigInteger('accepted_quote_id')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'pickup_date']);
            $table->spatialIndex('origin');
            $table->spatialIndex('destination');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
