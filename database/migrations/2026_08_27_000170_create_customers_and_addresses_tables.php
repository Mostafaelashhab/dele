<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers are a convenience record for repeat recipients. Orders keep
        // their own frozen copy of recipient details, so deleting a customer
        // never rewrites delivery history.
        Schema::create('customers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('business_id')->nullable();
            $table->string('name');
            $table->string('phone', 32);
            $table->string('alternate_phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('orders_count')->default(0);
            $table->timestamp('last_ordered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'phone']);
            $table->index('phone');
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('owner_type', 64);
            $table->ulid('owner_id');
            $table->ulid('zone_id')->nullable();

            $table->string('label')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('address_line');
            $table->string('building', 64)->nullable();
            $table->string('floor', 32)->nullable();
            $table->string('apartment', 32)->nullable();
            $table->string('landmark')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->default('Banha');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('zone_id')->references('id')->on('zones')->nullOnDelete();
            $table->index(['owner_type', 'owner_id', 'is_default'], 'addresses_owner_index');
            $table->index('zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('customers');
    }
};
