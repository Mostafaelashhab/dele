<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Domain\Deliveries\Actor;
use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Self-service onboarding for shops.
 *
 * A new business starts Active so it can create its first delivery
 * immediately — the pilot's whole point is real orders — while the platform
 * keeps the ability to suspend an account that misbehaves.
 */
class RegisterBusinessController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function create(): View
    {
        return view('auth.register', [
            'zones' => Zone::query()->active()->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', Rule::in([
                'restaurant', 'pharmacy', 'grocery', 'clothing', 'electronics', 'online', 'other',
            ])],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^01[0-2,5]\d{8}$/', Rule::unique('users', 'phone')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'zone_id' => ['nullable', 'string', Rule::exists('zones', 'id')],
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

            // The zone is optional and may be absent from the payload
            // entirely, not merely empty.
            $zone = filled($validated['zone_id'] ?? null)
                ? Zone::find($validated['zone_id'])
                : null;

            $business = Business::create([
                'name' => $validated['business_name'],
                'name_ar' => $validated['business_name'],
                'slug' => $this->uniqueSlug($validated['business_name']),
                'category' => $validated['category'],
                'contact_person' => $validated['contact_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'status' => AccountStatus::Active,
                'default_zone_id' => $zone?->id,
                'address_line' => $validated['address_line'] ?? null,
                'latitude' => $zone?->centroid_latitude,
                'longitude' => $zone?->centroid_longitude,
            ]);

            $business->forceFill(['onboarded_at' => now()])->save();

            $business->memberships()->create([
                'user_id' => $user->id,
                'role' => UserRole::BusinessOwner->value,
                'is_primary_contact' => true,
                'is_active' => true,
            ]);

            $role = Role::where('slug', UserRole::BusinessOwner->value)->first();

            $role?->users()->attach($user->id, [
                'tenant_type' => 'business',
                'tenant_id' => $business->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->auditLogger->log(
                action: AuditAction::Created,
                entity: $business,
                actor: Actor::user($user),
                description: 'Business self-registered.',
                tenantType: 'business',
                tenantId: $business->id,
            );

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('business.dashboard')
            ->with('status', __('app.auth.register').' — '.__('app.name'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $suffix = 1;

        while (Business::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
