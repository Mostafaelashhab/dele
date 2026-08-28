<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only. Nothing in the application updates or deletes a row
        // here: the timeline a customer sees and the record an operator
        // audits are the same rows.
        Schema::create('order_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('order_id');
            $table->ulid('delivery_id')->nullable();

            $table->string('type', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();

            $table->string('actor_type', 32)->default('system');
            $table->string('actor_id', 40)->nullable();
            $table->string('actor_label')->nullable();

            $table->json('payload')->nullable();
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('delivery_id')->references('id')->on('deliveries')->cascadeOnDelete();

            $table->index(['order_id', 'occurred_at'], 'order_events_order_time_index');
            $table->index(['delivery_id', 'occurred_at'], 'order_events_delivery_time_index');
            $table->index(['type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
    }
};
