<?php

namespace Tests\Unit;

use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Money is the type every financial figure in the platform passes through, so
 * these tests are about one property above all: arithmetic must never lose or
 * invent a piastre.
 *
 * The application is booted because Money reads its currency and scale from
 * platform configuration; nothing here touches the database.
 */
class MoneyTest extends TestCase
{
    #[Test]
    public function it_adds_and_subtracts_without_drift(): void
    {
        $a = Money::ofMinor(1999);
        $b = Money::ofMinor(1);

        $this->assertSame(2000, $a->plus($b)->minor);
        $this->assertSame(1998, $a->minus($b)->minor);
    }

    #[Test]
    public function it_parses_major_units_that_would_break_in_floating_point(): void
    {
        // 0.1 + 0.2 famously is not 0.3 in binary floating point. Going
        // through minor units means the total is exact.
        $total = Money::ofMajor('0.10')->plus(Money::ofMajor('0.20'));

        $this->assertSame(30, $total->minor);
        $this->assertSame(3000, Money::ofMajor('30.00')->minor);
        $this->assertSame(2999, Money::ofMajor('29.99')->minor);
    }

    #[Test]
    public function it_applies_basis_point_percentages_with_half_up_rounding(): void
    {
        // 12% of 25.00 EGP is exactly 3.00.
        $this->assertSame(300, Money::ofMinor(2500)->percentage(1200)->minor);

        // 12% of 17.50 is 2.10.
        $this->assertSame(210, Money::ofMinor(1750)->percentage(1200)->minor);

        // A half-piastre rounds up rather than truncating away from the payee.
        $this->assertSame(1, Money::ofMinor(1)->percentage(5000)->minor);
    }

    #[Test]
    public function allocating_always_sums_back_to_the_original(): void
    {
        // 100 piastres across 3 parts cannot divide evenly; the remainder has
        // to land somewhere rather than vanish.
        $parts = Money::ofMinor(100)->allocateEvenly(3);

        $this->assertCount(3, $parts);
        $this->assertSame([34, 33, 33], array_map(fn (Money $part) => $part->minor, $parts));
        $this->assertSame(100, Money::sum($parts)->minor);
    }

    #[Test]
    public function it_rounds_up_to_a_cash_friendly_increment(): void
    {
        // Riders carry coins, not fractions: 27.30 becomes 27.50.
        $this->assertSame(2750, Money::ofMinor(2730)->roundUpTo(50)->minor);

        // An amount already on the increment is left alone.
        $this->assertSame(2750, Money::ofMinor(2750)->roundUpTo(50)->minor);
    }

    #[Test]
    public function it_refuses_to_mix_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::ofMinor(100, 'EGP')->plus(Money::ofMinor(100, 'USD'));
    }

    #[Test]
    public function it_compares_and_clamps(): void
    {
        $low = Money::ofMinor(500);
        $high = Money::ofMinor(1500);

        $this->assertTrue($high->greaterThan($low));
        $this->assertTrue($low->lessThan($high));
        $this->assertSame(1500, $low->max($high)->minor);
        $this->assertSame(500, $high->min($low)->minor);
        $this->assertSame(-500, $low->negated()->minor);
        $this->assertSame(500, $low->negated()->absolute()->minor);
    }
}
