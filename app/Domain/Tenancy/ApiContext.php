<?php

namespace App\Domain\Tenancy;

use App\Models\ApiClient;
use App\Models\ApiKey;
use RuntimeException;

/**
 * The authenticated API caller for this request.
 */
class ApiContext
{
    private ?ApiClient $client = null;

    private ?ApiKey $key = null;

    private string $requestId;

    public function __construct()
    {
        $this->requestId = 'req_'.bin2hex(random_bytes(12));
    }

    public function authenticate(ApiClient $client, ApiKey $key): void
    {
        $this->client = $client;
        $this->key = $key;
    }

    public function client(): ?ApiClient
    {
        return $this->client;
    }

    public function clientOrFail(): ApiClient
    {
        return $this->client ?? throw new RuntimeException('No API client authenticated for this request.');
    }

    public function key(): ?ApiKey
    {
        return $this->key;
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function isAuthenticated(): bool
    {
        return $this->client !== null;
    }
}
