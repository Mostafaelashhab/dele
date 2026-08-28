<?php

namespace App\Livewire\Company;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AuditAction;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Which zones a company will collect from and deliver to.
 *
 * This is the first filter the matching engine applies, so it is also the
 * lever a company pulls when it wants more or less work.
 */
class ServiceAreas extends Component
{
    use UsesPortalLayout;

    /**
     * @var array<string, array{pickup: bool, dropoff: bool, surcharge: string}>
     */
    public array $areas = [];

    public function mount(): void
    {
        $company = app(CurrentTenant::class)->companyOrFail();
        $existing = $company->serviceAreas()->get()->keyBy('id');

        foreach (app(ZoneResolver::class)->activeZones() as $zone) {
            $pivot = $existing->get($zone->id)?->pivot;

            $this->areas[$zone->id] = [
                'pickup' => (bool) ($pivot?->accepts_pickup ?? false),
                'dropoff' => (bool) ($pivot?->accepts_dropoff ?? false),
                'surcharge' => (string) (($pivot?->surcharge_minor ?? 0) / 100),
            ];
        }
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
        $company = app(CurrentTenant::class)->companyOrFail();

        $sync = [];

        foreach ($this->areas as $zoneId => $settings) {
            // A zone with neither direction enabled is simply not served, so
            // it is dropped rather than stored as an all-false row.
            if (! $settings['pickup'] && ! $settings['dropoff']) {
                continue;
            }

            $sync[$zoneId] = [
                'accepts_pickup' => $settings['pickup'],
                'accepts_dropoff' => $settings['dropoff'],
                'surcharge_minor' => (int) round(((float) $settings['surcharge']) * 100),
            ];
        }

        $company->serviceAreas()->sync($sync);

        app(AuditLogger::class)->log(
            action: AuditAction::Updated,
            entity: $company,
            description: 'Service areas updated.',
            newValues: ['zones' => array_keys($sync)],
            tenantType: 'delivery_company',
            tenantId: $company->id,
        );

        session()->flash('status', __('app.common.save'));
    }

    public function render(): View
    {
        return $this->portalView('livewire.company.service-areas', title: __('app.nav.service_areas'));
    }
}
