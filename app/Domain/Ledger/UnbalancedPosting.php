<?php

namespace App\Domain\Ledger;

use RuntimeException;

class UnbalancedPosting extends RuntimeException
{
    public static function by(int $differenceMinor): self
    {
        return new self(
            "Refusing to post an unbalanced ledger group: debits and credits differ by {$differenceMinor} minor units."
        );
    }
}
