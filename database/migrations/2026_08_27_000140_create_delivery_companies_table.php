<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('slug')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->string('status', 32)->default('pending');

            $table->string('address_line')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Provider abstraction: an in-network company is dispatched through
            // the platform, an external one through its own integration.
            $table->string('provider', 32)->default('internal');
            $table->json('provider_config')->nullable();

            $table->boolean('auto_accept_offers')->default(false);
            $table->unsignedSmallInteger('max_concurrent_deliveries')->default(50);
            $table->unsignedSmallInteger('offer_timeout_seconds')->nullable();
            $table->unsignedSmallInteger('commission_bps')->nullable();
            $table->string('settlement_period', 32)->default('weekly');
            $table->string('settlement_account')->nullable();
            $table->json('working_hours')->nullable();

            // Denormalised performance counters, recomputed by the metrics job.
            // Dispatch reads these on every match, so they must not require a
            // join across the full delivery history.
            $table->unsignedSmallInteger('rating_bps')->default(0);
            $table->unsignedSmallInteger('acceptance_rate_bps')->default(0);
            $table->unsignedSmallInteger('completion_rate_bps')->default(0);
            $table->unsignedSmallInteger('average_pickup_minutes')->default(12);
            $table->unsignedInteger('completed_deliveries_count')->default(0);
            $table->timestamp('metrics_updated_at')->nullable();

            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'provider']);
            $table->index('acceptance_rate_bps');
        });

        Schema::create('company_service_areas', function (Blueprint $table) {
            $table->id();
            $table->ulid('delivery_company_id');
            $table->ulid('zone_id');
            $table->boolean('accepts_pickup')->default(true);
            $table->boolean('accepts_dropoff')->default(true);
            $table->unsignedInteger('surcharge_minor')->default(0);
            $table->timestamps();

            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->cascadeOnDelete();
            $table->foreign('zone_id')->references('id')->on('zones')->cascadeOnDelete();
            $table->unique(['delivery_company_id', 'zone_id']);
            $table->index(['zone_id', 'accepts_pickup']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_service_areas');
        Schema::dropIfExists('delivery_companies');
    }
};
