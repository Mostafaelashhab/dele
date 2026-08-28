<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\DeliveryOffer;
use App\Models\Order;
use App\Models\PricingRule;
use App\Models\Rider;
use App\Models\Role;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Renders every page in every portal against realistic data.
 *
 * Blade and Livewire faults only surface at render time, so a screen that no
 * behavioural test happens to open can break without anything going red. This
 * walks all of them, which is what makes a Blade typo a failing build rather
 * than a support call.
 */
class PageRendersTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private DeliveryCompany $company;

    private Order $order;

    private Delivery $delivery;

    private DeliveryOffer $offer;

    private Settlement $settlement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        $pickup = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        $dropoff = Zone::factory()->at(30.4560, 31.1900)->create(['code' => 'MNS']);

        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);

        $this->business = Business::factory()->create(['default_zone_id' => $pickup->id]);
        $this->company = DeliveryCompany::factory()->create();

        $this->company->serviceAreas()->sync([
            $pickup->id => ['accepts_pickup' => true, 'accepts_dropoff' => true, 'surcharge_minor' => 0],
            $dropoff->id => ['accepts_pickup' => true, 'accepts_dropoff' => true, 'surcharge_minor' => 0],
        ]);

        $rider = Rider::factory()->for($this->company)->online()->create();

        $this->order = Order::factory()
            ->for($this->business)
            ->between($pickup, $dropoff)
            ->create();

        $this->delivery = Delivery::factory()->create([
            'order_id' => $this->order->id,
            'business_id' => $this->business->id,
            'delivery_company_id' => $this->company->id,
            'rider_id' => $rider->id,
        ]);

        $this->offer = DeliveryOffer::factory()->create([
            'delivery_id' => $this->delivery->id,
            'delivery_company_id' => $this->company->id,
        ]);

        $this->settlement = Settlement::factory()->create(['party_id' => $this->company->id]);

        $this->business->addresses()->create([
            'zone_id' => $pickup->id,
            'label' => 'Main branch',
            'contact_name' => 'Owner',
            'contact_phone' => '01000000000',
            'address_line' => 'Banha',
            'city' => 'Banha',
            'is_default' => true,
        ]);
    }

    /**
     * The same screens again in English.
     *
     * Arabic is the default, so an English-only render fault — a malformed
     * plural specification, a key that exists but resolves to an array —
     * would otherwise never be exercised. One representative page per portal
     * is enough to catch that class of fault without doubling the suite.
     */
    #[Test]
    #[DataProvider('englishPages')]
    public function a_page_renders_in_english(string $portal, string $route): void
    {
        $user = match ($portal) {
            'admin' => $this->platformAdmin(),
            'business' => $this->businessUser(),
            'company' => $this->companyUser(),
            'rider' => $this->riderUser(),
        };

        $user->forceFill(['locale' => 'en'])->save();

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get($this->url($route))
            ->assertOk();

        // Without this the test would pass just as happily in Arabic, which
        // would make the whole English pass theatre.
        $response->assertSee('lang="en"', escape: false);
        $response->assertSee('dir="ltr"', escape: false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function englishPages(): array
    {
        return [
            'admin.analytics' => ['admin', 'admin.analytics'],
            'admin.live' => ['admin', 'admin.live'],
            'admin.orders.show' => ['admin', 'admin.orders.show'],
            'business.dashboard' => ['business', 'business.dashboard'],
            'business.orders.show' => ['business', 'business.orders.show'],
            'company.dashboard' => ['company', 'company.dashboard'],
            'company.deliveries.show' => ['company', 'company.deliveries.show'],
            'rider.home' => ['rider', 'rider.home'],
            'rider.deliveries.show' => ['rider', 'rider.deliveries.show'],
        ];
    }

    #[Test]
    #[DataProvider('adminPages')]
    public function an_admin_page_renders(string $route): void
    {
        $this->actingAs($this->platformAdmin())
            ->get($this->url($route))
            ->assertOk();
    }

    #[Test]
    #[DataProvider('businessPages')]
    public function a_business_page_renders(string $route): void
    {
        $this->actingAs($this->businessUser())
            ->get($this->url($route))
            ->assertOk();
    }

    #[Test]
    #[DataProvider('companyPages')]
    public function a_company_page_renders(string $route): void
    {
        $this->actingAs($this->companyUser())
            ->get($this->url($route))
            ->assertOk();
    }

    #[Test]
    #[DataProvider('riderPages')]
    public function a_rider_page_renders(string $route): void
    {
        $this->actingAs($this->riderUser())
            ->get($this->url($route))
            ->assertOk();
    }

    #[Test]
    public function the_public_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('tracking.show', $this->delivery->tracking_token))->assertOk();
        $this->get(route('rider.manifest'))->assertOk();
    }

    #[Test]
    public function every_page_renders_in_english_as_well_as_arabic(): void
    {
        // The interface is Arabic-first but must stay complete in English, so
        // a missing translation key cannot hide behind the default locale.
        $this->withSession(['locale' => 'en']);

        $this->actingAs($this->platformAdmin())->get('/admin')->assertOk()->assertSee('Dashboard');
        $this->actingAs($this->businessUser())->get('/app')->assertOk();
        $this->actingAs($this->companyUser())->get('/company')->assertOk();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminPages(): array
    {
        return self::named([
            'admin.dashboard', 'admin.live', 'admin.orders.index', 'admin.orders.show',
            'admin.businesses.index', 'admin.businesses.show',
            'admin.companies.index', 'admin.companies.onboard', 'admin.companies.show',
            'admin.riders.index', 'admin.zones.index', 'admin.pricing.index',
            'admin.settlements.index', 'admin.settlements.show',
            'admin.analytics', 'admin.audit.index', 'admin.settings.index',
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function businessPages(): array
    {
        return self::named([
            'business.dashboard', 'business.orders.index', 'business.orders.create',
            'business.orders.show', 'business.addresses.index', 'business.customers.index',
            'business.finance', 'business.team.index', 'business.api.index', 'business.settings',
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function companyPages(): array
    {
        return self::named([
            'company.dashboard', 'company.offers.index', 'company.offers.show',
            'company.deliveries.index', 'company.deliveries.show', 'company.riders.index',
            'company.service-areas', 'company.pricing.index', 'company.settlements.index',
            'company.settings',
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function riderPages(): array
    {
        return self::named(['rider.home', 'rider.history', 'rider.earnings', 'rider.deliveries.show']);
    }

    /**
     * @param  array<int, string>  $routes
     * @return array<string, array{0: string}>
     */
    private static function named(array $routes): array
    {
        return collect($routes)->mapWithKeys(fn (string $route) => [$route => [$route]])->all();
    }

    /**
     * Resolves the route, supplying whichever bound model it needs.
     */
    private function url(string $route): string
    {
        return match ($route) {
            'admin.orders.show', 'business.orders.show' => route($route, $this->order->number),
            'admin.businesses.show' => route($route, $this->business->id),
            'admin.companies.show' => route($route, $this->company->id),
            'admin.settlements.show' => route($route, $this->settlement->reference),
            'company.offers.show' => route($route, $this->offer->id),
            'company.deliveries.show', 'rider.deliveries.show' => route($route, $this->delivery->public_id),
            default => route($route),
        };
    }

    private function platformAdmin(): User
    {
        $user = User::factory()->create();

        Role::where('slug', UserRole::PlatformAdmin->value)->first()->users()->attach($user->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function businessUser(): User
    {
        $user = User::factory()->create();

        $this->business->memberships()->create([
            'user_id' => $user->id,
            'role' => UserRole::BusinessOwner->value,
            'is_active' => true,
        ]);

        return $user;
    }

    private function companyUser(): User
    {
        $user = User::factory()->create();

        $this->company->memberships()->create([
            'user_id' => $user->id,
            'role' => UserRole::CompanyOwner->value,
            'is_active' => true,
        ]);

        return $user;
    }

    private function riderUser(): User
    {
        $user = User::factory()->create();

        $this->delivery->rider->update(['user_id' => $user->id]);

        return $user;
    }
}
