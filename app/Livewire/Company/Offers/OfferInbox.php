<?php

namespace App\Livewire\Company\Offers;

use App\Actions\Deliveries\AcceptDeliveryOfferAction;
use App\Actions\Deliveries\RejectDeliveryOfferAction;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\OfferStatus;
use App\Models\DeliveryOffer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * The delivery company's inbox of live offers.
 *
 * Polls every few seconds because an offer that expires unseen costs the
 * company work and the business time. The countdown is rendered client-side
 * from the server's expiry timestamp, so it stays honest without a request
 * per second.
 */
class OfferInbox extends Component
{
    public string $rejectionReason = '';

    public ?string $rejecting = null;

    /**
     * @return Collection<int, DeliveryOffer>
     */
    #[Computed]
    public function offers(): Collection
    {
        return DeliveryOffer::query()
            ->where('delivery_company_id', app(CurrentTenant::class)->companyOrFail()->id)
            ->open()
            ->with(['delivery.order.pickupZone', 'delivery.order.dropoffZone', 'delivery.business'])
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * @return Collection<int, DeliveryOffer>
     */
    #[Computed]
    public function recentlyClosed(): Collection
    {
        return DeliveryOffer::query()
            ->where('delivery_company_id', app(CurrentTenant::class)->companyOrFail()->id)
            ->whereNot('status', OfferStatus::Pending)
            ->where('responded_at', '>=', now()->subHours(12))
            ->with(['delivery.order'])
            ->orderByDesc('responded_at')
            ->limit(10)
            ->get();
    }

    public function accept(string $offerId): void
    {
        $offer = $this->findOffer($offerId);

        try {
            $delivery = app(AcceptDeliveryOfferAction::class)->handle($offer, auth()->user());
        } catch (Throwable $exception) {
            // Losing a race for an offer is normal marketplace behaviour, not
            // an error worth an exception page.
            $this->dispatch('toast', message: $exception->getMessage(), tone: 'error');
            unset($this->offers);

            return;
        }

        session()->flash('status', __('delivery.event.DeliveryAccepted'));

        $this->redirectRoute('company.deliveries.show', $delivery->public_id, navigate: true);
    }

    public function startReject(string $offerId): void
    {
        $this->rejecting = $offerId;
        $this->rejectionReason = '';
    }

    public function reject(): void
    {
        if ($this->rejecting === null) {
            return;
        }

        $offer = $this->findOffer($this->rejecting);

        app(RejectDeliveryOfferAction::class)->handle(
            offer: $offer,
            respondedBy: auth()->user(),
            reason: $this->rejectionReason !== '' ? $this->rejectionReason : 'declined',
        );

        $this->rejecting = null;
        $this->rejectionReason = '';

        unset($this->offers, $this->recentlyClosed);

        $this->dispatch('toast', message: __('delivery.offer.rejected'), tone: 'neutral');
    }

    private function findOffer(string $offerId): DeliveryOffer
    {
        return DeliveryOffer::query()
            ->whereKey($offerId)
            ->where('delivery_company_id', app(CurrentTenant::class)->companyOrFail()->id)
            ->with(['delivery.order', 'deliveryCompany'])
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.company.offers.offer-inbox')
            ->layout('components.layouts.app', [
                'portal' => 'company',
                'title' => __('app.nav.offers'),
                'context' => app(CurrentTenant::class)->companyOrFail()->displayName(),
            ]);
    }
}
