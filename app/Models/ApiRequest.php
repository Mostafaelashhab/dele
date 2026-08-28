<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'api_client_id', 'api_key_id', 'method', 'path', 'route_name', 'status_code',
    'duration_ms', 'ip_address', 'user_agent', 'idempotency_key', 'request_id',
    'request_summary', 'error',
])]
class ApiRequest extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'duration_ms' => 'integer',
            'request_summary' => 'array',
            'error' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    public function wasSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }
}
