<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('scope', 32)->default('platform');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('scope');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 96)->unique();
            $table->string('name');
            $table->string('group', 64)->default('general');
            $table->timestamps();

            $table->index('group');
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id']);
        });

        // A role assignment is scoped to a tenant. The same user may be an
        // owner of one business and staff at another; nullable tenant columns
        // represent platform-wide roles.
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_type', 32)->nullable();
            $table->ulid('tenant_id')->nullable();
            $table->timestamps();

            $table->unique(['role_id', 'user_id', 'tenant_type', 'tenant_id'], 'role_user_unique_assignment');
            $table->index(['user_id', 'tenant_type', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
