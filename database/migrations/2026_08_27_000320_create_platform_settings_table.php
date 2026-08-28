<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Runtime overrides for values that also exist in config/platform.php.
        // Config is the default; a row here is an operator decision that must
        // survive a deploy and be changeable without one.
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key', 128)->primary();
            $table->json('value');
            $table->string('group', 64)->default('general');
            $table->string('type', 32)->default('string');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
