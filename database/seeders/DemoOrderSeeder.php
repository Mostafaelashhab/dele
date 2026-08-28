<?php

namespace Database\Seeders;

use App\Actions\Deliveries\AcceptDeliveryOfferAction;
use App\Actions\Deliveries\AdvanceDeliveryAction;
use App\Actions\Deliveries\AssignRiderAction;
use App\Actions\Deliveries\RespondToAssignmentAction;
use App\Actions\Orders\CreateOrderAction;
use App\Domain\Dispatch\DispatchService;
use App\Domain\Orders\OrderData;
use App\Domain\Shared\ValueObjects\LocationSnapshot;
use App\Domain\Shared\ValueObjects\Money;
use App\Enums\DeliveryPriority;
use App\Enums\PackageSize;
use App\Enums\PaymentType;
use App\Models\Business;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Runs real deliveries through the real code path, so a freshly seeded
 * database looks and behaves like an operating network.
 *
 * Nothing here writes a status directly: every order goes through the same
 * actions and state machine as production traffic. That is deliberate — if
 * the seeder can produce a delivered order, the pipeline genuinely works.
 */
class DemoOrderSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, phone: string, address: string, zone: string}>
     */
    private const RECIPIENTS = [
        ['name' => 'سارة محمود', 'phone' => '01098765401', 'address' => '١٢ شارع الجلاء', 'zone' => 'BNH-MNS'],
        ['name' => 'خالد عزت', 'phone' => '01098765402', 'address' => '٤ شارع المستشفى', 'zone' => 'BNH-UNI'],
        ['name' => 'منى صابر', 'phone' => '01098765403', 'address' => 'عمارة ٧ الحي الجديد', 'zone' => 'BNH-NEW'],
        ['name' => 'حسام الدين', 'phone' => '01098765404', 'address' => 'بجوار مسجد الرحمة', 'zone' => 'BNH-KGZ'],
        ['name' => 'ياسمين طه', 'phone' => '01098765405', 'address' => '٢٢ شارع فريد ندا', 'zone' => 'BNH-FND'],
        ['name' => 'عمر شوقي', 'phone' => '01098765406', 'address' => 'أمام محطة القطار', 'zone' => 'BNH-STN'],
        ['name' => 'دينا فاروق', 'phone' => '01098765407', 'address' => 'شارع سندنهور الرئيسي', 'zone' => 'BNH-SND'],
        ['name' => 'محمد رأفت', 'phone' => '01098765408', 'address' => 'ميت راضي البلد', 'zone' => 'BNH-MTR'],
    ];

    public function run(): void
    {
        // Notifications would otherwise fill the log with hundreds of seeded
        // SMS lines; the delivery pipeline itself still runs in full.
        Notification::fake();

        $businesses = Business::query()->active()->get();
        $zones = Zone::query()->get()->keyBy('code');
        $dispatcher = app(DispatchService::class);

        if ($businesses->isEmpty()) {
            $this->command?->warn('No businesses seeded; run NetworkSeeder first.');

            return;
        }

        $created = 0;

        foreach (range(1, 14) as $index) {
            $business = $businesses[$index % $businesses->count()];
            $recipient = self::RECIPIENTS[$index % count(self::RECIPIENTS)];
            $zone = $zones->get($recipient['zone']);

            try {
                $order = $this->createOrder($business, $recipient, $zone, $index);

                // Jobs are queued, not run, during seeding, so dispatch is
                // driven synchronously here to get real offers on the board.
                $dispatcher->dispatch($order->currentDelivery);

                $this->advance($order->fresh('currentDelivery'), $index);

                $created++;
            } catch (Throwable $exception) {
                $this->command?->warn("Order {$index} could not be seeded: {$exception->getMessage()}");
            }
        }

        $this->command?->info("Seeded {$created} demo deliveries across the network.");
    }

    /**
     * @param  array<string, string>  $recipient
     */
    private function createOrder(Business $business, array $recipient, ?Zone $zone, int $index): Order
    {
        $pickupAddress = $business->addresses()->first();

        $isCod = $index % 3 === 0;

        return app(CreateOrderAction::class)->handle(
            business: $business,
            data: new OrderData(
                pickup: $pickupAddress?->toSnapshot($business->contact_person, $business->phone)
                    ?? new LocationSnapshot(
                        contactName: $business->contact_person ?? $business->name,
                        contactPhone: $business->phone,
                        addressLine: (string) $business->address_line,
                        city: 'Banha',
                        latitude: $business->latitude,
                        longitude: $business->longitude,
                        zoneId: $business->default_zone_id,
                    ),
                dropoff: new LocationSnapshot(
                    contactName: $recipient['name'],
                    contactPhone: $recipient['phone'],
                    addressLine: $recipient['address'],
                    area: $zone?->name_ar,
                    city: 'Banha',
                    latitude: $zone?->centroid_latitude,
                    longitude: $zone?->centroid_longitude,
                    zoneId: $zone?->id,
                ),
                priority: $index % 5 === 0 ? DeliveryPriority::Express : DeliveryPriority::Standard,
                packageSize: $index % 7 === 0 ? PackageSize::Large : PackageSize::Small,
                paymentType: $isCod ? PaymentType::CashOnDelivery : PaymentType::Prepaid,
                codAmount: $isCod ? Money::ofMajor(random_int(80, 450)) : null,
                reference: 'SEED-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                notes: $index % 4 === 0 ? 'برجاء الاتصال بالعميل قبل الوصول' : null,
            ),
            dispatchImmediately: false,
        );
    }

    /**
     * Walks a delivery as far down the lifecycle as its index dictates, so the
     * seeded board shows a realistic spread of in-flight and completed work.
     */
    private function advance(Order $order, int $index): void
    {
        $delivery = $order->currentDelivery;
        $offer = $delivery?->offers()->pending()->orderBy('rank')->first();

        // Every fourth order is left waiting on the board, which is what an
        // operator's screen actually looks like mid-shift.
        if ($offer === null || $index % 4 === 1) {
            return;
        }

        $delivery = app(AcceptDeliveryOfferAction::class)->handle($offer);

        $rider = $delivery->deliveryCompany
            ?->riders()
            ->availableForWork()
            ->first();

        if ($rider === null) {
            return;
        }

        $assignment = app(AssignRiderAction::class)->handle($delivery, $rider);

        if ($index % 4 === 2) {
            return;
        }

        $delivery = app(RespondToAssignmentAction::class)->accept($assignment);

        $advance = app(AdvanceDeliveryAction::class);
        $rider = $rider->fresh();

        $delivery = $advance->arrivedAtPickup($delivery, $rider);
        $delivery = $advance->pickedUp($delivery, $rider);
        $delivery = $advance->startTransit($delivery, $rider);

        if ($index % 4 === 3) {
            return;
        }

        $delivery = $advance->arrivedAtDestination($delivery, $rider);

        $advance->delivered(
            delivery: $delivery,
            rider: $rider,
            receivedBy: $order->dropoffSnapshot()->contactName,
            codCollected: $order->payment_type->requiresCollection()
                ? $order->cod_amount_minor
                : null,
        );
    }
}
