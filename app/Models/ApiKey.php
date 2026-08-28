<?php

namespace App\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Only the SHA-256 hash of a key is persisted. The plaintext is returned once,
 * at issue time, and is unrecoverable afterwards.
 */
#[Fillable(['api_client_id', 'name', 'prefix', 'key_hash', 'last_four', 'expires_at', 'created_by_user_id'])]
#[Hidden(['key_hash'])]
class ApiKey extends Model
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Mint a new key. The returned plaintext is the only copy that will ever
     * exist; the caller must show it to the user immediately.
     *
     * @return array{model: self, plain_text: string}
     */
    public static function issue(ApiClient $client, string $name, ?\DateTimeInterface $expiresAt = null, ?int $createdBy = null): array
    {
        $prefix = config('platform.api.key_prefix').'_'.Str::lower(Str::random(12));
        $secret = Str::random(40);
        $plainText = $prefix.'.'.$secret;

        $key = self::create([
            'api_client_id' => $client->id,
            'name' => $name,
            'prefix' => $prefix,
            'key_hash' => hash('sha256', $plainText),
            'last_four' => Str::substr($secret, -4),
            'expires_at' => $expiresAt,
            'created_by_user_id' => $createdBy,
        ]);

        return ['model' => $key, 'plain_text' => $plainText];
    }

    /**
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    #[Scope]
    protected function usable(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function masked(): string
    {
        return $this->prefix.'.'.str_repeat('•', 36).$this->last_four;
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}
