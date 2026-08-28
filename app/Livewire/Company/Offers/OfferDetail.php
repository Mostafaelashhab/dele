<?php

namespace App\Livewire\Company\Offers;

use App\Actions\Deliveries\AcceptDeliveryOfferAction;
use App\Actions\Deliveries\RejectDeliveryOfferAction;
use App\Domain\Tenancy\CurrentTenant;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\DeliveryOffer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * A single offer, including why the platform ranked this company for it.
 *
 * Showing the score breakdown is deliberate: a company that can see it is
 * being passed over on ETA rather than on price knows what to fix.
 */
class OfferDetail extends Component
{
    use UsesPortalLayout;

    public string $offerId = '';

    public string $rejectionReason = '';

    public function mount(string $offer): void
    {
        $this->offerId = DeliveryOffer::query()
            ->whereKey($offer)
            ->where('delivery_company_id', app(CurrentTenant::class)->companyOrFail()->id)
            ->firstOrFail()
            ->id;
    }

    #[Computed]
    public function offer(): DeliveryOffer
    {
        return DeliveryOffer::query()
            ->whereKey($this->offerId)
            ->with(['delivery.order', 'delivery.business', 'deliveryCompany'])
            ->firstOrFail();
    }

    public function accept(): void
    {
        try {
            $delivery = app(AcceptDeliveryOfferAction::class)->handle($this->offer(), auth()->user());
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), tone: 'error');
            unset($this->offer);

            return;
        }

        $this->redirectRoute('company.deliveries.show', $delivery->public_id, navigate: true);
    }

    public function reject(): void
    {
        app(RejectDeliveryOfferAction::class)->handle(
            offer: $this->offer(),
            respondedBy: auth()->user(),
            reason: $this->rejectionReason !== '' ? $this->rejectionReason : 'declined',
        );

        $this->redirectRoute('company.offers.index', navigate: true);
    }

    public function render(): View
    {
        return $this->portalView(
            'livewire.company.offers.offer-detail',
            title: $this->offer()->delivery->order->number,
        );
    }
}
