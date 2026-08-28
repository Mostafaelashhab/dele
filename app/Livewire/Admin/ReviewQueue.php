<?php

namespace App\Livewire\Admin;

use App\Domain\Audit\AuditLogger;
use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The queue of accounts waiting on a human.
 *
 * Self-registration created a promise the platform had no way to keep: a
 * company or a rider could sign up, land in Pending, and stay there — the only
 * status control in the admin toggled Active against Suspended, so nothing
 * could move an account out of Pending at all. A rider was asked for their ID
 * card and told a reviewer would look at it, and no screen could open it.
 *
 * This is that screen. Approving is deliberately two decisions rather than
 * one: a company is approved as a business, while a solo rider is approved as
 * a person whose identity was checked — and the second is recorded separately
 * because it is a claim somebody is making on the platform's behalf.
 */
class ReviewQueue extends Component
{
    use UsesPortalLayout;

    public string $rejectingId = '';

    public string $rejectionReason = '';

    /**
     * Companies waiting to be let into dispatch.
     *
     * @return Collection<int, DeliveryCompany>
     */
    #[Computed]
    public function pending(): Collection
    {
        return DeliveryCompany::query()
            ->where('status', AccountStatus::Pending)
            ->with(['riders', 'serviceAreas'])
            ->oldest('created_at')
            ->get();
    }

    /**
     * Approve an account and, for a solo rider, record that somebody actually
     * looked at their documents.
     */
    public function approve(string $companyId): void
    {
        $company = DeliveryCompany::query()
            ->where('status', AccountStatus::Pending)
            ->with('riders')
            ->findOrFail($companyId);

        DB::transaction(function () use ($company): void {
            // Not fillable: these stamps are the platform's own record of what
            // it decided, not something a form may set.
            $company->forceFill([
                'status' => AccountStatus::Active,
                'onboarded_at' => $company->onboarded_at ?? now(),
                'suspended_at' => null,
            ])->save();

            if ($company->is_solo) {
                $company->riders->each(fn (Rider $rider) => $rider->forceFill([
                    'identity_verified_at' => now(),
                    'identity_rejected_reason' => null,
                ])->save());
            }

            $logger = app(AuditLogger::class);

            $logger->log(
                action: AuditAction::AccountApproved,
                entity: $company,
                description: $company->is_solo
                    ? 'Independent rider approved after identity review.'
                    : 'Delivery company approved.',
                tenantType: 'delivery_company',
                tenantId: $company->id,
            );

            if ($company->is_solo) {
                $logger->log(
                    action: AuditAction::IdentityVerified,
                    entity: $company,
                    description: 'Identity documents accepted.',
                    tenantType: 'delivery_company',
                    tenantId: $company->id,
                );
            }
        });

        unset($this->pending);

        session()->flash('status', __('review.approved'));
    }

    public function startRejection(string $companyId): void
    {
        $this->rejectingId = $companyId;
        $this->rejectionReason = '';
    }

    public function cancelRejection(): void
    {
        $this->rejectingId = '';
        $this->rejectionReason = '';
    }

    /**
     * Turn an account down, with the reason recorded.
     *
     * Closed rather than deleted: somebody applied, and the record of that —
     * and of why it was refused — is what makes the decision reviewable later.
     */
    public function reject(): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:4', 'max:200'],
        ], attributes: ['rejectionReason' => __('review.reason')]);

        $company = DeliveryCompany::query()
            ->where('status', AccountStatus::Pending)
            ->with('riders')
            ->findOrFail($this->rejectingId);

        DB::transaction(function () use ($company): void {
            $company->forceFill([
                'status' => AccountStatus::Closed,
                'suspension_reason' => $this->rejectionReason,
            ])->save();

            $company->riders->each(fn (Rider $rider) => $rider->forceFill([
                'identity_rejected_reason' => $this->rejectionReason,
            ])->save());

            app(AuditLogger::class)->log(
                action: $company->is_solo
                    ? AuditAction::IdentityRejected
                    : AuditAction::StatusChanged,
                entity: $company,
                description: 'Application rejected: '.$this->rejectionReason,
                tenantType: 'delivery_company',
                tenantId: $company->id,
            );
        });

        $this->cancelRejection();
        unset($this->pending);

        session()->flash('status', __('review.rejected'));
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.review-queue', [], __('review.title'));
    }
}
