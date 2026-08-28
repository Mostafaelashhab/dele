<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'api_client_id', 'key', 'endpoint', 'request_hash', 'response_status',
    'response_body', 'resource_id', 'locked_at', 'completed_at', 'expires_at',
])]
class IdempotencyKey extends Model
{
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'response_body' => 'array',
            'locked_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * A replay is only safe to serve when the body is identical; a different
     * body under the same key is a client bug and must be surfaced as one.
     */
    public function matchesRequest(string $hash): bool
    {
        return hash_equals($this->request_hash, $hash);
    }
}
