<?php

namespace Tests\Feature;

use App\Domain\Tenancy\CurrentTenant;
use App\Enums\UserRole;
use App\Http\Middleware\EnsurePlatformStaff;
use App\Http\Middleware\ResolveBusinessTenant;
use App\Http\Middleware\ResolveCompanyTenant;
use App\Http\Middleware\ResolveRider;
use App\Models\Business;
use App\Models\DeliveryCompany;
use App\Models\PricingRule;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Portal screens after the first paint.
 *
 * PageRendersTest walks every page with a plain GET, which runs the route's
 * middleware and therefore always has a tenant. Livewire's own update requests
 * do not go through that middleware — it re-runs only what has been declared
 * persistent — so every click, form submit and `wire:poll` tick on a portal
 * screen took a completely different path through the app, and no test
 * covered it. That path was broken: the tenant was never resolved and the
 * first interaction on any portal page threw "No delivery company resolved
 * for this request."
 */
class PortalInteractionTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private DeliveryCompany $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        $zone = Zone::factory()->at(30.4610, 31.1840)->create(['code' => 'CTR']);
        PricingRule::factory()->create(['name' => 'Base', 'amount_minor' => 1500]);

        $this->business = Business::factory()->create(['default_zone_id' => $zone->id]);
        $this->company = DeliveryCompany::factory()->create();
    }

    /**
     * The registration itself, asserted directly: this is the single line
     * that keeps every portal interaction working, and it lives far from the
     * screens that depend on it.
     */
    #[Test]
    public function the_tenant_resolvers_survive_livewire_update_requests(): void
    {
        $persistent = app(PersistentMiddleware::class)->getPersistentMiddleware();

        foreach ([
            EnsurePlatformStaff::class,
            ResolveBusinessTenant::class,
            ResolveCompanyTenant::class,
            ResolveRider::class,
        ] as $middleware) {
            $this->assertContains(
                $middleware,
                $persistent,
                $middleware.' must be persistent or every Livewire interaction loses its tenant.'
            );
        }
    }

    /**
     * The end-to-end proof: render a portal page, then replay the component
     * the way the browser does, through Livewire's own endpoint.
     */
    #[Test]
    #[DataProvider('portalPages')]
    public function a_portal_screen_survives_a_livewire_update(string $portal, string $route): void
    {
        $user = $this->userFor($portal);

        $html = $this->actingAs($user)->get(route($route))->assertOk()->getContent();

        [$snapshot, $updateUri] = $this->componentFrom($html, $route);

        /*
         * A real second request arrives in a container that knows nothing
         * about the first. In a test the application instance is reused, so
         * the scoped CurrentTenant would still be holding the tenant that the
         * GET resolved — and the update request would pass for a reason the
         * browser never gets to enjoy. Forgetting the scoped instances is what
         * makes this a test of the update request rather than of the GET.
         */
        $this->app->forgetScopedInstances();

        // Replayed through the endpoint the page itself advertises. Livewire
        // randomises that path per install, so hard-coding /livewire/update
        // would only ever prove that a 404 is a 404.
        $response = $this->actingAs($user)
            ->withHeaders(['X-Livewire' => '1', 'Referer' => route($route)])
            ->postJson($updateUri, [
                'components' => [[
                    'snapshot' => $snapshot,
                    'updates' => [],
                    'calls' => [['path' => '', 'method' => '$refresh', 'params' => []]],
                ]],
                '_token' => csrf_token(),
            ]);

        // Before the tenant middleware was made persistent this came back a
        // 500 carrying "No delivery company resolved for this request."
        $response->assertOk();

        $this->assertStringNotContainsString(
            'resolved for this request',
            $response->getContent(),
            'The update request lost its tenant.'
        );

        // The response code alone is not proof: a component that never reads
        // the tenant returns 200 either way. This asserts the middleware
        // genuinely ran during the update.
        if ($portal !== 'admin') {
            $tenant = app(CurrentTenant::class);

            $this->assertNotNull(
                $portal === 'company' ? $tenant->company() : $tenant->business(),
                'The tenant was not resolved on the update request.'
            );
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function portalPages(): array
    {
        return [
            'company dashboard' => ['company', 'company.dashboard'],
            'company offers' => ['company', 'company.offers.index'],
            'company deliveries' => ['company', 'company.deliveries.index'],
            'company riders' => ['company', 'company.riders.index'],
            'business dashboard' => ['business', 'business.dashboard'],
            'business orders' => ['business', 'business.orders.index'],
            'business create order' => ['business', 'business.orders.create'],
            'admin live board' => ['admin', 'admin.live'],
            'admin analytics' => ['admin', 'admin.analytics'],
            'admin orders' => ['admin', 'admin.orders.index'],
        ];
    }

    /**
     * Livewire's name for the component a route renders.
     *
     * Derived from the class rather than read out of a registry, because
     * Livewire v4 exposes no such registry — and the mapping is a stable
     * convention: App\Livewire\Admin\LiveOperations is admin.live-operations.
     */
    private function componentNameFor(string $route): string
    {
        $class = Str::before(
            Route::getRoutes()->getByName($route)->getAction('uses'),
            '@'
        );

        return Str::of($class)
            ->after('App\\Livewire\\')
            ->explode('\\')
            ->map(fn (string $segment) => Str::kebab($segment))
            ->implode('.');
    }

    /**
     * The page's own component snapshot and update endpoint, read out of the
     * rendered HTML exactly as the browser reads them.
     *
     * The component is matched by name rather than by taking the first
     * snapshot on the page. Every portal screen renders the notification bell
     * before its own component, and the bell needs no tenant — so refreshing
     * it would have proved nothing about the bug this test exists for.
     *
     * @return array{string, string}
     */
    private function componentFrom(string $html, string $route): array
    {
        $component = $this->componentNameFor($route);

        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $snapshots);

        $match = collect($snapshots[1])
            ->map(fn (string $raw) => html_entity_decode($raw))
            ->first(fn (string $decoded) => str_contains($decoded, '"name":"'.$component.'"'));

        $this->assertNotNull(
            $match,
            "The page did not render the [{$component}] component this test means to exercise."
        );

        preg_match('/data-update-uri="([^"]+)"/', $html, $uri);

        $this->assertNotEmpty($uri, 'The page advertised no Livewire update endpoint.');

        return [$match, $uri[1]];
    }

    protected function userFor(string $portal): User
    {
        $user = User::factory()->create();

        match ($portal) {
            'admin' => Role::where('slug', UserRole::PlatformAdmin->value)->first()
                ->users()->attach($user->id, ['created_at' => now(), 'updated_at' => now()]),
            'business' => $this->business->memberships()->create([
                'user_id' => $user->id,
                'role' => UserRole::BusinessOwner->value,
                'is_active' => true,
            ]),
            'company' => $this->company->memberships()->create([
                'user_id' => $user->id,
                'role' => UserRole::CompanyOwner->value,
                'is_active' => true,
            ]),
        };

        return $user;
    }
}
