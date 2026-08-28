<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only the storage path is kept. Images live on the filesystem disk,
        // which keeps rows small and lets the disk be swapped for object
        // storage later without a schema change.
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('slug');
            $table->string('cover_path')->nullable()->after('logo_path');
        });

        Schema::table('delivery_companies', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('slug');
        });

        Schema::table('riders', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('locale');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            // A second proof slot: riders often photograph the parcel at the
            // door and the building entrance, and one image is frequently not
            // enough to settle a dispute.
            $table->string('proof_photo_secondary_path')->nullable()->after('proof_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('proof_photo_secondary_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });

        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('delivery_companies', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'cover_path']);
        });
    }
};
