<?php

namespace App\Livewire\Business\Orders;

use App\Actions\Orders\CreateOrderAction;
use App\Domain\Orders\OrderData;
use App\Domain\Pricing\PriceQuote;
use App\Domain\Pricing\PricingContext;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\Contracts\DistanceCalculator;
use App\Domain\Shared\ValueObjects\GeoPoint;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Domain\Shared\ValueObjects\Money;
use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Zones\ZoneResolver;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Address;
use App\Models\Business;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The form a shop fills in to send a parcel.
 *
 * Prices live as the form is edited, because a business deciding whether to
 * offer free delivery on an order needs the number before it commits, not
 * after. The quote shown here is indicative — the binding price is whatever
 * the accepting company quotes — and the form says so.
 */
class CreateOrder extends Component
{
    use UsesPortalLayout;

    public const MAP_ID = 'create-order';

    // Pickup
    public string $pickupAddressId = '';

    public string $pickupName = '';

    public string $pickupPhone = '';

    public string $pickupAddress = '';

    public string $pickupZoneId = '';

    public ?float $pickupLat = null;

    public ?float $pickupLng = null;

    // Dropoff
    public string $dropoffName = '';

    public string $dropoffPhone = '';

    public string $dropoffAddress = '';

    public string $dropoffLandmark = '';

    public string $dropoffZoneId = '';

    public ?float $dropoffLat = null;

    public ?float $dropoffLng = null;

    // Parcel
    public string $priority = DeliveryPriority::Standard->value;

    public string $packageSize = PackageSize::Small->value;

    public string $paymentType = PaymentType::Prepaid->value;

    public string $codAmount = '';

    public string $reference = '';

    public string $notes = '';

    public function mount(): void
    {
        $business = $this->business();

        $default = $business->addresses()->where('is_default', true)->first()
            ?? $business->addresses()->first();

        if ($default !== null) {
            $this->applyPickupAddress($default);

            return;
        }

        // No saved address yet: prefill from the business profile so the very
        // first order is still one screen of typing, not two.
        $this->pickupName = $business->name;
        $this->pickupPhone = $business->phone;
        $this->pickupAddress = (string) $business->address_line;
        $this->pickupZoneId = (string) $business->default_zone_id;
        $this->pickupLat = $business->latitude;
        $this->pickupLng = $business->longitude;
    }

    private function business(): Business
    {
        return app(CurrentTenant::class)->businessOrFail();
    }

    /**
     * @return Collection<int, Address>
     */
    #[Computed]
    public function savedAddresses(): Collection
    {
        return $this->business()->addresses()->orderByDesc('is_default')->orderBy('label')->get();
    }

    /**
     * @return Collection<int, Zone>
     */
    #[Computed]
    public function zones()
    {
        return app(ZoneResolver::class)->activeZones();
    }

    public function updatedPickupAddressId(string $value): void
    {
        if ($value === '') {
            return;
        }

        $address = $this->business()->addresses()->whereKey($value)->first();

        if ($address !== null) {
            $this->applyPickupAddress($address);
        }
    }

    /**
     * Snap a chosen zone's centre onto the coordinates when the business has
     * not pinned an exact point, so pricing and matching still have geometry
     * to work with instead of falling back to a nominal distance.
     */
    public function updatedDropoffZoneId(string $value): void
    {
        $zone = $this->zones()->firstWhere('id', $value);

        if ($zone !== null && $this->dropoffLat === null) {
            $this->dropoffLat = $zone->centroid_latitude;
            $this->dropoffLng = $zone->centroid_longitude;
        }

        unset($this->mapConfig);
    }

    /**
     * The live indicative quote. Null until both ends are locatable.
     */
    #[Computed]
    public function quote(): ?PriceQuote
    {
        $pickup = GeoPoint::tryMake($this->pickupLat, $this->pickupLng);
        $dropoff = GeoPoint::tryMake($this->dropoffLat, $this->dropoffLng);

        if ($pickup === null || $dropoff === null) {
            return null;
        }

        $route = app(DistanceCalculator::class)->estimate($pickup, $dropoff);

        return app(PricingEngine::class)->quote(new PricingContext(
            distanceMeters: $route->distanceMeters,
            estimatedMinutes: $route->durationMinutes
                + (int) config('platform.routing.pickup_handling_minutes')
                + (int) config('platform.routing.dropoff_handling_minutes'),
            priority: DeliveryPriority::from($this->priority),
            packageSize: PackageSize::from($this->packageSize),
            paymentType: PaymentType::from($this->paymentType),
            codAmount: $this->codMoney(),
            pickupZone: $this->zones()->firstWhere('id', $this->pickupZoneId),
            dropoffZone: $this->zones()->firstWhere('id', $this->dropoffZoneId),
            business: $this->business(),
        ));
    }

    /**
     * Pickup and dropoff as currently entered.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function mapConfig(): array
    {
        $markers = [];

        if ($this->pickupLat !== null && $this->pickupLng !== null) {
            $markers[] = [
                'key' => 'pickup',
                'lat' => $this->pickupLat,
                'lng' => $this->pickupLng,
                'variant' => 'pickup',
                'label' => '↑',
                'size' => 28,
                'title' => __('delivery.labels.pickup'),
            ];
        }

        if ($this->dropoffLat !== null && $this->dropoffLng !== null) {
            $markers[] = [
                'key' => 'dropoff',
                'lat' => $this->dropoffLat,
                'lng' => $this->dropoffLng,
                'variant' => 'dropoff',
                'label' => '↓',
                'size' => 28,
                'title' => __('delivery.labels.dropoff'),
            ];
        }

        return [
            'markers' => $markers,
            'route' => count($markers) === 2
                ? array_map(fn (array $m) => ['lat' => $m['lat'], 'lng' => $m['lng']], $markers)
                : [],
            // Only the destination matters while placing a pin; drawing
            // every zone turns the picker into a wall of overlapping rings.
            'zones' => [],
        ];
    }

    /**
     * Drop the customer's pin.
     *
     * Most Banha addresses are landmarks rather than street numbers, so
     * pointing at the spot is both faster and more accurate than any address
     * field — and it is what makes the quote and the dispatch honest.
     */
    public function placeDropoff(float $lat, float $lng): void
    {
        $this->dropoffLat = $lat;
        $this->dropoffLng = $lng;

        // Snap the zone to wherever the pin landed, so pricing and matching
        // agree with the map the business is looking at.
        $zone = app(ZoneResolver::class)
            ->resolve(new GeoPoint($lat, $lng));

        if ($zone !== null) {
            $this->dropoffZoneId = $zone->id;
        }

        unset($this->quote, $this->mapConfig);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $order = app(CreateOrderAction::class)->handle(
            business: $this->business(),
            data: new OrderData(
                pickup: LocationSnapshot::fromArray([
                    'contact_name' => $validated['pickupName'],
                    'contact_phone' => $validated['pickupPhone'],
                    'address_line' => $validated['pickupAddress'],
                    'area' => $this->zones()->firstWhere('id', $this->pickupZoneId)?->displayName(),
                    'city' => config('platform.city'),
                    'latitude' => $this->pickupLat,
                    'longitude' => $this->pickupLng,
                    'zone_id' => $this->pickupZoneId ?: null,
                ]),
                dropoff: LocationSnapshot::fromArray([
                    'contact_name' => $validated['dropoffName'],
                    'contact_phone' => $validated['dropoffPhone'],
                    'address_line' => $validated['dropoffAddress'],
                    'landmark' => $this->dropoffLandmark ?: null,
                    'area' => $this->zones()->firstWhere('id', $this->dropoffZoneId)?->displayName(),
                    'city' => config('platform.city'),
                    'latitude' => $this->dropoffLat,
                    'longitude' => $this->dropoffLng,
                    'zone_id' => $this->dropoffZoneId ?: null,
                ]),
                priority: DeliveryPriority::from($this->priority),
                packageSize: PackageSize::from($this->packageSize),
                paymentType: PaymentType::from($this->paymentType),
                codAmount: $this->codMoney(),
                reference: $this->reference ?: null,
                notes: $this->notes ?: null,
            ),
            creator: auth()->user(),
        );

        session()->flash('status', __('delivery.event.OrderCreated').' — '.$order->number);

        $this->redirectRoute('business.orders.show', $order->number, navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'pickupName' => ['required', 'string', 'max:120'],
            'pickupPhone' => ['required', 'string', 'regex:/^01[0-2,5]\d{8}$/'],
            'pickupAddress' => ['required', 'string', 'max:255'],
            'pickupZoneId' => ['nullable', 'string', 'exists:zones,id'],

            'dropoffName' => ['required', 'string', 'max:120'],
            'dropoffPhone' => ['required', 'string', 'regex:/^01[0-2,5]\d{8}$/'],
            'dropoffAddress' => ['required', 'string', 'max:255'],
            'dropoffLandmark' => ['nullable', 'string', 'max:160'],
            'dropoffZoneId' => ['required', 'string', 'exists:zones,id'],

            'priority' => ['required', 'string', Rule::enum(DeliveryPriority::class)],
            'packageSize' => ['required', 'string', Rule::enum(PackageSize::class)],
            'paymentType' => ['required', 'string', Rule::enum(PaymentType::class)],
            'codAmount' => [
                Rule::requiredIf($this->paymentType === PaymentType::CashOnDelivery->value),
                'nullable',
                'numeric',
                'min:0',
                'max:1000000',
            ],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'pickupName' => __('address.contact_name'),
            'pickupPhone' => __('address.contact_phone'),
            'pickupAddress' => __('address.address_line'),
            'dropoffName' => __('address.contact_name'),
            'dropoffPhone' => __('address.contact_phone'),
            'dropoffAddress' => __('address.address_line'),
            'dropoffZoneId' => __('address.zone'),
            'codAmount' => __('order.payment.cod'),
        ];
    }

    private function codMoney(): Money
    {
        return $this->paymentType === PaymentType::CashOnDelivery->value && $this->codAmount !== ''
            ? Money::ofMajor($this->codAmount)
            : Money::zero();
    }

    private function applyPickupAddress(Address $address): void
    {
        $this->pickupAddressId = $address->id;
        $this->pickupName = $address->contact_name ?? $this->business()->name;
        $this->pickupPhone = $address->contact_phone ?? $this->business()->phone;
        $this->pickupAddress = $address->composedLine();
        $this->pickupZoneId = (string) $address->zone_id;
        $this->pickupLat = $address->latitude;
        $this->pickupLng = $address->longitude;
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.orders.create-order', [
            'priorities' => DeliveryPriority::cases(),
            'sizes' => PackageSize::cases(),
            'payments' => PaymentType::cases(),
        ], __('app.dashboard.quick_create'));
    }
}
