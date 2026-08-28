<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('delivery_company_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('phone', 32);
            $table->string('national_id', 32)->nullable();
            $table->string('status', 32)->default('offline');
            $table->string('vehicle_type', 32)->default('motorcycle');
            $table->string('vehicle_identifier', 64)->nullable();

            $table->unsignedTinyInteger('max_concurrent_deliveries')->default(2);
            $table->unsignedTinyInteger('active_deliveries_count')->default(0);

            $table->unsignedSmallInteger('rating_bps')->default(0);
            $table->unsignedSmallInteger('acceptance_rate_bps')->default(0);
            $table->unsignedSmallInteger('completion_rate_bps')->default(0);
            $table->unsignedInteger('completed_deliveries_count')->default(0);

            // Last known position, kept on the row for cheap proximity filters.
            // The full breadcrumb trail lives in delivery_locations.
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('went_online_at')->nullable();

            $table->string('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->cascadeOnDelete();

            // Dispatch's hottest query: online riders of a company with spare
            // capacity, so the composite index leads with the filters.
            $table->index(['delivery_company_id', 'status', 'active_deliveries_count'], 'riders_dispatch_index');
            $table->index(['status', 'location_updated_at']);
            $table->unique(['delivery_company_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
