<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('owner_type', 32);
            $table->ulid('owner_id');
            $table->string('name');
            $table->string('status', 32)->default('active');
            $table->json('scopes')->nullable();
            $table->unsignedSmallInteger('rate_limit_per_minute')->nullable();
            $table->string('environment', 16)->default('live');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_type', 'owner_id']);
            $table->index('status');
        });

        // Only a hash is stored. The plaintext key is shown exactly once, at
        // issue time, and cannot be recovered afterwards.
        Schema::create('api_keys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('api_client_id');
            $table->string('name');
            $table->string('prefix', 24)->unique();
            $table->string('key_hash', 64)->unique();
            $table->string('last_four', 8);
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('api_client_id')->references('id')->on('api_clients')->cascadeOnDelete();
            $table->index(['api_client_id', 'revoked_at']);
        });

        Schema::create('api_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ulid('api_client_id')->nullable();
            $table->ulid('api_key_id')->nullable();
            $table->string('method', 10);
            $table->string('path');
            $table->string('route_name')->nullable();
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->string('request_id', 40)->nullable();
            $table->json('request_summary')->nullable();
            $table->json('error')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['api_client_id', 'created_at'], 'api_requests_client_time_index');
            $table->index(['status_code', 'created_at']);
            $table->index('created_at');
        });

        // Replay protection for POST endpoints. A repeated key returns the
        // stored response instead of creating a second delivery.
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('api_client_id');
            $table->string('key', 128);
            $table->string('endpoint', 128);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->string('resource_id', 40)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('api_client_id')->references('id')->on('api_clients')->cascadeOnDelete();
            $table->unique(['api_client_id', 'key']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('api_requests');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('api_clients');
    }
};
