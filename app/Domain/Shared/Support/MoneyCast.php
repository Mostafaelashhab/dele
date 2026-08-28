<?php

namespace App\Domain\Shared\Support;

use App\Domain\Shared\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Casts an integer minor-unit column to a Money object, so no caller can
 * accidentally treat a stored amount as a plain number.
 *
 * @implements CastsAttributes<Money, Money|int|null>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::ofMinor((int) $value, $attributes['currency'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>|int|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array|int|null
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return [$key => $value->minor];
        }

        if (is_int($value)) {
            return [$key => $value];
        }

        throw new InvalidArgumentException(
            "Attribute [{$key}] must be a Money instance or integer minor units."
        );
    }
}
