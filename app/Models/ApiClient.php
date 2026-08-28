<?php

namespace App\Models;

use App\Enums\ApiClientStatus;
use Database\Factories\ApiClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['owner_type', 'owner_id', 'name', 'status', 'scopes', 'rate_limit_per_minute', 'environment'])]
class ApiClient extends Model
{
    /** @use HasFactory<ApiClientFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApiClientStatus::class,
            'scopes' => 'array',
            'rate_limit_per_minute' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<ApiKey, $this>
     */
    public function keys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * @return HasMany<WebhookEndpoint, $this>
     */
    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', ApiClientStatus::Active);
    }

    public function rateLimit(): int
    {
        return $this->rate_limit_per_minute
            ?? (int) config('platform.api.default_rate_limit_per_minute');
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?? [];

        return $scopes === [] || in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }

    public function isBusinessClient(): bool
    {
        return $this->owner_type === (new Business)->getMorphClass();
    }
}
