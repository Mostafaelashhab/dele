<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every price the platform charges is the sum of matching rows here.
        // A null scope column means "applies to everything" — that is what
        // lets an operator change prices without a deployment.
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type', 48);

            // Owner scope: null company means the platform-wide default table.
            $table->ulid('delivery_company_id')->nullable();
            $table->ulid('business_id')->nullable();

            // Match conditions. Null on any of these widens the rule.
            $table->ulid('pickup_zone_id')->nullable();
            $table->ulid('dropoff_zone_id')->nullable();
            $table->string('priority', 32)->nullable();
            $table->string('package_size', 32)->nullable();
            $table->unsignedInteger('min_distance_meters')->nullable();
            $table->unsignedInteger('max_distance_meters')->nullable();
            $table->time('active_from')->nullable();
            $table->time('active_until')->nullable();
            $table->json('active_days')->nullable();

            // Amounts: a rule uses the field its type calls for.
            $table->integer('amount_minor')->default(0);
            $table->integer('rate_minor_per_km')->default(0);
            $table->integer('percentage_bps')->default(0);
            $table->unsignedInteger('free_units')->default(0);

            $table->unsignedSmallInteger('evaluation_order')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('pickup_zone_id')->references('id')->on('zones')->cascadeOnDelete();
            $table->foreign('dropoff_zone_id')->references('id')->on('zones')->cascadeOnDelete();

            $table->index(['is_active', 'delivery_company_id', 'evaluation_order'], 'pricing_rules_resolution_index');
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
