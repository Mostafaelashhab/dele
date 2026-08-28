<?php

namespace App\Livewire\Business;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Team extends Component
{
    use UsesPortalLayout;

    public bool $inviting = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $role = 'business_staff';

    /**
     * @return Collection<int, BusinessUser>
     */
    #[Computed]
    public function members(): Collection
    {
        return BusinessUser::query()
            ->where('business_id', app(CurrentTenant::class)->businessOrFail()->id)
            ->with('user')
            ->orderByDesc('is_primary_contact')
            ->get();
    }

    public function save(): void
    {
        $business = app(CurrentTenant::class)->businessOrFail();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'regex:/^01[0-2,5]\d{8}$/', Rule::unique('users', 'phone')],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(['business_owner', 'business_staff'])],
        ]);

        DB::transaction(function () use ($business, $validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'locale' => app()->getLocale(),
            ]);

            $business->memberships()->create([
                'user_id' => $user->id,
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            // The role is attached scoped to this business, so the same person
            // can hold a different role at another tenant.
            Role::where('slug', $validated['role'])->first()?->users()->attach($user->id, [
                'tenant_type' => 'business',
                'tenant_id' => $business->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            app(AuditLogger::class)->log(
                action: AuditAction::Created,
                entity: $user,
                description: 'Team member added.',
                tenantType: 'business',
                tenantId: $business->id,
            );
        });

        $this->reset(['inviting', 'name', 'email', 'phone', 'password']);
        unset($this->members);

        session()->flash('status', __('app.common.save'));
    }

    public function toggleActive(int $membershipId): void
    {
        $membership = BusinessUser::query()
            ->whereKey($membershipId)
            ->where('business_id', app(CurrentTenant::class)->businessOrFail()->id)
            ->firstOrFail();

        // The account you are signed in as cannot lock itself out.
        if ($membership->user_id === auth()->id()) {
            $this->dispatch('toast', message: __('app.auth.inactive'), tone: 'error');

            return;
        }

        $membership->update(['is_active' => ! $membership->is_active]);

        unset($this->members);
    }

    public function render(): View
    {
        return $this->portalView('livewire.business.team', [
            'roles' => [
                'business_owner' => UserRole::BusinessOwner->label(),
                'business_staff' => UserRole::BusinessStaff->label(),
            ],
        ], __('app.nav.team'));
    }
}
