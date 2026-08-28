<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference', 32)->unique();
            $table->string('party_type', 32);
            $table->string('party_id', 40);

            $table->string('period', 32)->default('weekly');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 32)->default('draft');

            $table->unsignedInteger('deliveries_count')->default(0);
            $table->integer('gross_minor')->default(0);
            $table->integer('platform_fee_minor')->default(0);
            $table->integer('cod_collected_minor')->default(0);
            $table->integer('adjustments_minor')->default(0);
            $table->integer('net_payable_minor')->default(0);
            $table->string('currency', 3)->default('EGP');

            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['party_type', 'party_id', 'period_start', 'period_end'], 'settlements_party_period_unique');
            $table->index(['status', 'period_end']);
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreign('settlement_id')->references('id')->on('settlements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
        });

        Schema::dropIfExists('settlements');
    }
};
