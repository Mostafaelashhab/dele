<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Something the recipient says went wrong, raised from the public
        // tracking page. Unauthenticated by nature: the tracking token is the
        // only credential anyone holds, so the row records where the report
        // came from as well as what it said.
        Schema::create('delivery_issues', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('order_id');
            $table->ulid('delivery_id');

            // Whoever was carrying it when the report was made. Kept as its
            // own column rather than read through the delivery: a redispatch
            // must not quietly move a complaint onto a company that had
            // nothing to do with it.
            $table->ulid('delivery_company_id')->nullable();
            $table->ulid('rider_id')->nullable();

            $table->string('category', 32);
            $table->string('status', 16)->default('open');
            $table->text('note')->nullable();

            // The delivery's state at the moment of the report. A complaint
            // read three hours later is unintelligible without it.
            $table->string('delivery_status', 32);

            $table->string('reporter_ip', 45)->nullable();

            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('delivery_id')->references('id')->on('deliveries')->cascadeOnDelete();
            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->nullOnDelete();
            $table->foreign('rider_id')->references('id')->on('riders')->nullOnDelete();

            $table->index(['delivery_id', 'created_at'], 'delivery_issues_delivery_index');
            $table->index(['delivery_company_id', 'status'], 'delivery_issues_company_status_index');
            $table->index(['status', 'created_at'], 'delivery_issues_open_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_issues');
    }
};
