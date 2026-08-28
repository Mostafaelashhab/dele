<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->after('id')->unique();
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('locale', 5)->default('ar')->after('phone');
            $table->boolean('is_active')->default(true)->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes();

            $table->index(['is_active']);
        });

        // Email stays the login identifier, but riders are frequently
        // identified by phone alone, so it must be unique when present.
        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['is_active']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'ulid', 'phone', 'locale', 'is_active', 'last_login_at', 'last_login_ip',
            ]);
        });
    }
};
