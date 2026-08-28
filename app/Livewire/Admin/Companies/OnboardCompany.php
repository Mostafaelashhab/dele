<?php

namespace App\Livewire\Admin\Companies;

use App\Domain\Audit\AuditLogger;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\SettlementPeriod;
use App\Enums\UserRole;
use App\Livewire\Concerns\UsesPortalLayout;
use App\Models\DeliveryCompany;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Onboards an existing delivery company in one pass: the company record, a
 * dispatcher login, and its service areas.
 *
 * Everything a company needs to start receiving offers is captured here, so
 * an operator sitting with a courier owner can finish in a single sitting.
 */
class OnboardCompany extends Component
{
    use UsesPortalLayout;

    public string $name = '';

    public string $nameAr = '';

    public string $contactPerson = '';

    public string $phone = '';

    public string $email = '';

    public string $addressLine = '';

    public int $maxConcurrent = 50;

    public int $commissionBps = 1200;

    public string $settlementPeriod = 'weekly';

    public bool $autoAccept = false;

    public bool $createLogin = true;

    public string $ownerName = '';

    public string $ownerEmail = '';

    public string $ownerPassword = '';

    /**
     * @var array<int, string>
     */
    public array $zoneIds = [];

    /**
     * @return Collection<int, Zone>
     */
    #[Computed]
    public function zones(): Collection
    {
        return app(ZoneResolver::class)->activeZones();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'nameAr' => ['nullable', 'string', 'max:120'],
            'contactPerson' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^01[0-2,5]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'addressLine' => ['nullable', 'string', 'max:255'],
            'maxConcurrent' => ['required', 'integer', 'min:1', 'max:500'],
            'commissionBps' => ['required', 'integer', 'min:0', 'max:5000'],
            'settlementPeriod' => ['required', Rule::enum(SettlementPeriod::class)],
            'zoneIds' => ['required', 'array', 'min:1'],
            'zoneIds.*' => ['string', 'exists:zones,id'],
            'ownerName' => [Rule::requiredIf($this->createLogin), 'nullable', 'string', 'max:120'],
            'ownerEmail' => [
                Rule::requiredIf($this->createLogin), 'nullable', 'email', 'max:255',
                Rule::unique('users', 'email'),
            ],
            'ownerPassword' => [Rule::requiredIf($this->createLogin), 'nullable', Password::defaults()],
        ]);

        $company = DB::transaction(function () use ($validated): DeliveryCompany {
            $company = DeliveryCompany::create([
                'name' => $validated['name'],
                'name_ar' => $validated['nameAr'] ?: $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'contact_person' => $validated['contactPerson'],
                'phone' => $validated['phone'],
                'email' => $validated['email'] ?: null,
                'status' => AccountStatus::Active,
                'address_line' => $validated['addressLine'] ?: null,
                'provider' => 'internal',
                'auto_accept_offers' => $this->autoAccept,
                'max_concurrent_deliveries' => $validated['maxConcurrent'],
                'commission_bps' => $validated['commissionBps'],
                'settlement_period' => $validated['settlementPeriod'],
            ]);

            $company->forceFill(['onboarded_at' => now()])->save();

            // Every selected zone is enabled for both directions by default;
            // the company narrows it later from its own portal.
            $company->serviceAreas()->sync(
                collect($validated['zoneIds'])
                    ->mapWithKeys(fn (string $id) => [$id => [
                        'accepts_pickup' => true,
                        'accepts_dropoff' => true,
                        'surcharge_minor' => 0,
                    ]])
                    ->all()
            );

            if ($this->createLogin) {
                $user = User::create([
                    'name' => $validated['ownerName'],
                    'email' => $validated['ownerEmail'],
                    'phone' => $validated['phone'],
                    'password' => $validated['ownerPassword'],
                    'locale' => 'ar',
                ]);

                $company->memberships()->create([
                    'user_id' => $user->id,
                    'role' => UserRole::CompanyOwner->value,
                    'is_primary_contact' => true,
                    'is_active' => true,
                ]);

                Role::where('slug', UserRole::CompanyOwner->value)->first()?->users()->attach($user->id, [
                    'tenant_type' => 'delivery_company',
                    'tenant_id' => $company->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            app(AuditLogger::class)->log(
                action: AuditAction::Created,
                entity: $company,
                description: 'Delivery company onboarded.',
                tenantType: 'delivery_company',
                tenantId: $company->id,
            );

            return $company;
        });

        session()->flash('status', __('app.common.save'));

        $this->redirectRoute('admin.companies.show', $company->id, navigate: true);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $suffix = 1;

        while (DeliveryCompany::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    public function render(): View
    {
        return $this->portalView('livewire.admin.companies.onboard-company', [
            'periods' => SettlementPeriod::cases(),
        ], __('app.common.create'));
    }
}
