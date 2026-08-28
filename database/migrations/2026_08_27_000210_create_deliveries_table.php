<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A delivery is one fulfilment attempt for an order. Keeping it
        // separate from the order is what makes re-dispatch after a failure a
        // new row rather than a mutation of history.
        Schema::create('deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('order_id');
            $table->ulid('business_id');
            $table->ulid('delivery_company_id')->nullable();
            $table->ulid('rider_id')->nullable();

            // Public identifiers. Internal ULIDs never leave the system.
            $table->string('public_id', 40)->unique();
            $table->string('tracking_token', 64)->unique();

            $table->string('status', 32)->default('draft');
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('provider', 32)->default('internal');
            $table->string('provider_reference')->nullable();

            $table->unsignedInteger('distance_meters')->default(0);
            $table->unsignedSmallInteger('estimated_minutes')->default(0);
            $table->timestamp('estimated_delivery_at')->nullable();

            // Immutable price snapshot: what the business pays, what the
            // company earns, and the platform's cut, all in minor units.
            $table->string('currency', 3)->default('EGP');
            $table->unsignedInteger('price_minor')->default(0);
            $table->unsignedInteger('platform_fee_minor')->default(0);
            $table->unsignedInteger('company_payout_minor')->default(0);
            $table->unsignedInteger('rider_payout_minor')->default(0);
            $table->json('price_breakdown')->nullable();
            $table->json('matching_snapshot')->nullable();

            $table->unsignedTinyInteger('dispatch_round')->default(0);
            $table->unsignedTinyInteger('offers_sent_count')->default(0);
            $table->timestamp('searching_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('arrived_at_pickup_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('in_transit_at')->nullable();
            $table->timestamp('arrived_at_destination_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('failure_reason')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('cancelled_by', 32)->nullable();

            $table->string('proof_photo_path')->nullable();
            $table->string('proof_signature_path')->nullable();
            $table->string('received_by')->nullable();
            $table->unsignedInteger('cod_collected_minor')->default(0);
            $table->text('delivery_notes')->nullable();

            $table->boolean('financials_recorded')->default(false);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->nullOnDelete();
            $table->foreign('rider_id')->references('id')->on('riders')->nullOnDelete();

            $table->unique(['order_id', 'attempt']);
            $table->index(['status', 'created_at']);
            $table->index(['delivery_company_id', 'status'], 'deliveries_company_status_index');
            $table->index(['rider_id', 'status'], 'deliveries_rider_status_index');
            $table->index(['business_id', 'status', 'created_at'], 'deliveries_business_status_index');
            $table->index('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
