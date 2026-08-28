<?php

namespace App\Livewire\Admin\Zones;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Zone;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Zone administration.
 *
 * Zones are circles today — a centre and a radius, which is enough for a city
 * the size of Banha and needs no geospatial extension. The polygon column
 * already exists on the table and the resolver prefers it when present, so
 * upgrading is a data migration rather than a rewrite.
 */
class ZoneManager extends Component
{
    use UsesPortalLayout;

    public const MAP_ID = 'admin-zones';

    public bool $editing = false;

    public ?string $zoneId = null;

    public string $code = '';

    public string $name = '';

    public string $nameAr = '';

    public string $latitude = '';

    public string $longitude = '';

    public int $radius = 1500;

    public string $basePrice = '15';

    public int $estimatedMinutes = 25;

    public int $sortOrder = 0;

    public bool $active = true;

    /**
     * @return Collection<int, Zone>
     */
    #[Computed]
    public function zones(): Collection
    {
        return Zone::query()
            ->withCount('deliveryCompanies')
            ->ordered()
            ->get();
    }

    /**
     * Every zone drawn as a circle, plus a marker for the one being edited.
     *
     * Seeing the circles overlap is the whole point: a coverage gap or a
     * double-covered street is obvious on a map and invisible in a table of
     * coordinates.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapConfig(): array
    {
        $markers = [];

        if ($this->latitude !== '' && $this->longitude !== '') {
            $markers[] = [
                'key' => 'editing',
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
                'variant' => 'pickup',
                'label' => '',
                'size' => 26,
            ];
        }

        return [
            'zones' => MapPayload::zones($this->zones()),
            'markers' => $markers,
        ];
    }

    /**
     * Place the centre by clicking the map.
     *
     * Typing a latitude by hand is error-prone and nobody knows what
     * 30.4610 looks like; clicking the spot is how this is actually done.
     */
    public function placeCentre(float $lat, float $lng): void
    {
        $this->latitude = (string) $lat;
        $this->longitude = (string) $lng;

        if (! $this->editing) {
            $this->edit();
            $this->latitude = (string) $lat;
            $this->longitude = (string) $lng;
        }
    }

    public function edit(?string $id = null): void
    {
        $this->resetForm();

        if ($id !== null) {
            $zone = Zone::query()->findOrFail($id);

            $this->zoneId = $zone->id;
            $this->code = $zone->code;
            $this->name = $zone->name;
            $this->nameAr = $zone->name_ar;
            $this->latitude = (string) $zone->centroid_latitude;
            $this->longitude = (string) $zone->centroid_longitude;
            $this->radius = $zone->radius_meters;
            $this->basePrice = (string) ($zone->basePrice()->minor / 100);
            $this->estimatedMinutes = $zone->estimated_minutes;
            $this->sortOrder = $zone->sort_order;
            $this->active = $zone->is_active;
        }

        $this->editing = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'code' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('zones', 'code')->ignore($this->zoneId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'nameAr' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:100', 'max:50000'],
            'basePrice' => ['required', 'numeric', 'min:0', 'max:10000'],
            'estimatedMinutes' => ['required', 'integer', 'min:1', 'max:600'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $attributes = [
            'code' => Str::upper($validated['code']),
            'name' => $validated['name'],
            'name_ar' => $validated['nameAr'],
            'city' => config('platform.city'),
            'centroid_latitude' => (float) $validated['latitude'],
            'centroid_longitude' => (float) $validated['longitude'],
            'radius_meters' => $validated['radius'],
            'base_price_minor' => (int) round(((float) $validated['basePrice']) * 100),
            'estimated_minutes' => $validated['estimatedMinutes'],
            'sort_order' => $validated['sortOrder'],
            'is_active' => $this->active,
        ];

        $zone = $this->zoneId === null
            ? Zone::create($attributes)
            : tap(Zone::findOrFail($this->zoneId))->update($attributes);

        app(AuditLogger::class)->log(
            action: $this->zoneId === null ? AuditAction::Created : AuditAction::Updated,
            entity: $zone,
            newValues: $attributes,
        );

        $this->resetForm();
        unset($this->zones, $this->mapConfig);

        $this->dispatch('map-refresh', id: self::MAP_ID, config: $this->mapConfig());

        session()->flash('status', __('app.common.save'));
    }

    public function toggle(string $id): void
    {
        $zone = Zone::query()->findOrFail($id);
        $zone->update(['is_active' => ! $zone->is_active]);

        unset($this->zones, $this->mapConfig);

        $this->dispatch('map-refresh', id: self::MAP_ID, config: $this->mapConfig());
    }

    private function resetForm(): void
    {
        $this->reset([
            'editing', 'zoneId', 'code', 'name', 'nameAr', 'latitude',
            'longitude', 'radius', 'basePrice', 'estimatedMinutes', 'sortOrder',
        ]);

        $this->active = true;
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.zones.zone-manager', title: __('app.nav.zones'));
    }
}
