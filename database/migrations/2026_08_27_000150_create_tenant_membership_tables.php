<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_users', function (Blueprint $table) {
            $table->id();
            $table->ulid('business_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 64)->default('business_staff');
            $table->string('job_title')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->ulid('delivery_company_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 64)->default('company_dispatcher');
            $table->string('job_title')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->cascadeOnDelete();
            $table->unique(['delivery_company_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
        });

        // How a business wants the network to behave on its behalf: a company
        // it prefers, or one it never wants to see again after a bad run.
        Schema::create('business_company_preferences', function (Blueprint $table) {
            $table->id();
            $table->ulid('business_id');
            $table->ulid('delivery_company_id');
            $table->string('preference', 32)->default('preferred');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('delivery_company_id')->references('id')->on('delivery_companies')->cascadeOnDelete();
            $table->unique(['business_id', 'delivery_company_id']);
            $table->index(['business_id', 'preference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_company_preferences');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('business_users');
    }
};
