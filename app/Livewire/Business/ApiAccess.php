<?php

namespace App\Livewire\Business;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\ApiClientStatus;
use App\Enums\AuditAction;
use App\Enums\WebhookEvent;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\ApiClient;
use App\Models\ApiKey;
use App\Models\WebhookEndpoint;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Self-service API credentials and webhook endpoints.
 *
 * A freshly minted key is held in a public property for exactly one render so
 * the owner can copy it; it is never persisted in plaintext and never
 * retrievable again.
 */
class ApiAccess extends Component
{
    use UsesPortalLayout;

    public string $clientName = '';

    public string $keyName = '';

    public ?string $revealedKey = null;

    public string $webhookUrl = '';

    /**
     * @var array<int, string>
     */
    public array $webhookEvents = [];

    public ?string $revealedSecret = null;

    /**
     * @return Collection<int, ApiClient>
     */
    #[Computed]
    public function clients(): Collection
    {
        return app(CurrentTenant::class)->businessOrFail()
            ->apiClients()
            ->with('keys')
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, WebhookEndpoint>
     */
    #[Computed]
    public function endpoints(): Collection
    {
        return app(CurrentTenant::class)->businessOrFail()
            ->webhookEndpoints()
            ->latest()
            ->get();
    }

    public function createClient(): void
    {
        $business = app(CurrentTenant::class)->businessOrFail();

        $validated = $this->validate([
            'clientName' => ['required', 'string', 'max:120'],
        ]);

        $client = $business->apiClients()->create([
            'name' => $validated['clientName'],
            'status' => ApiClientStatus::Active,
            'environment' => app()->isProduction() ? 'live' : 'test',
        ]);

        $business->forceFill(['api_enabled' => true])->save();

        $issued = ApiKey::issue($client, __('app.nav.api'), null, auth()->id());
        $this->revealedKey = $issued['plain_text'];

        app(AuditLogger::class)->log(
            action: AuditAction::ApiKeyIssued,
            entity: $issued['model'],
            description: 'API client and key created.',
            tenantType: 'business',
            tenantId: $business->id,
        );

        $this->reset('clientName');
        unset($this->clients);
    }

    public function issueKey(string $clientId): void
    {
        $client = $this->findClient($clientId);

        $issued = ApiKey::issue($client, $this->keyName ?: __('app.nav.api'), null, auth()->id());
        $this->revealedKey = $issued['plain_text'];

        app(AuditLogger::class)->log(
            action: AuditAction::ApiKeyIssued,
            entity: $issued['model'],
            tenantType: 'business',
            tenantId: $client->owner_id,
        );

        $this->reset('keyName');
        unset($this->clients);
    }

    public function revokeKey(string $keyId): void
    {
        $key = ApiKey::query()
            ->whereKey($keyId)
            ->whereIn('api_client_id', $this->clients()->pluck('id'))
            ->firstOrFail();

        $key->revoke();

        app(AuditLogger::class)->log(
            action: AuditAction::ApiKeyRevoked,
            entity: $key,
            tenantType: 'business',
            tenantId: app(CurrentTenant::class)->businessOrFail()->id,
        );

        unset($this->clients);
    }

    public function createWebhook(): void
    {
        $business = app(CurrentTenant::class)->businessOrFail();

        $validated = $this->validate([
            'webhookUrl' => ['required', 'url:https', 'max:500'],
            'webhookEvents' => ['required', 'array', 'min:1'],
            'webhookEvents.*' => [Rule::in(WebhookEvent::values())],
        ]);

        $endpoint = $business->webhookEndpoints()->create([
            'url' => $validated['webhookUrl'],
            'events' => $validated['webhookEvents'],
            'is_active' => true,
        ]);

        // Shown once, in the same way as an API key.
        $this->revealedSecret = $endpoint->secret;

        $this->reset(['webhookUrl', 'webhookEvents']);
        unset($this->endpoints);
    }

    public function deleteWebhook(string $id): void
    {
        app(CurrentTenant::class)->businessOrFail()
            ->webhookEndpoints()
            ->whereKey($id)
            ->firstOrFail()
            ->delete();

        unset($this->endpoints);
    }

    private function findClient(string $id): ApiClient
    {
        return app(CurrentTenant::class)->businessOrFail()
            ->apiClients()
            ->whereKey($id)
            ->firstOrFail();
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.api-access', [
            'availableEvents' => WebhookEvent::cases(),
        ], __('app.nav.api'));
    }
}
