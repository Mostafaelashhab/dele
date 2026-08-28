<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('delivery_id');
            $table->ulid('rider_id');
            $table->ulid('delivery_company_id');

            $table->string('status', 32)->default('offered');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('pickup_distance_meters')->nullable();
            $table->unsignedSmallInteger('estimated_pickup_minutes')->nullable();
            $table->unsignedInteger('payout_minor')->default(0);
            $table->string('currency', 3)->default('EGP');

            $table->timestamp('offered_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('delivery_id')->references('id')->on('deliveries')->cascadeOnDelete();
            $table->foreign('rider_id')->references('id')->on('riders')->cascadeOnDelete();
            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->cascadeOnDelete();

            $table->index(['rider_id', 'status'], 'assignments_rider_status_index');
            $table->index(['delivery_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_assignments');
    }
};
