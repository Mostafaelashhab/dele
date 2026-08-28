<?php

namespace App\Livewire\Business;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AuditAction;
use App\Enums\DeliveryPriority;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Business;
use App\Models\BusinessCompanyPreference;
use App\Models\DeliveryCompany;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Business profile plus the two levers that shape how the network behaves for
 * it: which strategy ranks companies, and which companies it prefers or has
 * blocked outright.
 */
class Settings extends Component
{
    use UsesPortalLayout, WithFileUploads;

    public string $name = '';

    public string $contactPerson = '';

    public string $phone = '';

    public string $email = '';

    public string $addressLine = '';

    public string $defaultZoneId = '';

    public string $defaultPriority = 'standard';

    public string $matchingStrategy = '';

    public $logo = null;

    /**
     * @var array<string, string> company id => preference|'' (none)
     */
    public array $preferences = [];

    public function mount(): void
    {
        $business = app(CurrentTenant::class)->businessOrFail();

        $this->name = $business->name;
        $this->contactPerson = (string) $business->contact_person;
        $this->phone = $business->phone;
        $this->email = (string) $business->email;
        $this->addressLine = (string) $business->address_line;
        $this->defaultZoneId = (string) $business->default_zone_id;
        $this->defaultPriority = $business->default_priority->value;
        $this->matchingStrategy = (string) $business->matching_strategy;

        $existing = $business->companyPreferences()->get()->keyBy('delivery_company_id');

        foreach ($this->companies() as $company) {
            $this->preferences[$company->id] = $existing->get($company->id)?->preference ?? '';
        }
    }

    /**
     * @return Collection<int, DeliveryCompany>
     */
    #[Computed]
    public function companies(): Collection
    {
        return DeliveryCompany::query()->active()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Zone>
     */
    #[Computed]
    public function zones(): Collection
    {
        return app(ZoneResolver::class)->activeZones();
    }

    public function save(): void
    {
        $business = app(CurrentTenant::class)->businessOrFail();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'contactPerson' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^01[0-2,5]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'addressLine' => ['nullable', 'string', 'max:255'],
            'defaultZoneId' => ['nullable', 'string', 'exists:zones,id'],
            'defaultPriority' => ['required', Rule::enum(DeliveryPriority::class)],
            'matchingStrategy' => ['nullable', Rule::in(['weighted', 'cheapest', 'fastest'])],
            'logo' => ['nullable', 'image', 'max:'.(int) config('platform.media.max_upload_kb', 4096)],
        ]);

        $zone = $this->zones()->firstWhere('id', $validated['defaultZoneId']);

        $business->update([
            'name' => $validated['name'],
            'contact_person' => $validated['contactPerson'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?: null,
            'address_line' => $validated['addressLine'] ?: null,
            'default_zone_id' => $validated['defaultZoneId'] ?: null,
            'default_priority' => $validated['defaultPriority'],
            'matching_strategy' => $validated['matchingStrategy'] ?: null,
            'latitude' => $business->latitude ?? $zone?->centroid_latitude,
            'longitude' => $business->longitude ?? $zone?->centroid_longitude,
        ]);

        if ($this->logo instanceof TemporaryUploadedFile) {
            $business->storeMedia('logo_path', $this->logo, 'logos/business');
            $this->logo = null;
        }

        $this->syncPreferences($business);

        app(AuditLogger::class)->logChanges(
            action: AuditAction::Updated,
            entity: $business,
            description: 'Business settings updated.',
        );

        session()->flash('status', __('app.common.save'));
    }

    private function syncPreferences(Business $business): void
    {
        foreach ($this->preferences as $companyId => $preference) {
            if ($preference === '') {
                $business->companyPreferences()->where('delivery_company_id', $companyId)->delete();

                continue;
            }

            BusinessCompanyPreference::updateOrCreate(
                ['business_id' => $business->id, 'delivery_company_id' => $companyId],
                ['preference' => $preference],
            );
        }
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.settings', [
            'priorities' => DeliveryPriority::cases(),
        ], __('app.nav.settings'));
    }
}
