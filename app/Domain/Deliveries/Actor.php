<?php

namespace App\Domain\Deliveries;

use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use App\Models\User;

/**
 * Who caused a change. Every event and audit row carries one, so "the system
 * did it" is always distinguishable from "a dispatcher did it".
 */
final readonly class Actor
{
    public function __construct(
        public string $type,
        public ?string $id = null,
        public ?string $label = null,
    ) {}

    public static function system(string $label = 'system'): self
    {
        return new self('system', null, $label);
    }

    public static function user(User $user): self
    {
        return new self('user', (string) $user->id, $user->name);
    }

    public static function rider(Rider $rider): self
    {
        return new self('rider', $rider->id, $rider->name);
    }

    public static function company(DeliveryCompany $company): self
    {
        return new self('delivery_company', $company->id, $company->name);
    }

    public static function business(Business $business): self
    {
        return new self('business', $business->id, $business->name);
    }

    /**
     * The person waiting for the parcel.
     *
     * They hold no account, so there is no id to carry — only the fact that a
     * human on the receiving end caused this, which is precisely what an
     * operator reading the timeline needs to tell apart from an automatic
     * status change.
     */
    public static function customer(): self
    {
        return new self('customer', null, __('tracking.issue.actor'));
    }

    public static function api(string $clientId, string $label): self
    {
        return new self('api_client', $clientId, $label);
    }

    /**
     * Best available actor for the current request, falling back to system
     * for queued work where no user is authenticated.
     */
    public static function current(): self
    {
        $user = auth()->user();

        return $user instanceof User ? self::user($user) : self::system();
    }

    /**
     * @return array<string, ?string>
     */
    public function toArray(): array
    {
        return [
            'actor_type' => $this->type,
            'actor_id' => $this->id,
            'actor_label' => $this->label,
        ];
    }
}
