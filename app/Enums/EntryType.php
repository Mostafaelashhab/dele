<?php

namespace App\Enums;

/**
 * Double entry direction. Every financial event writes balanced pairs.
 */
enum EntryType: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function sign(): int
    {
        return $this === self::Credit ? 1 : -1;
    }
}
