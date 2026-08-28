<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A double-entry ledger, not a balance column. Balances are derived by
        // summing entries, so money can always be explained by the rows that
        // produced it. Rows are never updated once written.
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('group_id', 40);

            $table->string('account_type', 32);
            $table->string('account_id', 40)->nullable();

            $table->string('entry_type', 16);
            $table->string('category', 48);
            $table->integer('amount_minor');
            $table->string('currency', 3)->default('EGP');

            $table->ulid('order_id')->nullable();
            $table->ulid('delivery_id')->nullable();
            $table->ulid('settlement_id')->nullable();

            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('delivery_id')->references('id')->on('deliveries')->nullOnDelete();

            // Balance and statement queries always filter by account first.
            $table->index(['account_type', 'account_id', 'occurred_at'], 'ledger_account_time_index');
            $table->index(['settlement_id']);
            $table->index(['delivery_id']);
            $table->index(['group_id']);
            $table->index(['category', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
