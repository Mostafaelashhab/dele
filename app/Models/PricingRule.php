<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PricingRuleType;
use Database\Factories\PricingRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'name', 'type', 'delivery_company_id', 'business_id', 'pickup_zone_id',
    'dropoff_zone_id', 'priority', 'package_size', 'min_distance_meters',
    'max_distance_meters', 'active_from', 'active_until', 'active_days',
    'amount_minor', 'rate_minor_per_km', 'percentage_bps', 'free_units',
    'evaluation_order', 'is_active', 'starts_at', 'ends_at', 'conditions',
])]
class PricingRule extends Model
{
    /** @use HasFactory<PricingRuleFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PricingRuleType::class,
            'priority' => DeliveryPriority::class,
            'package_size' => PackageSize::class,
            'amount_minor' => MoneyCast::class,
            'rate_minor_per_km' => 'integer',
            'percentage_bps' => 'integer',
            'free_units' => 'integer',
            'min_distance_meters' => 'integer',
            'max_distance_meters' => 'integer',
            'evaluation_order' => 'integer',
            'is_active' => 'boolean',
            'active_days' => 'array',
            'conditions' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DeliveryCompany, $this>
     */
    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function pickupZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'pickup_zone_id');
    }

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function dropoffZone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'dropoff_zone_id');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * Rules owned by a company, plus the platform defaults it inherits.
     */
    #[Scope]
    protected function forCompany(Builder $query, ?string $companyId): Builder
    {
        return $query->where(function (Builder $q) use ($companyId): void {
            $q->whereNull('delivery_company_id');

            if ($companyId !== null) {
                $q->orWhere('delivery_company_id', $companyId);
            }
        });
    }

    /**
     * Whether this rule's time-of-day and day-of-week window includes $moment.
     */
    public function isActiveAt(Carbon $moment): bool
    {
        if (filled($this->active_days)) {
            $day = mb_strtolower($moment->format('l'));

            if (! in_array($day, array_map('mb_strtolower', $this->active_days), true)) {
                return false;
            }
        }

        if (blank($this->active_from) || blank($this->active_until)) {
            return true;
        }

        $time = $moment->format('H:i:s');
        $from = $this->normaliseTime($this->active_from);
        $until = $this->normaliseTime($this->active_until);

        // A window such as 22:00 -> 02:00 wraps past midnight.
        return $until >= $from
            ? $time >= $from && $time <= $until
            : $time >= $from || $time <= $until;
    }

    /**
     * Whether the rule's distance band includes the given trip length.
     */
    public function matchesDistance(int $meters): bool
    {
        if ($this->min_distance_meters !== null && $meters < $this->min_distance_meters) {
            return false;
        }

        return $this->max_distance_meters === null || $meters <= $this->max_distance_meters;
    }

    /**
     * How tightly this rule is scoped. Used to pick a winner when more than
     * one rule of the same type matches: the most specific one wins.
     */
    public function specificity(): int
    {
        return collect([
            $this->delivery_company_id, $this->business_id, $this->pickup_zone_id,
            $this->dropoff_zone_id, $this->priority, $this->package_size,
            $this->min_distance_meters, $this->max_distance_meters, $this->active_from,
        ])->filter(fn ($value) => $value !== null)->count();
    }

    private function normaliseTime(string $value): string
    {
        return mb_strlen($value) === 5 ? $value.':00' : $value;
    }
}
