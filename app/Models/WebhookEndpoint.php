<?php

namespace App\Models;

use App\Enums\WebhookEvent;
use Database\Factories\WebhookEndpointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Fillable(['owner_type', 'owner_id', 'api_client_id', 'name', 'url', 'secret', 'events', 'is_active'])]
#[Hidden(['secret'])]
class WebhookEndpoint extends Model
{
    /** @use HasFactory<WebhookEndpointFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::creating(function (self $endpoint): void {
            $endpoint->secret ??= 'whsec_'.Str::random(48);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'consecutive_failures' => 'integer',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'disabled_at' => 'datetime',
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
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    #[Scope]
    protected function listeningFor(Builder $query, WebhookEvent $event): Builder
    {
        return $query->where('is_active', true)
            ->whereNull('disabled_at')
            ->whereJsonContains('events', $event->value);
    }

    public function subscribesTo(WebhookEvent $event): bool
    {
        return in_array($event->value, $this->events ?? [], true);
    }

    public function rotateSecret(): string
    {
        $secret = 'whsec_'.Str::random(48);
        $this->forceFill(['secret' => $secret])->save();

        return $secret;
    }
}
