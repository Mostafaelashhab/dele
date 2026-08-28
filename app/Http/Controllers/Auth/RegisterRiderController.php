<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Domain\Zones\ZoneResolver;
use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\RiderStatus;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Http\Controllers\Controller;
use App\Models\DeliveryCompany;
use App\Models\Rider;
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
 * Self-service onboarding for a rider working alone.
 *
 * Underneath, a solo rider is a delivery company with exactly one rider. That
 * is not a workaround: matching, offers, service areas, the ledger and
 * settlements are all built around a company, and introducing a second kind
 * of carrier would put the most heavily tested part of the product at risk to
 * express something the existing model already expresses. The `is_solo` flag
 * is what lets every screen say "rider" where "company" would be a lie.
 *
 * The difference that matters is trust. A company vouches for the people it
 * employs; somebody signing up alone has nobody to vouch for them. So this is
 * the one registration that asks for identity documents, and the account stays
 * Pending — and out of dispatch — until a human has looked at them.
 */
class RegisterRiderController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ZoneResolver $zoneResolver,
    ) {}

    public function create(): View
    {
        return view('auth.register-rider', [
            'zones' => $this->zoneResolver->activeZones(),
            'vehicles' => VehicleType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('platform.media.max_upload_kb', 4096);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^01[0-2,5]\d{8}$/', Rule::unique('users', 'phone')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'vehicle_identifier' => ['nullable', 'string', 'max:40'],
            'zone_ids' => ['required', 'array', 'min:1'],
            'zone_ids.*' => ['string', Rule::exists('zones', 'id')],

            // The documents. Required here and nowhere else in the product:
            // this is the only account that arrives with nobody behind it.
            'id_card_front' => ['required', 'image', 'max:'.$maxKb],
            'id_card_back' => ['required', 'image', 'max:'.$maxKb],
            'face_photo' => ['required', 'image', 'max:'.$maxKb],
        ]);

        $user = DB::transaction(function () use ($request, $validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'locale' => app()->getLocale(),
            ]);

            $company = DeliveryCompany::create([
                'name' => $validated['name'],
                'name_ar' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'contact_person' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                // Pending until the documents are checked. `dispatchable()`
                // excludes anything not Active, so no offer can reach them.
                'status' => AccountStatus::Pending,
                'provider' => 'internal',
                'auto_accept_offers' => false,
                // One person carries one parcel at a time.
                'max_concurrent_deliveries' => 1,
                'commission_bps' => (int) config('platform.settlements.company_commission_bps', 0),
                'settlement_period' => config('platform.settlements.default_period', 'weekly'),
            ]);

            $company->forceFill(['is_solo' => true])->save();

            $company->serviceAreas()->sync(
                collect($validated['zone_ids'])
                    ->mapWithKeys(fn (string $id) => [$id => [
                        'accepts_pickup' => true,
                        'accepts_dropoff' => true,
                        'surcharge_minor' => 0,
                    ]])
                    ->all()
            );

            $rider = Rider::create([
                'delivery_company_id' => $company->id,
                'user_id' => $user->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'status' => RiderStatus::Offline,
                'vehicle_type' => $validated['vehicle_type'],
                // Absent, not null: a nullable field the browser never submitted is
                // missing from the validated array entirely.
                'vehicle_identifier' => ($validated['vehicle_identifier'] ?? null) ?: null,
                'max_concurrent_deliveries' => 1,
            ]);

            // The face photo is ordinary media — it is shown to a customer at
            // the door. The ID card is not, and never gets a URL.
            $rider->storeMedia('photo_path', $request->file('face_photo'), 'riders');
            $rider->storePrivateMedia('id_card_front_path', $request->file('id_card_front'), 'identity');
            $rider->storePrivateMedia('id_card_back_path', $request->file('id_card_back'), 'identity');

            $company->memberships()->create([
                'user_id' => $user->id,
                'role' => UserRole::CompanyOwner->value,
                'is_primary_contact' => true,
                'is_active' => true,
            ]);

            foreach ([UserRole::CompanyOwner, UserRole::Rider] as $role) {
                Role::where('slug', $role->value)->first()
                    ?->users()->attach($user->id, [
                        'tenant_type' => 'delivery_company',
                        'tenant_id' => $company->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $this->auditLogger->log(
                action: AuditAction::Created,
                entity: $company,
                actor: Actor::user($user),
                description: 'Independent rider self-registered, identity pending review.',
                tenantType: 'delivery_company',
                tenantId: $company->id,
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('rider.home')
            ->with('status', __('app.auth.rider_pending_flash'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'rider';
        $slug = $base;
        $suffix = 1;

        while (DeliveryCompany::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
