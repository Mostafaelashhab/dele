<?php

namespace App\Domain\Proof;

/**
 * The outcome of checking a handover code, kept as a type rather than a
 * boolean because "wrong code" and "no attempts left" send the rider down
 * different paths.
 */
enum ConfirmationResult: string
{
    case Verified = 'verified';
    case Incorrect = 'incorrect';
    case LockedOut = 'locked_out';
    case NotIssued = 'not_issued';

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    public function message(): string
    {
        return match ($this) {
            self::Verified => __('rider.proof.code_verified'),
            self::Incorrect => __('rider.proof.code_incorrect'),
            self::LockedOut => __('rider.proof.code_locked_out'),
            self::NotIssued => __('rider.proof.code_not_issued'),
        };
    }
}
