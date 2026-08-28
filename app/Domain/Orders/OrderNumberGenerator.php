<?php

namespace App\Domain\Orders;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Human-quotable order numbers. Riders and shop owners read these aloud over
 * the phone, so they avoid characters that sound or look alike.
 */
class OrderNumberGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const MAX_ATTEMPTS = 8;

    public function generate(): string
    {
        $prefix = 'BN'.now()->format('ymd');

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $number = $prefix.'-'.$this->randomSuffix();

            if (! Order::withTrashed()->where('number', $number)->exists()) {
                return $number;
            }
        }

        // Collisions this deep mean the random space is saturated for today;
        // fall back to a ULID tail, which is unique but less readable.
        return $prefix.'-'.Str::upper(Str::substr((string) Str::ulid(), -8));
    }

    private function randomSuffix(): string
    {
        $suffix = '';
        $max = mb_strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < 5; $i++) {
            $suffix .= self::ALPHABET[random_int(0, $max)];
        }

        return $suffix;
    }
}
