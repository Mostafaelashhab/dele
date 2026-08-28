<?php

namespace App\Domain\Ledger;

use App\Domain\Shared\ValueObjects\Money;
use App\Enums\EntryType;
use App\Enums\LedgerAccountType;
use App\Enums\TransactionCategory;

/**
 * One side of one posting, before it reaches the database.
 */
final readonly class LedgerEntry
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public LedgerAccountType $accountType,
        public ?string $accountId,
        public EntryType $entryType,
        public TransactionCategory $category,
        public Money $amount,
        public ?string $description = null,
        public array $metadata = [],
    ) {}

    public static function debit(
        LedgerAccountType $accountType,
        ?string $accountId,
        TransactionCategory $category,
        Money $amount,
        ?string $description = null,
        array $metadata = [],
    ): self {
        return new self($accountType, $accountId, EntryType::Debit, $category, $amount, $description, $metadata);
    }

    public static function credit(
        LedgerAccountType $accountType,
        ?string $accountId,
        TransactionCategory $category,
        Money $amount,
        ?string $description = null,
        array $metadata = [],
    ): self {
        return new self($accountType, $accountId, EntryType::Credit, $category, $amount, $description, $metadata);
    }

    public function signedMinor(): int
    {
        return $this->amount->minor * $this->entryType->sign();
    }
}
