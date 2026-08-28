<?php

namespace App\Domain\Proof;

use App\Models\Delivery;
use Illuminate\Support\Facades\DB;
use Random\RandomException;

/**
 * The handover code: generated with the delivery, shown to the recipient on
 * their tracking page, spoken to the rider, typed in to close the delivery.
 *
 * The code is not a secret in the cryptographic sense — it is four digits and
 * it is meant to be read out loud. Its job is to prove the rider was standing
 * in front of the person holding the tracking link, which a photograph of a
 * doorstep cannot. What stops it being guessed is the attempt limit: a rider
 * gets a few tries against one delivery and then the code stops being an
 * option for that delivery at all, leaving the photo as the way to close it.
 */
class DeliveryConfirmationCode
{
    /**
     * Generate a code for a delivery that does not have one.
     *
     * @throws RandomException
     */
    public static function generate(): string
    {
        $digits = max(4, min(8, (int) config('platform.proof.code_digits', 4)));

        return str_pad(
            (string) random_int(0, (10 ** $digits) - 1),
            $digits,
            '0',
            STR_PAD_LEFT
        );
    }

    public static function maxAttempts(): int
    {
        return max(1, (int) config('platform.proof.code_max_attempts', 5));
    }

    /**
     * Has this delivery burned through its attempts?
     */
    public function isLockedOut(Delivery $delivery): bool
    {
        return $delivery->confirmation_attempts >= self::maxAttempts();
    }

    /**
     * Check a code the rider typed, counting the attempt either way.
     *
     * The row is locked for the read-modify-write so two tabs cannot spend the
     * same attempt twice, and the comparison is timing-safe out of habit
     * rather than necessity.
     */
    public function verify(Delivery $delivery, string $submitted): ConfirmationResult
    {
        return DB::transaction(function () use ($delivery, $submitted): ConfirmationResult {
            /** @var Delivery $fresh */
            $fresh = Delivery::query()->lockForUpdate()->findOrFail($delivery->id);

            if ($fresh->confirmation_code === null) {
                return ConfirmationResult::NotIssued;
            }

            if ($fresh->confirmation_code_verified_at !== null) {
                return ConfirmationResult::Verified;
            }

            if ($this->isLockedOut($fresh)) {
                return ConfirmationResult::LockedOut;
            }

            $normalised = preg_replace('/\D/', '', $submitted) ?? '';

            if (! hash_equals($fresh->confirmation_code, $normalised)) {
                $fresh->forceFill([
                    'confirmation_attempts' => $fresh->confirmation_attempts + 1,
                ])->save();

                return $this->isLockedOut($fresh)
                    ? ConfirmationResult::LockedOut
                    : ConfirmationResult::Incorrect;
            }

            $fresh->forceFill([
                'confirmation_code_verified_at' => now(),
                'confirmation_attempts' => $fresh->confirmation_attempts + 1,
            ])->save();

            $delivery->setRawAttributes($fresh->getAttributes(), true);

            return ConfirmationResult::Verified;
        });
    }
}
