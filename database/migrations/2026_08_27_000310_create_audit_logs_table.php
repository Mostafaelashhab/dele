<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('actor_type', 32)->default('system');
            $table->string('actor_id', 40)->nullable();
            $table->string('actor_label')->nullable();

            $table->string('action', 64);
            $table->string('entity_type', 64)->nullable();
            $table->string('entity_id', 40)->nullable();
            $table->string('description')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('context')->nullable();

            // Which tenant the action touched, so a tenant can be shown its own
            // audit trail without exposing anyone else's.
            $table->string('tenant_type', 32)->nullable();
            $table->ulid('tenant_id')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['entity_type', 'entity_id', 'created_at'], 'audit_entity_index');
            $table->index(['actor_type', 'actor_id', 'created_at'], 'audit_actor_index');
            $table->index(['tenant_type', 'tenant_id', 'created_at'], 'audit_tenant_index');
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
