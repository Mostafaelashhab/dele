<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable money, stored and computed exclusively in integer minor units
 * (piastres for EGP). No float ever enters a financial calculation; the only
 * float in this class is the display helper on the way out.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    private function __construct(
        public int $minor,
        public string $currency,
    ) {}

    public static function ofMinor(int $minor, ?string $currency = null): self
    {
        return new self($minor, $currency ?? self::defaultCurrency());
    }

    public static function zero(?string $currency = null): self
    {
        return new self(0, $currency ?? self::defaultCurrency());
    }

    /**
     * Build from a major unit amount (e.g. "25.50" EGP). Accepts a string to
     * avoid binary float representation errors at the boundary.
     */
    public static function ofMajor(int|float|string $major, ?string $currency = null): self
    {
        $scale = self::scale();
        $normalised = number_format((float) $major, self::decimals(), '.', '');

        return new self((int) round(((float) $normalised) * $scale), $currency ?? self::defaultCurrency());
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor + $other->minor, $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minor - $other->minor, $this->currency);
    }

    public function times(int $factor): self
    {
        return new self($this->minor * $factor, $this->currency);
    }

    /**
     * Multiply by a basis-point rate (10000 bps = 100%), rounding half up.
     */
    public function percentage(int $basisPoints): self
    {
        return new self(intdiv($this->minor * $basisPoints + 5000, 10000), $this->currency);
    }

    /**
     * Multiply by a rational factor without leaving integer arithmetic.
     */
    public function multipliedBy(int $numerator, int $denominator): self
    {
        if ($denominator === 0) {
            throw new InvalidArgumentException('Cannot divide money by zero.');
        }

        return new self(intdiv($this->minor * $numerator + intdiv($denominator, 2), $denominator), $this->currency);
    }

    /**
     * Round up to the nearest increment, e.g. the nearest 50 piastres.
     */
    public function roundUpTo(int $incrementMinor): self
    {
        if ($incrementMinor <= 1) {
            return $this;
        }

        $remainder = $this->minor % $incrementMinor;

        if ($remainder === 0) {
            return $this;
        }

        return new self($this->minor + ($incrementMinor - $remainder), $this->currency);
    }

    /**
     * Split into $parts, distributing the remainder one minor unit at a time
     * so the parts always sum back to the original amount exactly.
     *
     * @return array<int, self>
     */
    public function allocateEvenly(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Cannot allocate money into fewer than one part.');
        }

        $base = intdiv($this->minor, $parts);
        $remainder = $this->minor - ($base * $parts);
        $result = [];

        for ($i = 0; $i < $parts; $i++) {
            $result[] = new self($base + ($i < $remainder ? 1 : 0), $this->currency);
        }

        return $result;
    }

    public function negated(): self
    {
        return new self(-$this->minor, $this->currency);
    }

    public function absolute(): self
    {
        return new self(abs($this->minor), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function isPositive(): bool
    {
        return $this->minor > 0;
    }

    public function isNegative(): bool
    {
        return $this->minor < 0;
    }

    public function equals(self $other): bool
    {
        return $this->minor === $other->minor && $this->currency === $other->currency;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minor > $other->minor;
    }

    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->minor < $other->minor;
    }

    public function max(self $other): self
    {
        return $this->greaterThan($other) ? $this : $other;
    }

    public function min(self $other): self
    {
        return $this->lessThan($other) ? $this : $other;
    }

    /**
     * @param  array<int, self>  $items
     */
    public static function sum(array $items, ?string $currency = null): self
    {
        $total = self::zero($currency ?? ($items[0]->currency ?? null));

        foreach ($items as $item) {
            $total = $total->plus($item);
        }

        return $total;
    }

    /**
     * Display only. Never feed this back into a calculation.
     */
    public function toMajor(): float
    {
        return $this->minor / self::scale();
    }

    public function format(bool $withSymbol = true): string
    {
        $formatted = number_format($this->minor / self::scale(), self::decimals(), '.', ',');

        return $withSymbol
            ? $formatted.' '.config('platform.currency.symbol')
            : $formatted;
    }

    /**
     * @return array{minor: int, currency: string, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'minor' => $this->minor,
            'currency' => $this->currency,
            'formatted' => $this->format(false),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}."
            );
        }
    }

    private static function defaultCurrency(): string
    {
        return (string) config('platform.currency.code', 'EGP');
    }

    private static function scale(): int
    {
        return (int) config('platform.currency.minor_unit_scale', 100);
    }

    private static function decimals(): int
    {
        return (int) log10(self::scale());
    }
}
