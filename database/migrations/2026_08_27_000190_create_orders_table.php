<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('business_id');
            $table->ulid('customer_id')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulid('api_client_id')->nullable();

            // The business's own identifier, unique within that business only.
            $table->string('reference', 64)->nullable();
            $table->string('number', 24)->unique();
            $table->string('status', 32)->default('draft');

            // Frozen copies of both ends of the journey. See LocationSnapshot.
            $table->json('pickup');
            $table->json('dropoff');
            $table->ulid('pickup_zone_id')->nullable();
            $table->ulid('dropoff_zone_id')->nullable();

            $table->string('priority', 32)->default('standard');
            $table->string('package_size', 32)->default('small');
            $table->unsignedInteger('package_weight_grams')->nullable();
            $table->string('payment_type', 32)->default('prepaid');
            $table->unsignedInteger('cod_amount_minor')->default(0);
            $table->unsignedInteger('declared_value_minor')->default(0);
            $table->string('currency', 3)->default('EGP');

            $table->text('notes')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->unsignedTinyInteger('delivery_attempts')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('pickup_zone_id')->references('id')->on('zones')->nullOnDelete();
            $table->foreign('dropoff_zone_id')->references('id')->on('zones')->nullOnDelete();

            // A business must be able to replay its own reference safely; the
            // partial-uniqueness is enforced in application code because the
            // column is nullable across drivers.
            $table->index(['business_id', 'reference']);
            $table->index(['business_id', 'status', 'created_at'], 'orders_business_status_index');
            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
