<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\DeliveryCompany;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A rider with no company behind them.
 *
 * Modelled as a delivery company of one, so most of this checks that the
 * disguise holds: dispatch must treat them like any other carrier, while the
 * two things that genuinely differ — identity documents, and being kept out of
 * dispatch until those are checked — must actually differ.
 */
class IndependentRiderTest extends TestCase
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
    public function a_rider_can_register_alone_and_becomes_a_company_of_one(): void
    {
        $this->submit()->assertRedirect(route('rider.home'));

        $company = DeliveryCompany::where('name', 'محمد إبراهيم')->sole();

        $this->assertTrue($company->is_solo, 'A lone rider is a solo company.');
        $this->assertSame(AccountStatus::Pending, $company->status);
        $this->assertSame(1, $company->max_concurrent_deliveries, 'One person carries one parcel.');
        $this->assertCount(1, $company->riders);
        $this->assertTrue($company->serviceAreas->contains($this->zone));
        $this->assertNotNull($company->soloRider());
        $this->assertAuthenticated();
    }

    /**
     * The property that makes taking someone's ID acceptable: it is never
     * reachable by URL, only through an authorised read.
     */
    #[Test]
    public function the_id_card_is_stored_privately_and_the_face_photo_is_not(): void
    {
        $this->submit();

        $rider = DeliveryCompany::where('name', 'محمد إبراهيم')->sole()->riders()->sole();

        $this->assertTrue($rider->hasPrivateMedia('id_card_front_path'));
        $this->assertTrue($rider->hasPrivateMedia('id_card_back_path'));

        // On the private disk, and nowhere near the public one.
        Storage::disk('local')->assertExists($rider->id_card_front_path);
        Storage::disk('public')->assertMissing($rider->id_card_front_path);

        // The face photo is shown to a customer at the door, so it is ordinary
        // media and does have a URL.
        Storage::disk('public')->assertExists($rider->photo_path);
        $this->assertNotNull($rider->mediaUrl('photo_path'));
    }

    #[Test]
    public function identity_documents_are_required(): void
    {
        $this->submit(['id_card_front' => null, 'face_photo' => null])
            ->assertSessionHasErrors(['id_card_front', 'face_photo']);

        $this->assertSame(0, DeliveryCompany::where('is_solo', true)->count());
    }

    /**
     * The guarantee: an unverified stranger is never handed a parcel.
     */
    #[Test]
    public function an_unreviewed_rider_receives_no_orders(): void
    {
        $this->submit();

        $company = DeliveryCompany::where('is_solo', true)->sole();
        $rider = $company->riders()->sole();

        $this->assertTrue($rider->needsIdentityCheck());
        $this->assertTrue($rider->hasSubmittedIdentity());

        $this->assertFalse(
            DeliveryCompany::query()->dispatchable()->whereKey($company->id)->exists(),
            'A rider whose identity has not been checked must not be a dispatch candidate.'
        );

        $company->forceFill([
            'status' => AccountStatus::Active,
        ])->save();
        $rider->forceFill(['identity_verified_at' => now()])->save();

        $this->assertFalse($rider->fresh()->needsIdentityCheck());
        $this->assertTrue(
            DeliveryCompany::query()->dispatchable()->whereKey($company->id)->exists(),
            'Approving the rider should put them into dispatch.'
        );
    }

    #[Test]
    public function the_chooser_offers_the_rider_door(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('register.rider'))
            ->assertSee(__('marketing.choose.rider_cta'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = []): TestResponse
    {
        return $this->post('/register/rider', array_merge([
            'name' => 'محمد إبراهيم',
            'phone' => '01098765432',
            'email' => 'rider@solo.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'vehicle_type' => 'motorcycle',
            'vehicle_identifier' => 'ب ن ه ١٢٣',
            'zone_ids' => [$this->zone->id],
            'id_card_front' => UploadedFile::fake()->image('front.jpg'),
            'id_card_back' => UploadedFile::fake()->image('back.jpg'),
            'face_photo' => UploadedFile::fake()->image('face.jpg'),
        ], $overrides));
    }
}
