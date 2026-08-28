<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The marketplace record: what the platform asked each company, what
        // it scored, what it answered, and how long it took. This table is the
        // raw material for every acceptance-rate and responsiveness metric.
        Schema::create('delivery_offers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('delivery_id');
            $table->ulid('delivery_company_id');

            $table->unsignedTinyInteger('round')->default(1);
            $table->unsignedTinyInteger('rank')->default(1);
            $table->string('status', 32)->default('pending');

            $table->unsignedInteger('quoted_price_minor');
            $table->unsignedInteger('company_payout_minor');
            $table->string('currency', 3)->default('EGP');
            $table->unsignedSmallInteger('quoted_eta_minutes')->default(0);

            $table->unsignedSmallInteger('score_bps')->default(0);
            $table->json('score_breakdown')->nullable();

            $table->timestamp('offered_at');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('delivery_id')->references('id')->on('deliveries')->cascadeOnDelete();
            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->cascadeOnDelete();

            $table->unique(['delivery_id', 'delivery_company_id', 'round'], 'delivery_offers_unique_round');
            // The company inbox query, and the expiry sweeper's query.
            $table->index(['delivery_company_id', 'status', 'expires_at'], 'delivery_offers_inbox_index');
            $table->index(['status', 'expires_at']);
            $table->index('delivery_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_offers');
    }
};
