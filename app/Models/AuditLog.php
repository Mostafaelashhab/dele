<?php

namespace App\Models;

use App\Enums\AuditAction;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'actor_type', 'actor_id', 'actor_label', 'action', 'entity_type', 'entity_id',
    'description', 'old_values', 'new_values', 'context', 'tenant_type',
    'tenant_id', 'ip_address', 'user_agent',
])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'context' => 'array',
        ];
    }

    #[Scope]
    protected function forEntity(Builder $query, Model $entity): Builder
    {
        return $query->where('entity_type', $entity::class)
            ->where('entity_id', (string) $entity->getKey());
    }

    #[Scope]
    protected function forTenant(Builder $query, string $type, string $id): Builder
    {
        return $query->where('tenant_type', $type)->where('tenant_id', $id);
    }
}
