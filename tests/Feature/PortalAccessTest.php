<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Multi-tenancy is only real if it holds at the edge. These tests drive the
 * actual HTTP routes rather than the models, because that is where a mistake
 * would let one tenant read another's data.
 */
class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Zone::factory()->create(['code' => 'CTR']);
    }

    #[Test]
    public function a_guest_is_sent_to_the_login_page(): void
    {
        $this->get('/app')->assertRedirect(route('login'));
        $this->get('/company')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/rider')->assertRedirect(route('login'));
    }

    #[Test]
    public function a_business_user_reaches_their_own_dashboard(): void
    {
        [$user] = $this->businessUser();

        $this->actingAs($user)->get('/app')->assertOk();
    }

    #[Test]
    public function a_business_user_cannot_reach_the_admin_or_company_portals(): void
    {
        [$user] = $this->businessUser();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/company')->assertForbidden();
        $this->actingAs($user)->get('/rider')->assertForbidden();
    }

    #[Test]
    public function a_company_user_cannot_reach_the_business_or_admin_portals(): void
    {
        [$user] = $this->companyUser();

        $this->actingAs($user)->get('/company')->assertOk();
        $this->actingAs($user)->get('/app')->assertForbidden();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function platform_staff_reach_the_admin_portal_and_nothing_else_by_default(): void
    {
        $admin = User::factory()->create();
        Role::where('slug', UserRole::PlatformAdmin->value)->first()->users()->attach($admin->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();

        // Platform staff are not a tenant, so the tenant portals reject them.
        $this->actingAs($admin)->get('/app')->assertForbidden();
        $this->actingAs($admin)->get('/company')->assertForbidden();
    }

    #[Test]
    public function a_business_cannot_open_another_businesss_order(): void
    {
        [$user, $business] = $this->businessUser();

        $rivalOrder = Order::factory()->for(Business::factory()->create())->create();

        // A guessed order number must not become a way in.
        $this->actingAs($user)
            ->get(route('business.orders.show', $rivalOrder->number))
            ->assertNotFound();
    }

    #[Test]
    public function a_business_only_sees_its_own_orders_in_the_list(): void
    {
        [$user, $business] = $this->businessUser();

        $mine = Order::factory()->for($business)->create(['reference' => 'MINE-1']);
        Order::factory()->for(Business::factory()->create())->create(['reference' => 'THEIRS-1']);

        $this->actingAs($user)
            ->get(route('business.orders.index'))
            ->assertOk()
            ->assertSee($mine->number)
            ->assertDontSee('THEIRS-1');
    }

    #[Test]
    public function a_suspended_business_is_locked_out(): void
    {
        [$user, $business] = $this->businessUser();

        $business->forceFill(['status' => AccountStatus::Suspended, 'suspended_at' => now()])->save();

        $this->actingAs($user)->get('/app')->assertForbidden();
    }

    #[Test]
    public function a_deactivated_user_is_locked_out(): void
    {
        [$user] = $this->businessUser();

        $user->update(['is_active' => false]);

        $this->actingAs($user)->get('/app')->assertForbidden();
    }

    #[Test]
    public function a_rider_reaches_the_rider_app_only(): void
    {
        $company = DeliveryCompany::factory()->create();
        $user = User::factory()->create();
        Rider::factory()->for($company)->online()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/rider')->assertOk();
        $this->actingAs($user)->get('/app')->assertForbidden();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    #[Test]
    public function a_suspended_rider_cannot_open_the_app(): void
    {
        $company = DeliveryCompany::factory()->create();
        $user = User::factory()->create();
        Rider::factory()->for($company)->suspended()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/rider')->assertForbidden();
    }

    #[Test]
    public function a_business_can_register_itself_and_lands_in_its_dashboard(): void
    {
        $response = $this->post('/register/business', [
            'business_name' => 'مطعم زاد',
            'category' => 'restaurant',
            'contact_name' => 'كريم فؤاد',
            'phone' => '01012345678',
            'email' => 'owner@zad.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $response->assertRedirect(route('business.dashboard'));

        $this->assertDatabaseHas('businesses', ['name' => 'مطعم زاد', 'status' => 'active']);
        $this->assertDatabaseHas('users', ['email' => 'owner@zad.test']);
        $this->assertAuthenticated();
    }

    /**
     * Somebody sending their own parcel, with no shop behind them.
     *
     * The landing page invites an individual to send something, so this holds
     * the product to that invitation: the same pipeline as a shop, without
     * the trade name and category a person does not have.
     */
    #[Test]
    public function an_individual_can_register_to_send_without_a_shop(): void
    {
        $response = $this->post('/register/individual', [
            'contact_name' => 'مصطفى سالم',
            'phone' => '01111222333',
            'email' => 'person@banha.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticated();

        $business = Business::where('phone', '01111222333')->sole();

        $this->assertTrue($business->is_individual);
        $this->assertSame('مصطفى سالم', $business->name, 'An individual trades under their own name.');
    }

    #[Test]
    public function a_shop_still_has_to_say_what_it_is(): void
    {
        $this->post('/register/business', [
            'contact_name' => 'كريم فؤاد',
            'phone' => '01111222444',
            'email' => 'shop@banha.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertSessionHasErrors(['business_name', 'category']);
    }

    #[Test]
    public function a_delivery_company_can_register_itself_but_starts_pending(): void
    {
        $zone = Zone::factory()->create();

        $response = $this->post('/register/company', [
            'company_name' => 'سرعة للتوصيل',
            'contact_name' => 'هالة سمير',
            'phone' => '01098765432',
            'email' => 'owner@sora.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'fleet_size' => 12,
            'zone_ids' => [$zone->id],
        ]);

        $response->assertRedirect(route('company.dashboard'));
        $this->assertAuthenticated();

        $company = DeliveryCompany::where('name', 'سرعة للتوصيل')->sole();

        $this->assertSame(AccountStatus::Pending, $company->status);
        $this->assertSame(12, $company->max_concurrent_deliveries);
        $this->assertTrue($company->serviceAreas->contains($zone));
    }

    /**
     * The safety property that makes self-registration acceptable: a company
     * that signed itself up is not in dispatch until someone approves it.
     */
    #[Test]
    public function a_self_registered_company_is_not_dispatchable_until_approved(): void
    {
        $zone = Zone::factory()->create();

        $this->post('/register/company', [
            'company_name' => 'سرعة للتوصيل',
            'contact_name' => 'هالة سمير',
            'phone' => '01098765432',
            'email' => 'owner@sora.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'fleet_size' => 12,
            'zone_ids' => [$zone->id],
        ]);

        $company = DeliveryCompany::where('name', 'سرعة للتوصيل')->sole();

        $this->assertFalse(
            DeliveryCompany::query()->dispatchable()->whereKey($company->id)->exists(),
            'A pending company must never be a dispatch candidate.'
        );

        $company->forceFill(['status' => AccountStatus::Active])->save();

        $this->assertTrue(
            DeliveryCompany::query()->dispatchable()->whereKey($company->id)->exists(),
            'Approving the company should put it into dispatch.'
        );
    }

    #[Test]
    public function a_pending_company_is_told_why_its_board_is_empty(): void
    {
        $zone = Zone::factory()->create();

        $this->post('/register/company', [
            'company_name' => 'سرعة للتوصيل',
            'contact_name' => 'هالة سمير',
            'phone' => '01098765432',
            'email' => 'owner@sora.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'fleet_size' => 12,
            'zone_ids' => [$zone->id],
        ]);

        $this->get(route('company.dashboard'))
            ->assertOk()
            ->assertSee(__('app.auth.company_pending_title'));
    }

    #[Test]
    public function the_register_chooser_offers_both_doors(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee(route('register.business'))
            ->assertSee(route('register.company'));
    }

    #[Test]
    public function signing_in_records_the_login_and_audits_it(): void
    {
        [$user] = $this->businessUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('business.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'logged_in']);
    }

    #[Test]
    public function a_failed_sign_in_is_audited_and_does_not_authenticate(): void
    {
        [$user] = $this->businessUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed']);
    }

    #[Test]
    public function repeated_failed_sign_ins_are_throttled(): void
    {
        [$user] = $this->businessUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // The sixth attempt is refused on the throttle, not the password.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function the_public_landing_page_is_reachable_and_indexable(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(__('app.tagline'))
            ->assertDontSee('name="robots" content="noindex', false);
    }

    /**
     * @return array{0: User, 1: Business}
     */
    private function businessUser(): array
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        $business->memberships()->create([
            'user_id' => $user->id,
            'role' => UserRole::BusinessOwner->value,
            'is_active' => true,
        ]);

        return [$user, $business];
    }

    /**
     * @return array{0: User, 1: DeliveryCompany}
     */
    private function companyUser(): array
    {
        $company = DeliveryCompany::factory()->create();
        $user = User::factory()->create();

        $company->memberships()->create([
            'user_id' => $user->id,
            'role' => UserRole::CompanyOwner->value,
            'is_active' => true,
        ]);

        return [$user, $company];
    }
}
