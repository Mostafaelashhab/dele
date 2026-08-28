<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\ReviewQueue;
use App\Models\DeliveryCompany;
use App\Models\Rider;
use App\Models\Role;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The screen that lets somebody keep the promise the registration form makes.
 *
 * Before this existed, a self-registered company or rider landed in Pending
 * and nothing in the product could move them out of it — the only status
 * control toggled Active against Suspended. A rider was asked for their ID
 * card and told a reviewer would look at it, and no screen could open one.
 *
 * The tests that matter most here are the ones about who may see a document:
 * these files are acceptable to hold only because they are unreachable.
 */
class IdentityReviewTest extends TestCase
{
    use RefreshDatabase;

    private Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Storage::fake('public');
        Storage::fake('local');

        $this->zone = Zone::factory()->create();
    }

    #[Test]
    public function approving_a_pending_rider_puts_them_into_dispatch(): void
    {
        $company = $this->registerRider();
        $rider = $company->riders()->sole();

        $this->assertFalse(
            DeliveryCompany::query()->dispatchable()->whereKey($company->id)->exists()
        );

        Livewire::actingAs($this->admin())
            ->test(ReviewQueue::class)
            ->call('approve', $company->id);

        $company->refresh();
        $rider->refresh();

        $this->assertSame(AccountStatus::Active, $company->status);
        $this->assertNotNull($rider->identity_verified_at, 'Approval records that the ID was checked.');
        $this->assertFalse($rider->needsIdentityCheck());

        $this->assertTrue(
            DeliveryCompany::query()->dispatchable()->whereKey($company->id)->exists(),
            'An approved rider must become a dispatch candidate.'
        );
    }

    #[Test]
    public function rejecting_records_the_reason_rather_than_deleting_the_application(): void
    {
        $company = $this->registerRider();

        Livewire::actingAs($this->admin())
            ->test(ReviewQueue::class)
            ->call('startRejection', $company->id)
            ->set('rejectionReason', 'صورة البطاقة غير واضحة')
            ->call('reject')
            ->assertHasNoErrors();

        $company->refresh();

        $this->assertSame(AccountStatus::Closed, $company->status);
        $this->assertSame('صورة البطاقة غير واضحة', $company->suspension_reason);
        $this->assertSame(
            'صورة البطاقة غير واضحة',
            $company->riders()->sole()->identity_rejected_reason
        );
    }

    #[Test]
    public function a_rejection_must_say_why(): void
    {
        $company = $this->registerRider();

        Livewire::actingAs($this->admin())
            ->test(ReviewQueue::class)
            ->call('startRejection', $company->id)
            ->set('rejectionReason', '')
            ->call('reject')
            ->assertHasErrors('rejectionReason');

        $this->assertSame(AccountStatus::Pending, $company->fresh()->status);
    }

    /**
     * The property that makes holding an ID card acceptable.
     */
    #[Test]
    public function an_identity_document_is_unreachable_without_platform_staff(): void
    {
        $rider = $this->registerRider()->riders()->sole();

        $url = route('admin.identity.document', [$rider->id, 'id_card_front_path']);

        // A stranger.
        $this->get($url)->assertRedirect(route('login'));

        // A signed-in user who is not platform staff.
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();

        // And it is never on the public disk in the first place.
        Storage::disk('public')->assertMissing($rider->id_card_front_path);
    }

    #[Test]
    public function platform_staff_can_open_a_document_and_the_viewing_is_logged(): void
    {
        $rider = $this->registerRider()->riders()->sole();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.identity.document', [$rider->id, 'id_card_front_path']));

        $response->assertOk();
        $this->assertStringContainsString('image/', $response->headers->get('Content-Type'));

        // A reviewer's browser must not keep somebody's ID in its disk cache.
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'identity_viewed']);
    }

    /**
     * The parameter is an allowlist, not a path: a rider row holds other
     * columns containing paths and none of them belong on this endpoint.
     */
    #[Test]
    public function only_the_two_id_columns_can_be_requested(): void
    {
        $rider = $this->registerRider()->riders()->sole();

        foreach (['photo_path', 'proof_photo_path', 'created_at'] as $forbidden) {
            $this->actingAs($this->admin())
                ->get(route('admin.identity.document', [$rider->id, $forbidden]))
                ->assertNotFound();
        }
    }

    #[Test]
    public function the_queue_lists_what_is_waiting_and_nothing_else(): void
    {
        $waiting = $this->registerRider();
        $settled = DeliveryCompany::factory()->create(['status' => AccountStatus::Active]);

        Livewire::actingAs($this->admin())
            ->test(ReviewQueue::class)
            ->assertSee($waiting->displayName())
            ->assertDontSee($settled->displayName());
    }

    private function registerRider(): DeliveryCompany
    {
        $this->post('/register/rider', [
            'name' => 'محمد إبراهيم',
            'phone' => '01098765432',
            'email' => 'rider@solo.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'vehicle_type' => 'motorcycle',
            'zone_ids' => [$this->zone->id],
            'id_card_front' => UploadedFile::fake()->image('front.jpg'),
            'id_card_back' => UploadedFile::fake()->image('back.jpg'),
            'face_photo' => UploadedFile::fake()->image('face.jpg'),
        ]);

        // Registration signs the new rider in; the reviewer is somebody else.
        auth()->logout();

        return DeliveryCompany::where('is_solo', true)->sole();
    }

    private function admin(): User
    {
        $user = User::factory()->create();

        Role::where('slug', UserRole::PlatformAdmin->value)->first()
            ->users()->attach($user->id, ['created_at' => now(), 'updated_at' => now()]);

        return $user;
    }
}
