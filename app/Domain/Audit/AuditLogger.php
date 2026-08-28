<?php

namespace App\Domain\Audit;

use App\Domain\Deliveries\Actor;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Records who did what to which record.
 *
 * Writes go through a single entry point so every audit row has the same
 * shape, and so sensitive attributes are stripped in exactly one place rather
 * than at each of the dozens of call sites.
 */
class AuditLogger
{
    /**
     * Attribute names never written to the audit trail, in any casing.
     *
     * @var array<int, string>
     */
    private const REDACTED = [
        'password', 'password_confirmation', 'remember_token', 'secret',
        'key_hash', 'api_key', 'token', 'tracking_token',
    ];

    public function __construct(
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>  $context
     */
    public function log(
        AuditAction $action,
        ?Model $entity = null,
        ?Actor $actor = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $context = [],
        ?string $tenantType = null,
        ?string $tenantId = null,
    ): AuditLog {
        $actor ??= Actor::current();

        return AuditLog::create([
            'action' => $action,
            'entity_type' => $entity ? $entity::class : null,
            'entity_id' => $entity ? (string) $entity->getKey() : null,
            'description' => $description,
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'context' => $context === [] ? null : $context,
            'tenant_type' => $tenantType,
            'tenant_id' => $tenantId,
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 255),
            ...$actor->toArray(),
        ]);
    }

    /**
     * Log a model update, capturing only the attributes that actually changed.
     */
    public function logChanges(
        AuditAction $action,
        Model $entity,
        ?Actor $actor = null,
        ?string $description = null,
        array $context = [],
    ): ?AuditLog {
        $changes = $entity->getChanges();

        unset($changes['updated_at']);

        if ($changes === []) {
            return null;
        }

        $original = array_intersect_key($entity->getOriginal(), $changes);

        return $this->log(
            action: $action,
            entity: $entity,
            actor: $actor,
            description: $description,
            oldValues: $original,
            newValues: $changes,
            context: $context,
        );
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            if (in_array(mb_strtolower((string) $key), self::REDACTED, true)) {
                $values[$key] = '[redacted]';
            }
        }

        return $values;
    }
}
