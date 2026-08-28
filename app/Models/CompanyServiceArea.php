<?php

namespace App\Models;

use App\Domain\Shared\Support\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['delivery_company_id', 'zone_id', 'accepts_pickup', 'accepts_dropoff', 'surcharge_minor'])]
class CompanyServiceArea extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepts_pickup' => 'boolean',
            'accepts_dropoff' => 'boolean',
            'surcharge_minor' => MoneyCast::class,
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
     * @return BelongsTo<Zone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
