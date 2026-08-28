<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A short code the recipient reads off their tracking page and says to the
 * rider, who types it in to close the delivery.
 *
 * It is the second way of proving a handover happened. A photo proves a
 * parcel reached a doorstep; a code proves it reached the person holding the
 * tracking link — which is the stronger claim, and the one a recipient can
 * make without being photographed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            // Short and spoken aloud, so it is stored as a string to keep any
            // leading zero the generator produces.
            $table->string('confirmation_code', 8)->nullable()->after('tracking_token');
            $table->timestamp('confirmation_code_verified_at')->nullable()->after('received_by');
            $table->unsignedTinyInteger('confirmation_attempts')->default(0)->after('confirmation_code_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmation_code',
                'confirmation_code_verified_at',
                'confirmation_attempts',
            ]);
        });
    }
};
