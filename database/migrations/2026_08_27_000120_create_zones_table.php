<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('parent_id')->nullable();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->string('city')->default('Banha');
            $table->string('governorate')->default('Qalyubia');

            // Circle-based zones ship today; the polygon column is populated
            // when geospatial matching is enabled, and the resolver prefers it.
            $table->decimal('centroid_latitude', 10, 7);
            $table->decimal('centroid_longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(1500);
            $table->json('polygon')->nullable();

            $table->unsignedInteger('base_price_minor')->default(1500);
            $table->unsignedSmallInteger('estimated_minutes')->default(25);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('zones')->nullOnDelete();
            $table->index(['is_active', 'sort_order']);
            $table->index(['city', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
