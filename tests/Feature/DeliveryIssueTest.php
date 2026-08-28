<?php

namespace Tests\Feature;

use App\Actions\Deliveries\ReportDeliveryIssueAction;
use App\Enums\DeliveryIssueCategory;
use App\Enums\DeliveryIssueStatus;
use App\Enums\DeliveryStatus;
use App\Enums\OrderEventType;
use App\Enums\UserRole;
use App\Livewire\Shared\DeliveryIssues;
use App\Livewire\Tracking\TrackDelivery;
use App\Models\Delivery;
use App\Models\DeliveryCompany;
use App\Models\DeliveryIssue;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use App\Notifications\DeliveryIssueReported;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reporting a problem is the only write path a complete stranger can reach:
 * the tracking token is the whole credential, and the page has no login on it.
 *
 * So most of what follows is about what a report may *not* do — change the
 * parcel's state, flood the table, or reach a company it has nothing to do
 * with — rather than about the happy path.
 */
class DeliveryIssueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    #[Test]
    public function a_recipient_can_report_a_problem_from_the_tracking_page(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);

        Livewire::test(TrackDelivery::class, ['token' => $delivery->tracking_token])
            ->call('startReport')
            ->set('issueCategory', DeliveryIssueCategory::Late->value)
            ->set('issueNote', 'It has been two hours.')
            ->call('submitReport')
            ->assertHasNoErrors()
            ->assertSet('reporting', false)
            ->assertSee(DeliveryIssueCategory::Late->label());

        $issue = DeliveryIssue::query()->sole();

        $this->assertSame($delivery->id, $issue->delivery_id);
        $this->assertSame(DeliveryIssueCategory::Late, $issue->category);
        $this->assertSame(DeliveryIssueStatus::Open, $issue->status);
        $this->assertSame('It has been two hours.', $issue->note);

        // The state of the parcel when they complained, kept so the report is
        // still readable an hour later.
        $this->assertSame(DeliveryStatus::InTransit, $issue->delivery_status);
        $this->assertSame($delivery->delivery_company_id, $issue->delivery_company_id);
    }

    #[Test]
    public function a_report_never_changes_the_delivery(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);

        app(ReportDeliveryIssueAction::class)->handle($delivery, DeliveryIssueCategory::Late);

        // A stranger with a link must not be able to fail somebody's parcel.
        $this->assertSame(DeliveryStatus::InTransit, $delivery->fresh()->status);
        $this->assertSame($delivery->rider_id, $delivery->fresh()->rider_id);
    }

    #[Test]
    public function the_report_reaches_the_company_and_platform_staff(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);

        $dispatcher = User::factory()->create();
        $delivery->deliveryCompany->memberships()->create([
            'user_id' => $dispatcher->id,
            'role' => UserRole::CompanyOwner->value,
            'is_active' => true,
        ]);

        $staff = $this->staffUser();
        $stranger = User::factory()->create();

        app(ReportDeliveryIssueAction::class)->handle($delivery, DeliveryIssueCategory::NoContact);

        Notification::assertSentTo($dispatcher, DeliveryIssueReported::class);
        Notification::assertSentTo($staff, DeliveryIssueReported::class);
        Notification::assertNotSentTo($stranger, DeliveryIssueReported::class);
    }

    #[Test]
    public function the_report_stays_out_of_the_customers_timeline(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);

        app(ReportDeliveryIssueAction::class)->handle($delivery, DeliveryIssueCategory::Late);

        // Operators see it; the journey the recipient reads does not mix
        // "what happened to the parcel" with "what was said about it".
        $this->assertDatabaseHas('order_events', [
            'delivery_id' => $delivery->id,
            'type' => OrderEventType::IssueReported->value,
            'is_customer_visible' => false,
            'actor_type' => 'customer',
        ]);
    }

    #[Test]
    public function tapping_twice_does_not_raise_two_reports(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);
        $action = app(ReportDeliveryIssueAction::class);

        $dispatcher = User::factory()->create();
        $delivery->deliveryCompany->memberships()->create([
            'user_id' => $dispatcher->id,
            'role' => UserRole::CompanyOwner->value,
            'is_active' => true,
        ]);

        $first = $action->handle($delivery, DeliveryIssueCategory::Late);
        $second = $action->handle($delivery, DeliveryIssueCategory::Late);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DeliveryIssue::query()->count());

        // And the dispatcher is not told twice about the same complaint.
        Notification::assertSentToTimes($dispatcher, DeliveryIssueReported::class, 1);
    }

    #[Test]
    public function one_delivery_will_not_absorb_unlimited_reports(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::Delivered);

        DeliveryIssue::factory()
            ->count(ReportDeliveryIssueAction::MAX_PER_DELIVERY)
            ->create([
                'delivery_id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'delivery_status' => $delivery->status,
            ]);

        $this->expectExceptionMessage('report limit');

        app(ReportDeliveryIssueAction::class)->handle($delivery, DeliveryIssueCategory::Other);
    }

    #[Test]
    public function a_category_that_makes_no_sense_for_this_delivery_is_refused(): void
    {
        Notification::fake();

        // Still on its way: "marked delivered but I never got it" is not a
        // thing that can be true, and the form does not offer it. The check
        // has to survive a crafted post all the same.
        $delivery = $this->delivery(DeliveryStatus::InTransit);

        Livewire::test(TrackDelivery::class, ['token' => $delivery->tracking_token])
            ->call('startReport')
            ->set('issueCategory', DeliveryIssueCategory::NotReceived->value)
            ->call('submitReport')
            ->assertHasErrors('issueCategory');

        $this->assertSame(0, DeliveryIssue::query()->count());
    }

    #[Test]
    public function a_delivery_that_closed_long_ago_stops_accepting_reports(): void
    {
        $delivery = $this->delivery(DeliveryStatus::Delivered);
        $delivery->forceFill(['delivered_at' => now()->subDays(60)])->save();

        $this->assertFalse(app(ReportDeliveryIssueAction::class)->isReportable($delivery->fresh()));
    }

    #[Test]
    public function the_tracking_page_shows_the_report_back_but_never_the_internal_note(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::Delivered);

        $issue = app(ReportDeliveryIssueAction::class)
            ->handle($delivery, DeliveryIssueCategory::Damaged);

        $issue->forceFill([
            'status' => DeliveryIssueStatus::Resolved,
            'resolved_at' => now(),
            'resolution_note' => 'Rider says the box was already open at pickup.',
        ])->save();

        $this->get(route('tracking.show', $delivery->tracking_token))
            ->assertOk()
            ->assertSee(DeliveryIssueCategory::Damaged->label())
            ->assertSee(DeliveryIssueStatus::Resolved->label())
            // An operator's note is written for the record and for whoever
            // picks the case up next, not as a reply to the customer.
            ->assertDontSee('already open at pickup');
    }

    #[Test]
    public function a_company_can_only_open_reports_on_its_own_deliveries(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);
        app(ReportDeliveryIssueAction::class)->handle($delivery, DeliveryIssueCategory::Late);

        $outsider = User::factory()->create();
        DeliveryCompany::factory()->create()->memberships()->create([
            'user_id' => $outsider->id,
            'role' => UserRole::CompanyOwner->value,
            'is_active' => true,
        ]);

        Livewire::actingAs($outsider)
            ->test(DeliveryIssues::class, ['deliveryId' => $delivery->id])
            ->assertForbidden();
    }

    #[Test]
    public function closing_a_report_requires_saying_what_was_done(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);
        $issue = app(ReportDeliveryIssueAction::class)
            ->handle($delivery, DeliveryIssueCategory::NoContact);

        Livewire::actingAs($this->staffUser())
            ->test(DeliveryIssues::class, ['deliveryId' => $delivery->id])
            ->call('startResolve', $issue->id)
            ->set('resolution', '')
            ->call('resolve')
            ->assertHasErrors('resolution');

        $this->assertSame(DeliveryIssueStatus::Open, $issue->fresh()->status);

        Livewire::actingAs($staff = $this->staffUser())
            ->test(DeliveryIssues::class, ['deliveryId' => $delivery->id])
            ->call('startResolve', $issue->id)
            ->set('resolution', 'Called the recipient, rider was redirected.')
            ->call('resolve')
            ->assertHasNoErrors();

        $issue->refresh();

        $this->assertSame(DeliveryIssueStatus::Resolved, $issue->status);
        $this->assertSame($staff->id, $issue->resolved_by_user_id);

        $this->assertDatabaseHas('order_events', [
            'delivery_id' => $delivery->id,
            'type' => OrderEventType::IssueResolved->value,
        ]);
    }

    #[Test]
    public function acknowledging_is_not_closing(): void
    {
        Notification::fake();

        $delivery = $this->delivery(DeliveryStatus::InTransit);
        $issue = app(ReportDeliveryIssueAction::class)
            ->handle($delivery, DeliveryIssueCategory::Late);

        Livewire::actingAs($this->staffUser())
            ->test(DeliveryIssues::class, ['deliveryId' => $delivery->id])
            ->call('acknowledge', $issue->id);

        $issue->refresh();

        // Somebody has seen it — which the recipient can tell apart from
        // silence — but nothing has been settled.
        $this->assertSame(DeliveryIssueStatus::Acknowledged, $issue->status);
        $this->assertNotNull($issue->acknowledged_at);
        $this->assertNull($issue->resolved_at);
    }

    private function staffUser(): User
    {
        $user = User::factory()->create();

        Role::where('slug', UserRole::PlatformAdmin->value)->first()->users()->attach($user->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    private function delivery(DeliveryStatus $status): Delivery
    {
        $company = DeliveryCompany::factory()->create();
        $rider = Rider::factory()->for($company)->online()->create();
        $order = Order::factory()->create();

        return Delivery::factory()->create([
            'order_id' => $order->id,
            'business_id' => $order->business_id,
            'delivery_company_id' => $company->id,
            'rider_id' => $rider->id,
            'status' => $status,
            'delivered_at' => $status === DeliveryStatus::Delivered ? now() : null,
        ]);
    }
}
