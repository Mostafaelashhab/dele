<?php

namespace Database\Factories;

use App\Enums\LedgerAccountType;
use App\Enums\SettlementStatus;
use App\Models\DeliveryCompany;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'CMP-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'party_type' => LedgerAccountType::DeliveryCompany,
            'party_id' => DeliveryCompany::factory(),
            'period' => 'weekly',
            'period_start' => today()->subWeek()->startOfWeek(),
            'period_end' => today()->subWeek()->endOfWeek(),
            'status' => SettlementStatus::Open,
            'currency' => 'EGP',
        ];
    }
}
