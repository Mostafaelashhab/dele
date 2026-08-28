<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // High-volume breadcrumb table. Writes are throttled by interval and
        // by minimum movement, and rows are pruned on a retention schedule,
        // because storing a point per second per rider is what kills these
        // systems at scale.
        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ulid('rider_id');
            $table->ulid('delivery_id')->nullable();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('accuracy_meters')->nullable();
            $table->unsignedSmallInteger('heading_degrees')->nullable();
            $table->unsignedSmallInteger('speed_kmh')->nullable();
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->string('status', 32)->nullable();
            $table->timestamp('recorded_at');

            $table->foreign('rider_id')->references('id')->on('riders')->cascadeOnDelete();
            $table->foreign('delivery_id')->references('id')->on('deliveries')->nullOnDelete();

            $table->index(['delivery_id', 'recorded_at'], 'locations_delivery_time_index');
            $table->index(['rider_id', 'recorded_at'], 'locations_rider_time_index');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_locations');
    }
};
