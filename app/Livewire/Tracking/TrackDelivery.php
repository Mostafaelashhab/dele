<?php

namespace App\Livewire\Tracking;

use App\Actions\Deliveries\ReportDeliveryIssueAction;
use App\Domain\Tracking\TrackingPresenter;
use App\Enums\DeliveryIssueCategory;
use App\Models\Delivery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

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

    /** Whether the report form is open. */
    public bool $reporting = false;

    public string $issueCategory = '';

    public string $issueNote = '';

    public bool $justReported = false;

    /**
     * Reports one address may raise across all deliveries, and over how long.
     *
     * Separate from the per-delivery cap in the action: that one stops a
     * single leaked link being flooded, this one stops somebody working
     * through a list of them.
     */
    private const MAX_REPORTS = 4;

    private const DECAY_SECONDS = 3600;

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

    }

    /**
     * The problems worth offering for this delivery in its current state.
     *
     * @return array<int, DeliveryIssueCategory>
     */
    #[Computed]
    public function issueCategories(): array
    {
        $delivery = $this->delivery();

        return $delivery === null ? [] : DeliveryIssueCategory::availableFor($delivery);
    }

    public function startReport(): void
    {
        $this->reporting = true;
        $this->justReported = false;
        $this->issueCategory = '';
        $this->issueNote = '';
        $this->resetErrorBag();
    }

    public function cancelReport(): void
    {
        $this->reporting = false;
        $this->resetErrorBag();
    }

    public function submitReport(ReportDeliveryIssueAction $action): void
    {
        $delivery = $this->delivery();

        if ($delivery === null) {
            return;
        }

        $category = DeliveryIssueCategory::tryFrom($this->issueCategory);

        // Validated against what this delivery actually offers, not against
        // the enum: the form is public, and a category that makes no sense for
        // a parcel still in the shop must not arrive through a crafted post.
        if ($category === null || ! $category->appliesTo($delivery)) {
            throw ValidationException::withMessages([
                'issueCategory' => __('tracking.issue.category_required'),
            ]);
        }

        $this->validate([
            'issueNote' => ['nullable', 'string', 'max:500'],
        ], attributes: ['issueNote' => __('tracking.issue.note_label')]);

        $throttleKey = 'delivery-issue:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_REPORTS)) {
            throw ValidationException::withMessages([
                'issueCategory' => __('tracking.issue.throttled'),
            ]);
        }

        try {
            $action->handle($delivery, $category, $this->issueNote, request()->ip());
        } catch (Throwable) {
            // The action refuses on its own terms — too old, too many. Either
            // way the recipient is told the same thing rather than shown a
            // stack trace on a page they reached from a text message.
            throw ValidationException::withMessages([
                'issueCategory' => __('tracking.issue.throttled'),
            ]);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        $this->reporting = false;
        $this->justReported = true;
        $this->issueNote = '';
        $this->issueCategory = '';

        unset($this->issueCategories);

        $this->load();
    }

    private function delivery(): ?Delivery
    {
        return Delivery::query()
            ->where('tracking_token', $this->token)
            ->first();
    }

    public function shouldPoll(): bool
    {
        // Never while the form is open: a refresh mid-sentence would be a
        // re-render underneath somebody typing on a phone.
        return ! $this->reporting
            && ! $this->notFound
            && ! ($this->tracking['is_complete'] ?? false)
            && ! ($this->tracking['is_failed'] ?? false);
    }

    public function render(): View
    {
        return view('livewire.tracking.track-delivery')
            ->layout('components.layouts.public', [
                'title' => __('app.tracking.title'),
                // The one ground the whole product now shares. This page had
                // stayed light while everything around it went dark, so a
                // customer following a link from the site arrived somewhere
                // that did not look like the same company.
                'ground' => 'dark',
            ]);
    }
}
