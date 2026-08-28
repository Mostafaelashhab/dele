<?php

namespace App\Livewire\Business;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Zones\ZoneResolver;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Address;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Saved pickup points. A business with several branches picks one per order
 * instead of retyping an address it will use a hundred times.
 */
class AddressBook extends Component
{
    use UsesPortalLayout;

    public bool $editing = false;

    public ?string $addressId = null;

    public string $label = '';

    public string $contactName = '';

    public string $contactPhone = '';

    public string $addressLine = '';

    public string $landmark = '';

    public string $zoneId = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public bool $isDefault = false;

    /**
     * @return Collection<int, Address>
     */
    #[Computed]
    public function addresses(): Collection
    {
        return app(CurrentTenant::class)->businessOrFail()
            ->addresses()
            ->with('zone')
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get();
    }

    /**
     * @return Collection<int, Zone>
     */
    #[Computed]
    public function zones(): Collection
    {
        return app(ZoneResolver::class)->activeZones();
    }

    public function edit(?string $id = null): void
    {
        $this->reset(['addressId', 'label', 'contactName', 'contactPhone', 'addressLine', 'landmark', 'zoneId', 'latitude', 'longitude', 'isDefault']);

        if ($id !== null) {
            $address = $this->findAddress($id);

            $this->addressId = $address->id;
            $this->label = (string) $address->label;
            $this->contactName = (string) $address->contact_name;
            $this->contactPhone = (string) $address->contact_phone;
            $this->addressLine = $address->address_line;
            $this->landmark = (string) $address->landmark;
            $this->zoneId = (string) $address->zone_id;
            $this->latitude = $address->latitude;
            $this->longitude = $address->longitude;
            $this->isDefault = $address->is_default;
        }

        $this->editing = true;
    }

    public function save(): void
    {
        $business = app(CurrentTenant::class)->businessOrFail();

        $validated = $this->validate([
            'label' => ['required', 'string', 'max:80'],
            'contactName' => ['required', 'string', 'max:120'],
            'contactPhone' => ['required', 'string', 'regex:/^01[0-2,5]\d{8}$/'],
            'addressLine' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:160'],
            'zoneId' => ['required', 'string', 'exists:zones,id'],
        ]);

        $zone = $this->zones()->firstWhere('id', $validated['zoneId']);

        DB::transaction(function () use ($business, $validated, $zone): void {
            $attributes = [
                'label' => $validated['label'],
                'contact_name' => $validated['contactName'],
                'contact_phone' => $validated['contactPhone'],
                'address_line' => $validated['addressLine'],
                'landmark' => $validated['landmark'] ?: null,
                'zone_id' => $validated['zoneId'],
                'area' => $zone?->displayName(),
                'city' => config('platform.city'),
                // Falling back to the zone centre keeps every saved address
                // locatable, which is what dispatch and pricing need.
                'latitude' => $this->latitude ?? $zone?->centroid_latitude,
                'longitude' => $this->longitude ?? $zone?->centroid_longitude,
                'is_default' => $this->isDefault,
            ];

            if ($this->isDefault) {
                $business->addresses()->update(['is_default' => false]);
            }

            if ($this->addressId === null) {
                $business->addresses()->create($attributes);

                return;
            }

            $this->findAddress($this->addressId)->update($attributes);
        });

        $this->editing = false;
        unset($this->addresses);

        session()->flash('status', __('app.common.save'));
    }

    public function delete(string $id): void
    {
        $this->findAddress($id)->delete();
        unset($this->addresses);
    }

    private function findAddress(string $id): Address
    {
        return app(CurrentTenant::class)->businessOrFail()
            ->addresses()
            ->whereKey($id)
            ->firstOrFail();
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.address-book', title: __('app.nav.addresses'));
    }
}
