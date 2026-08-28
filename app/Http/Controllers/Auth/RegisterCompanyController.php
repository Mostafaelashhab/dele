<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DeliveryCompany;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Self-service onboarding for delivery companies.
 *
 * The public side of the platform argues hard that a company outside the
 * network never sees the orders being distributed inside it. That argument is
 * only honest if a company can actually join without waiting for someone to
 * pick up the phone — which is what this route exists for.
 *
 * Unlike a shop, a company does not start Active. It starts Pending, and
 * `DeliveryCompany::dispatchable()` already excludes anything that is not
 * Active, so a self-registered company receives no offers until the platform
 * approves it. The account is real and usable in the meantime — it can be
 * signed into and configured — but it is not yet part of dispatch. Admin
 * onboarding at `admin.companies.onboard` remains the fast path for a company
 * an operator is sitting with, and that one still starts Active.
 */
class RegisterCompanyController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ZoneResolver $zoneResolver,
    ) {}

    public function create(): View
    {
        return view('auth.register-company', [
            'zones' => $this->zoneResolver->activeZones(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^01[0-2,5]\d{8}$/', Rule::unique('users', 'phone')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'fleet_size' => ['required', 'integer', 'min:1', 'max:500'],
            'zone_ids' => ['required', 'array', 'min:1'],
            'zone_ids.*' => ['string', Rule::exists('zones', 'id')],
            'address_line' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['contact_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'locale' => app()->getLocale(),
            ]);

            $company = DeliveryCompany::create([
                'name' => $validated['company_name'],
                'name_ar' => $validated['company_name'],
                'slug' => $this->uniqueSlug($validated['company_name']),
                'contact_person' => $validated['contact_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                // Pending, not Active — see the class docblock.
                'status' => AccountStatus::Pending,
                'address_line' => $validated['address_line'] ?? null,
                'provider' => 'internal',
                'auto_accept_offers' => false,
                'max_concurrent_deliveries' => $validated['fleet_size'],
                'commission_bps' => (int) config('platform.settlements.company_commission_bps', 1200),
                'settlement_period' => config('platform.settlements.default_period', 'weekly'),
            ]);

            // Declared coverage is taken at face value in both directions; the
            // company narrows it from its own portal once it is approved.
            $company->serviceAreas()->sync(
                collect($validated['zone_ids'])
                    ->mapWithKeys(fn (string $id) => [$id => [
                        'accepts_pickup' => true,
                        'accepts_dropoff' => true,
                        'surcharge_minor' => 0,
                    ]])
                    ->all()
            );

            $company->memberships()->create([
                'user_id' => $user->id,
                'role' => UserRole::CompanyOwner->value,
                'is_primary_contact' => true,
                'is_active' => true,
            ]);

            Role::where('slug', UserRole::CompanyOwner->value)->first()
                ?->users()->attach($user->id, [
                    'tenant_type' => 'delivery_company',
                    'tenant_id' => $company->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->auditLogger->log(
                action: AuditAction::Created,
                entity: $company,
                actor: Actor::user($user),
                description: 'Delivery company self-registered, pending review.',
                tenantType: 'delivery_company',
                tenantId: $company->id,
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('company.dashboard')
            ->with('status', __('app.auth.company_pending_flash'));
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
}
