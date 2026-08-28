<?php

namespace Database\Factories;

use App\Enums\EntryType;
use App\Enums\LedgerAccountType;
use App\Enums\TransactionCategory;
use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FinancialTransaction>
 */
class FinancialTransactionFactory extends Factory
{
    protected $model = FinancialTransaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => (string) Str::ulid(),
            'account_type' => LedgerAccountType::Platform,
            'account_id' => null,
            'entry_type' => EntryType::Credit,
            'category' => TransactionCategory::PlatformFee,
            'amount_minor' => fake()->numberBetween(100, 5000),
            'currency' => 'EGP',
            'occurred_at' => now(),
        ];
    }
}
