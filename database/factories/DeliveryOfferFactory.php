<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\DeliveryOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOffer>
 */
class DeliveryOfferFactory extends Factory
{
    protected $model = DeliveryOffer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'delivery_company_id' => DeliveryCompany::factory(),
            'round' => 1,
            'rank' => 1,
            'status' => OfferStatus::Pending,
            'quoted_price_minor' => 2500,
            'company_payout_minor' => 2200,
            'currency' => 'EGP',
            'quoted_eta_minutes' => 25,
            'score_bps' => 7500,
            'offered_at' => now(),
            'expires_at' => now()->addSeconds(90),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'offered_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinutes(3),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => OfferStatus::Accepted,
            'responded_at' => now(),
        ]);
    }
}
