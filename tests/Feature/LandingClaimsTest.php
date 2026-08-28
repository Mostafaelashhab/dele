<?php

namespace Tests\Feature;

use App\Domain\Matching\MatchingEngine;
use App\Domain\Pricing\PriceQuote;
use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AccountStatus;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Models\DeliveryCompany;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The landing page makes specific, checkable claims about how the platform
 * works. These tests hold the page to them, so a change to pricing, matching
 * or the fee model cannot leave a stale promise in public.
 */
class LandingClaimsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Zone::factory()->count(3)->create();
    }

    #[Test]
    public function the_ranking_panel_shows_the_weights_the_dispatcher_actually_uses(): void
    {
        $response = $this->get('/')->assertOk();

        $weights = MatchingEngine::weights();
        $total = array_sum($weights);

        foreach ($weights as $key => $weight) {
            $percentage = (int) round(($weight / $total) * 100);

            $response->assertSee(__('offer.factor.'.$key));
            $response->assertSee($percentage.'%');
        }
    }

    /**
     * The page says the network is free. This holds the product to it.
     *
     * Copy is easy to change and a config value is easy to forget, so the
     * claim is checked against what a real quote actually charges rather than
     * against another piece of text.
     */
    #[Test]
    public function the_free_claim_matches_a_product_that_charges_nothing(): void
    {
        $this->assertNull(
            config('platform.subscription'),
            'A subscription config block would contradict the free-pilot copy.'
        );

        $this->assertFalse(
            Schema::hasTable('subscriptions'),
            'A subscriptions table would contradict the free-pilot copy.'
        );

        $quote = $this->sampleQuote();

        $this->assertTrue(
            $quote->platformFee->isZero(),
            'The page says the network is free, so a quote must carry no platform fee.'
        );

        $this->assertTrue(
            $quote->companyPayout->equals($quote->total),
            'With no fee taken, the whole delivery price belongs to the company.'
        );

        $this->get('/')
            ->assertOk()
            ->assertSee(__('marketing.free.title'));

        // The subscription question moved to its own page when the landing
        // was split up; the claim still has to be answered somewhere public.
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee(__('marketing.faq.items.4.q'));
    }

    /**
     * The failure mode this guards is charging while still saying "free".
     *
     * If a fee is ever switched back on, the free-pilot copy has to come down
     * in the same change — so this fails loudly rather than letting the page
     * quietly lie about money.
     */
    #[Test]
    public function the_free_copy_is_not_shown_when_a_fee_is_configured(): void
    {
        config(['platform.pricing.platform_fee.percentage_bps' => 1200]);
        Cache::flush();

        $this->assertFalse(
            $this->sampleQuote()->platformFee->isZero(),
            'The fixture must actually charge for this test to mean anything.'
        );

        $this->get('/')
            ->assertOk()
            ->assertDontSee(__('marketing.free.title'))
            ->assertSee('12');
    }

    #[Test]
    public function the_protection_section_describes_both_proof_mechanisms(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('marketing.protection.title'))
            ->assertSee(__('marketing.protection.code_title'))
            ->assertSee(__('marketing.protection.photo_title'));
    }

    #[Test]
    public function each_audience_is_sent_to_its_own_registration_door(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('register.business'))
            ->assertSee(route('register.company'));
    }

    /**
     * The network figures are counts, not aspirations, so an empty network
     * must render honestly rather than showing invented numbers.
     */
    #[Test]
    public function network_figures_are_real_counts(): void
    {
        $this->get('/')->assertOk()->assertSee('0');

        DeliveryCompany::factory()->count(2)->create([
            'status' => AccountStatus::Active,
        ]);
        Cache::flush();

        $this->get('/')->assertOk()->assertSee('2');
    }

    /**
     * A representative quote from the engine that prices real orders.
     */
    private function sampleQuote(): PriceQuote
    {
        $zones = app(ZoneResolver::class)->activeZones();

        return app(PricingEngine::class)->quote(
            new PricingContext(
                distanceMeters: 3000,
                estimatedMinutes: 25,
                priority: DeliveryPriority::Standard,
                packageSize: PackageSize::Small,
                paymentType: PaymentType::Prepaid,
                codAmount: Money::zero(),
                pickupZone: $zones->first(),
                dropoffZone: $zones->skip(1)->first() ?? $zones->first(),
            )
        );
    }
}
