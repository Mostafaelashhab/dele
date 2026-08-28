<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('category', 64)->default('retail');
            $table->string('contact_person')->nullable();
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->string('status', 32)->default('pending');

            $table->ulid('default_zone_id')->nullable();
            $table->string('address_line')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Per-tenant overrides. Null means "inherit the platform default",
            // which keeps commercial terms out of the code.
            $table->unsignedSmallInteger('platform_fee_bps')->nullable();
            $table->string('default_priority', 32)->default('standard');
            $table->string('matching_strategy', 32)->nullable();
            $table->unsignedInteger('credit_limit_minor')->default(0);
            $table->boolean('api_enabled')->default(false);
            $table->json('settings')->nullable();

            $table->timestamp('onboarded_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('default_zone_id')->references('id')->on('zones')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
