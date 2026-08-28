<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How a business wants the network biased on its behalf. "blocked" is a hard
 * exclusion applied before scoring; "preferred" is a scoring bonus.
 */
#[Fillable(['business_id', 'delivery_company_id', 'preference', 'priority', 'reason'])]
class BusinessCompanyPreference extends Model
{
    public const PREFERRED = 'preferred';

    public const BLOCKED = 'blocked';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['priority' => 'integer'];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<DeliveryCompany, $this>
     */
    public function deliveryCompany(): BelongsTo
    {
        return $this->belongsTo(DeliveryCompany::class);
    }
}
