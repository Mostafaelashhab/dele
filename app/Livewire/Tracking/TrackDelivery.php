<?php

namespace App\Livewire\Tracking;

use App\Domain\Tracking\TrackingPresenter;
use App\Models\Delivery;
use App\Support\MapPayload;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The customer-facing tracking page.
 *
 * Unauthenticated: the token in the URL is the only credential, so the
 * component holds the token and never the delivery's internal id, and it
 * renders only what the TrackingPresenter has already deemed public.
 */
class TrackDelivery extends Component
{
    public string $token = '';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $tracking = null;

    public bool $notFound = false;

    /**
     * The map payload, kept separate from the tracking array because the map
     * component receives it as an event on refresh rather than through the
     * morph.
     *
     * @var array<string, mixed>
     */
    public array $map = ['markers' => [], 'zones' => [], 'route' => []];

    public const MAP_ID = 'customer-tracking';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->load();
    }

    /**
     * Called by the poll. Once the delivery is finished the page stops
     * polling, so a delivered order left open on a phone does not keep
     * hitting the server for the rest of the day.
     */
    public function load(): void
    {
        $delivery = Delivery::query()
            ->where('tracking_token', $this->token)
            ->with(['order', 'business', 'deliveryCompany', 'rider'])
            ->first();

        if ($delivery === null) {
            $this->notFound = true;
            $this->tracking = null;

            return;
        }

        $this->tracking = app(TrackingPresenter::class)->present($delivery);

        // Built from the same delivery, but through the payload builder, which
        // is the single place that decides a customer may see the rider's
        // position only while the parcel is actually in their hands.
        $this->map = MapPayload::forCustomer($delivery);

        $this->dispatch('map-refresh', id: self::MAP_ID, config: $this->map);
    }

    /**
     * Whether there is anything worth drawing a map for.
     */
    public function hasMap(): bool
    {
        return ! $this->notFound && ($this->map['markers'] ?? []) !== [];
    }

    public function shouldPoll(): bool
    {
        return ! $this->notFound
            && ! ($this->tracking['is_complete'] ?? false)
            && ! ($this->tracking['is_failed'] ?? false);
    }

    public function render(): View
    {
        return view('livewire.tracking.track-delivery')
            ->layout('components.layouts.public', [
                'title' => __('app.tracking.title'),
            ]);
    }
}
