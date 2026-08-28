<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three additions that share one idea: not every participant is a company
 * with a shopfront, and not every delivery came from the dispatcher.
 *
 * A rider working alone is modelled as a delivery company with exactly one
 * rider rather than as a new kind of participant. That is not a shortcut —
 * matching, offers, service areas, the ledger and settlements are all built
 * around a company, and teaching every one of them about a second kind of
 * carrier would put the most heavily tested part of the product at risk to
 * express something the existing model already expresses. The flag here is
 * what lets the interface tell the truth about who someone is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_companies', function (Blueprint $table): void {
            // A one-person operation. Shown differently, dispatched identically.
            $table->boolean('is_solo')->default(false)->after('provider');
        });

        Schema::table('riders', function (Blueprint $table): void {
            // Identity documents, required of a rider who has no company
            // vouching for them. Kept as private paths, never public URLs.
            $table->string('id_card_front_path')->nullable()->after('photo_path');
            $table->string('id_card_back_path')->nullable()->after('id_card_front_path');
            $table->timestamp('identity_verified_at')->nullable()->after('id_card_back_path');
            $table->string('identity_rejected_reason')->nullable()->after('identity_verified_at');
        });

        Schema::table('deliveries', function (Blueprint $table): void {
            /*
             * A job the company already had, entered so the customer gets a
             * tracking link and the handover is recorded.
             *
             * It never passes through matching — there is nothing to match, the
             * carrier is already decided — so it is marked rather than left to
             * be inferred from an absent dispatch round.
             */
            $table->boolean('is_external')->default(false)->after('provider');
            $table->index(['delivery_company_id', 'is_external'], 'deliveries_company_external_index');
        });

        Schema::table('businesses', function (Blueprint $table): void {
            // An individual sending a parcel is a business of one, for the
            // same reason a solo rider is a company of one.
            $table->boolean('is_individual')->default(false)->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_companies', fn (Blueprint $table) => $table->dropColumn('is_solo'));

        Schema::table('riders', fn (Blueprint $table) => $table->dropColumn([
            'id_card_front_path', 'id_card_back_path', 'identity_verified_at', 'identity_rejected_reason',
        ]));

        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropIndex('deliveries_company_external_index');
            $table->dropColumn('is_external');
        });

        Schema::table('businesses', fn (Blueprint $table) => $table->dropColumn('is_individual'));
    }
};
