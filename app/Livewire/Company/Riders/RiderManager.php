<?php

namespace App\Livewire\Company\Riders;

use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Enums\AuditAction;
use App\Enums\RiderStatus;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Rider roster for a delivery company.
 *
 * Adding a rider optionally creates a portal login, because a company can
 * onboard someone who will only ever be dispatched by phone, and one who will
 * use the PWA — both are real cases in this market.
 */
class RiderManager extends Component
{
    use UsesPortalLayout, WithFileUploads;

    public bool $creating = false;

    public string $name = '';

    public string $phone = '';

    public string $vehicleType = VehicleType::Motorcycle->value;

    public string $vehicleIdentifier = '';

    public int $maxConcurrent = 2;

    public bool $createLogin = true;

    public string $email = '';

    public string $password = '';

    public $photo = null;

    /**
     * @return Collection<int, Rider>
     */
    #[Computed]
    public function riders(): Collection
    {
        return Rider::query()
            ->where('delivery_company_id', app(CurrentTenant::class)->companyOrFail()->id)
            ->with('user')
            ->orderByRaw("CASE status WHEN 'online' THEN 0 WHEN 'busy' THEN 1 WHEN 'offline' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $company = app(CurrentTenant::class)->companyOrFail();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => [
                'required', 'string', 'regex:/^01[0-2,5]\d{8}$/',
                Rule::unique('riders', 'phone')->where('delivery_company_id', $company->id),
            ],
            'vehicleType' => ['required', Rule::enum(VehicleType::class)],
            'vehicleIdentifier' => ['nullable', 'string', 'max:64'],
            'maxConcurrent' => ['required', 'integer', 'min:1', 'max:10'],
            'email' => [Rule::requiredIf($this->createLogin), 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => [Rule::requiredIf($this->createLogin), 'nullable', 'string', 'min:8'],
            'photo' => ['nullable', 'image', 'max:'.(int) config('platform.media.max_upload_kb', 4096)],
        ]);

        DB::transaction(function () use ($company, $validated): void {
            $user = null;

            if ($this->createLogin) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'password' => $validated['password'],
                    'locale' => 'ar',
                ]);

                Role::where('slug', UserRole::Rider->value)->first()?->users()->attach($user->id, [
                    'tenant_type' => 'delivery_company',
                    'tenant_id' => $company->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $rider = Rider::create([
                'delivery_company_id' => $company->id,
                'user_id' => $user?->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'status' => RiderStatus::Offline,
                'vehicle_type' => $validated['vehicleType'],
                'vehicle_identifier' => $validated['vehicleIdentifier'] ?: null,
                'max_concurrent_deliveries' => $validated['maxConcurrent'],
            ]);

            if ($this->photo instanceof TemporaryUploadedFile) {
                $rider->storeMedia('photo_path', $this->photo, 'riders');
            }

            app(AuditLogger::class)->log(
                action: AuditAction::Created,
                entity: $rider,
                description: 'Rider added to roster.',
                tenantType: 'delivery_company',
                tenantId: $company->id,
            );
        });

        $this->reset(['creating', 'name', 'phone', 'vehicleIdentifier', 'email', 'password', 'photo']);
        unset($this->riders);

        session()->flash('status', __('app.common.save'));
    }

    /**
     * Suspending is reversible and does not delete history; a suspended rider
     * simply stops being eligible for dispatch.
     */
    public function toggleSuspension(string $riderId): void
    {
        $rider = Rider::query()
            ->whereKey($riderId)
            ->where('delivery_company_id', app(CurrentTenant::class)->companyOrFail()->id)
            ->firstOrFail();

        $suspending = $rider->status !== RiderStatus::Suspended;

        $rider->forceFill([
            'status' => $suspending ? RiderStatus::Suspended : RiderStatus::Offline,
            'suspension_reason' => $suspending ? 'suspended_by_company' : null,
        ])->save();

        app(AuditLogger::class)->log(
            action: $suspending ? AuditAction::Suspended : AuditAction::Reinstated,
            entity: $rider,
            tenantType: 'delivery_company',
            tenantId: $rider->delivery_company_id,
        );

        unset($this->riders);
    }

    public function render(): View
    {
        return $this->portalView('livewire.company.riders.rider-manager', [
            'vehicles' => VehicleType::cases(),
        ], __('app.nav.riders'));
    }
}
