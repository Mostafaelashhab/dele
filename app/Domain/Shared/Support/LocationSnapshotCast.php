<?php

namespace App\Domain\Shared\Support;

use App\Domain\Shared\ValueObjects\LocationSnapshot;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<LocationSnapshot, LocationSnapshot|array<string, mixed>|null>
 */
final class LocationSnapshotCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?LocationSnapshot
    {
        if ($value === null) {
            return null;
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        return is_array($decoded) ? LocationSnapshot::fromArray($decoded) : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof LocationSnapshot) {
            return json_encode($value->jsonSerialize(), JSON_UNESCAPED_UNICODE);
        }

        if (is_array($value)) {
            return json_encode(LocationSnapshot::fromArray($value)->jsonSerialize(), JSON_UNESCAPED_UNICODE);
        }

        throw new InvalidArgumentException("Attribute [{$key}] must be a LocationSnapshot or array.");
    }
}
