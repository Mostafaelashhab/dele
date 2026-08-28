<?php

namespace App\Livewire\Shared;

use App\Actions\Deliveries\ResolveDeliveryIssueAction;
use App\Models\Delivery;
use App\Models\DeliveryIssue;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * The operator's side of a recipient's report.
 *
 * One component for both portals rather than two: a complaint that reads one
 * way to the company handling it and another way to the platform reviewing it
 * is a complaint nobody can settle. What differs is only who is allowed to
 * open it, and that is a single check here.
 */
class DeliveryIssues extends Component
{
    public string $deliveryId = '';

    /** The issue currently being closed, if any. */
    public ?string $resolving = null;

    public string $resolution = '';

    public function mount(string $deliveryId): void
    {
        $this->deliveryId = $deliveryId;

        abort_unless($this->canManage(), 403);
    }

    /**
     * @return Collection<int, DeliveryIssue>
     */
    #[Computed]
    public function issues(): Collection
    {
        return DeliveryIssue::query()
            ->where('delivery_id', $this->deliveryId)
            ->with('resolvedBy')
            ->latest()
            ->get();
    }

    #[Computed]
    public function openCount(): int
    {
        return $this->issues()->reject->isResolved()->count();
    }

    public function acknowledge(string $issueId): void
    {
        $issue = $this->authorisedIssue($issueId);

        app(ResolveDeliveryIssueAction::class)->acknowledge($issue, auth()->user());

        unset($this->issues, $this->openCount);
    }

    public function startResolve(string $issueId): void
    {
        $this->authorisedIssue($issueId);

        $this->resolving = $issueId;
        $this->resolution = '';
        $this->resetErrorBag();
    }

    public function cancelResolve(): void
    {
        $this->resolving = null;
        $this->resolution = '';
        $this->resetErrorBag();
    }

    public function resolve(): void
    {
        $this->validate(
            ['resolution' => ['required', 'string', 'max:1000']],
            ['resolution.required' => __('tracking.issue.resolution_required')],
        );

        $issue = $this->authorisedIssue((string) $this->resolving);

        try {
            app(ResolveDeliveryIssueAction::class)->resolve($issue, auth()->user(), $this->resolution);
        } catch (Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), tone: 'error');

            return;
        }

        $this->resolving = null;
        $this->resolution = '';

        unset($this->issues, $this->openCount);

        $this->dispatch('toast', message: __('tracking.issue.status.resolved'), tone: 'success');
    }

    /**
     * Platform staff see every report; a company sees only the reports raised
     * against deliveries it was carrying.
     */
    private function canManage(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->is_active) {
            return false;
        }

        if ($user->isPlatformStaff()) {
            return true;
        }

        $companyId = Delivery::query()->whereKey($this->deliveryId)->value('delivery_company_id');

        return $companyId !== null
            && $user->companyMemberships()
                ->where('delivery_company_id', $companyId)
                ->where('is_active', true)
                ->exists();
    }

    /**
     * Re-checked on every write, not just on mount: the delivery could have
     * been reassigned since the page was opened.
     */
    private function authorisedIssue(string $issueId): DeliveryIssue
    {
        abort_unless($this->canManage(), 403);

        return DeliveryIssue::query()
            ->whereKey($issueId)
            ->where('delivery_id', $this->deliveryId)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.shared.delivery-issues');
    }
}
