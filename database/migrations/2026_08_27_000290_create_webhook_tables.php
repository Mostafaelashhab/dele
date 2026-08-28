<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('owner_type', 32);
            $table->ulid('owner_id');
            $table->ulid('api_client_id')->nullable();

            $table->string('name')->nullable();
            $table->string('url');
            $table->string('secret', 96);
            $table->json('events');
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();

            $table->foreign('api_client_id')->references('id')->on('api_clients')->nullOnDelete();
            $table->index(['owner_type', 'owner_id', 'is_active'], 'webhook_endpoints_owner_index');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('webhook_endpoint_id');
            $table->string('event', 64);
            $table->string('event_id', 40);
            $table->json('payload');

            $table->string('status', 32)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->string('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('webhook_endpoint_id')->references('id')->on('webhook_endpoints')->cascadeOnDelete();

            $table->unique(['webhook_endpoint_id', 'event_id'], 'webhook_deliveries_dedupe');
            $table->index(['status', 'next_attempt_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
    }
};
