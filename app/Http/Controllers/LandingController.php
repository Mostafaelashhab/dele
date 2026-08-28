<?php

namespace App\Http\Controllers;

use App\Domain\Matching\MatchingEngine;
use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AccountStatus;
use App\Enums\DeliveryPriority;
use App\Enums\DeliveryStatus;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Enums\RiderStatus;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The public landing page.
 *
 * Signed-in users are sent to their portal rather than shown marketing they
 * have already been sold. Everything the page claims is computed here from
 * the running system: the prices come from the real pricing engine, and the
 * network figures are counts, not aspirations.
 */
class LandingController extends Controller
{
    private const CACHE_TTL_MINUTES = 10;

    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($request->user()->homeRoute());
        }

        $zones = app(ZoneResolver::class)->activeZones();

        return view('public.landing', [
            'zones' => $zones,
            'companyCount' => DeliveryCompany::query()->where('status', AccountStatus::Active)->count(),
            'tiers' => $this->tiers($zones),
            'networkStats' => $this->networkStats(),
            'rankingWeights' => $this->rankingWeights(),
            'fees' => $this->fees($zones),
        ]);
    }

    /**
     * The three delivery priorities, priced by the real engine.
     *
     * A representative short hop across the city centre is quoted for each,
     * so the figures on the page and the figures a business will actually be
     * charged come from the same code. Cached briefly because the landing page
     * is the most-hit route and the quote does not change minute to minute.
     *
     * @param  Collection<int, Zone>  $zones
     * @return array<int, array<string, mixed>>
     */
    private function tiers(Collection $zones): array
    {
        return Cache::remember('landing.tiers.'.app()->getLocale(), now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($zones): array {
            $engine = app(PricingEngine::class);

            $pickup = $zones->first();
            $dropoff = $zones->skip(1)->first() ?? $pickup;

            /*
             * Ordered slowest and cheapest to fastest and dearest.
             *
             * These are not plans a business subscribes to — they are a choice
             * made per order — so they are presented as one scale rather than
             * as three competing tiers, and the middle one carries no
             * "most popular" badge. Standard is marked as the default because
             * that is what an order gets when nothing is chosen, which is a
             * fact about the product rather than a nudge.
             */
            $definitions = [
                [
                    'priority' => DeliveryPriority::Scheduled,
                    'icon' => 'clock',
                    'default' => false,
                    'points' => 'scheduled',
                ],
                [
                    'priority' => DeliveryPriority::Standard,
                    'icon' => 'package',
                    'default' => true,
                    'points' => 'standard',
                ],
                [
                    'priority' => DeliveryPriority::Express,
                    'icon' => 'navigation',
                    'default' => false,
                    'points' => 'express',
                ],
            ];

            $tiers = array_map(function (array $definition) use ($engine, $pickup, $dropoff): array {
                $quote = $engine->quote(new PricingContext(
                    distanceMeters: 3000,
                    estimatedMinutes: 25,
                    priority: $definition['priority'],
                    packageSize: PackageSize::Small,
                    paymentType: PaymentType::Prepaid,
                    codAmount: Money::zero(),
                    pickupZone: $pickup,
                    dropoffZone: $dropoff,
                ));

                return [
                    'name' => $definition['priority']->label(),
                    'icon' => $definition['icon'],
                    'default' => $definition['default'],
                    'price' => $quote->total->format(false),
                    'minor' => $quote->total->minor,
                    'body' => __('priority.'.$definition['points'].'.body'),
                    'points' => __('priority.'.$definition['points'].'.points'),
                ];
            }, $definitions);

            // The difference against standard is what makes this read as one
            // scale: the same delivery, moved sooner or later.
            $baseline = collect($tiers)->firstWhere('default', true)['minor'] ?? 0;

            return array_map(function (array $tier) use ($baseline): array {
                $tier['delta_minor'] = $tier['minor'] - $baseline;

                return $tier;
            }, $tiers);
        });
    }

    /**
     * What the platform actually charges, quoted by the engine that charges it.
     *
     * The page claims there is no subscription, which is a claim about the
     * whole system rather than about a price list — there is no subscription
     * concept in the product at all. What there is, is a share of each
     * delivery, so this returns a real quote broken into its three parts plus
     * the same fee projected across a few order volumes. Nothing here is
     * typed in by hand; change the rate in configuration and the page moves.
     *
     * @param  Collection<int, Zone>  $zones
     * @return array<string, mixed>
     */
    private function fees(Collection $zones): array
    {
        return Cache::remember('landing.fees.'.app()->getLocale(), now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($zones): array {
            $engine = app(PricingEngine::class);

            $quote = $engine->quote(new PricingContext(
                distanceMeters: 3000,
                estimatedMinutes: 25,
                priority: DeliveryPriority::Standard,
                packageSize: PackageSize::Small,
                paymentType: PaymentType::Prepaid,
                codAmount: Money::zero(),
                pickupZone: $zones->first(),
                dropoffZone: $zones->skip(1)->first() ?? $zones->first(),
            ));

            // The volumes start at zero deliberately: a zero-height first
            // column is the entire argument this section is making.
            $volumes = [0, 50, 150, 300];

            return [
                // The page reads this rather than the copy deciding for
                // itself: a "free" claim must be a fact about the engine.
                'charges' => ! $quote->platformFee->isZero(),
                'rate_percent' => rtrim(rtrim(number_format(
                    (int) config('platform.pricing.platform_fee.percentage_bps') / 100,
                    2
                ), '0'), '.'),
                'quote' => [
                    'total' => $quote->total->format(false),
                    'fee' => $quote->platformFee->format(false),
                    'company' => $quote->companyPayout->format(false),
                ],
                'volumes' => array_map(fn (int $orders) => [
                    'label' => $orders === 0
                        ? '0'
                        : number_format($orders),
                    'full' => $orders.' '.__('marketing.fees.chart_orders'),
                    'orders' => $orders,
                    'values' => [
                        'fee' => $quote->platformFee->times($orders)->toMajor(),
                    ],
                ], $volumes),
            ];
        });
    }

    /**
     * The live matching weights, as shown to the companies they rank.
     *
     * Read straight from the engine rather than restated, so the page cannot
     * describe an allocation rule the dispatcher does not apply. Publishing
     * them is deliberate: a company that knows it is judged on response time
     * and proximity can act on that, which is a fairer arrangement than the
     * relationships it replaces.
     *
     * @return array<int, array{label: string, percentage: int}>
     */
    private function rankingWeights(): array
    {
        $weights = MatchingEngine::weights();
        $total = array_sum($weights) ?: 1;

        return collect($weights)
            ->map(fn (float $weight, string $key) => [
                'label' => __('offer.factor.'.$key),
                'percentage' => (int) round(($weight / $total) * 100),
            ])
            ->sortByDesc('percentage')
            ->values()
            ->all();
    }

    /**
     * Counts the platform can stand behind.
     *
     * Deliberately not testimonials: a quote attributed to someone who never
     * said it would be fabricated, and this page is public and commercial.
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function networkStats(): array
    {
        return Cache::remember('landing.stats.'.app()->getLocale(), now()->addMinutes(self::CACHE_TTL_MINUTES), function (): array {
            $delivered = Delivery::query()->where('status', DeliveryStatus::Delivered)->count();

            $durations = Delivery::query()
                ->where('status', DeliveryStatus::Delivered)
                ->whereNotNull('delivered_at')
                ->latest('delivered_at')
                ->limit(500)
                ->get(['created_at', 'delivered_at'])
                ->map(fn (Delivery $delivery) => $delivery->totalMinutes())
                ->filter();

            return [
                [
                    'label' => __('marketing.network.delivered'),
                    'value' => number_format($delivered),
                ],
                [
                    'label' => __('marketing.network.companies'),
                    'value' => number_format(
                        DeliveryCompany::query()->where('status', AccountStatus::Active)->count()
                    ),
                ],
                [
                    'label' => __('marketing.network.riders'),
                    'value' => number_format(
                        Rider::query()->whereNot('status', RiderStatus::Suspended)->count()
                    ),
                ],
                [
                    'label' => __('marketing.network.minutes'),
                    // Shown as a dash rather than a zero until there is enough
                    // history for the average to mean anything.
                    'value' => $durations->isEmpty() ? '—' : number_format((int) round($durations->avg())),
                ],
            ];
        });
    }
}
