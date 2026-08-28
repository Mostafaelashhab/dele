<?php

namespace App\Domain\Tracking;

use App\Actions\Deliveries\ReportDeliveryIssueAction;
use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\DeliveryIssue;
use App\Models\DeliveryLocation;
use App\Models\OrderEvent;

/**
 * Builds the payload for the public tracking page.
 *
 * The page is unauthenticated, so this is the boundary that decides what a
 * stranger holding a link may see. Everything not explicitly listed here —
 * prices, the rider's phone number, internal ids, the business's own notes —
 * stays inside the platform.
 */
class TrackingPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Delivery $delivery): array
    {
        $delivery->loadMissing(['order', 'business', 'deliveryCompany', 'rider']);

        return [
            'order_number' => $delivery->order->number,
            'status' => $delivery->status->value,
            'status_label' => $delivery->status->label(),
            'status_tone' => $delivery->status->tone(),
            'timeline_step' => $delivery->status->timelineStep(),
            'is_complete' => $delivery->status === DeliveryStatus::Delivered,
            'is_failed' => in_array($delivery->status, [
                DeliveryStatus::Failed,
                DeliveryStatus::Cancelled,
                DeliveryStatus::Expired,
            ], true),
            'business' => [
                'name' => $delivery->business->displayName(),
            ],
            'delivery_company' => $delivery->deliveryCompany === null ? null : [
                'name' => $delivery->deliveryCompany->displayName(),
            ],
            // First name and vehicle only: enough for the customer to
            // recognise who is at the door, not enough to identify them.
            'rider' => $delivery->rider === null ? null : [
                'first_name' => explode(' ', trim($delivery->rider->name))[0] ?? '',
                'vehicle' => $delivery->rider->vehicle_type->label(),
                'rating' => $delivery->rider->rating_bps > 0
                    ? round($delivery->rider->rating(), 1)
                    : null,
            ],
            'destination' => $delivery->order->dropoffSnapshot()->toPublicArray(),
            'pickup_area' => $delivery->order->pickupSnapshot()->area,
            'estimated_arrival' => $delivery->estimatedArrival()?->toIso8601String(),
            'estimated_minutes_remaining' => $this->minutesRemaining($delivery),
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'rider_position' => $this->riderPosition($delivery),
            'confirmation_code' => $this->confirmationCode($delivery),
            'proof_recorded' => $delivery->hasProofOfDelivery(),
            'proof_photos' => $this->proofPhotos($delivery),
            'proof_by_code' => $delivery->confirmation_code_verified_at !== null,
            'received_by' => $delivery->delivered_at !== null ? $delivery->received_by : null,
            'timeline' => $this->timeline($delivery),
            'issues' => $this->issues($delivery),
            'can_report' => app(ReportDeliveryIssueAction::class)->isReportable($delivery),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * The photographs taken at the door, shown to the person who was waiting.
     *
     * Only after delivery: before it there is nothing to show, and a half-
     * finished handover is not something to publish. These reveal no more than
     * this page already does — the recipient's own address is on it — and they
     * are the difference between being told a parcel arrived and being able to
     * see where it was left.
     *
     * @return array<int, string>
     */
    private function proofPhotos(Delivery $delivery): array
    {
        if ($delivery->delivered_at === null) {
            return [];
        }

        return collect(['proof_photo_path', 'proof_photo_secondary_path'])
            ->map(fn (string $attribute) => $delivery->mediaUrl($attribute))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * The handover code, shown to whoever holds this tracking link.
     *
     * Withheld once the delivery is closed: after hand-off it proves nothing
     * and would sit on the page as a live-looking secret for as long as the
     * link is passed around. It is also withheld while the parcel is still at
     * the shop — a code shown before anyone could use it is just something
     * else to lose track of.
     */
    private function confirmationCode(Delivery $delivery): ?string
    {
        $usableDuring = [
            DeliveryStatus::PickedUp,
            DeliveryStatus::InTransit,
            DeliveryStatus::ArrivedAtDestination,
        ];

        if (! in_array($delivery->status, $usableDuring, true)) {
            return null;
        }

        return $delivery->confirmationCodeAvailable()
            ? $delivery->confirmation_code
            : null;
    }

    /**
     * The rider's live position, and only while they are actually carrying
     * this parcel. Before pickup and after hand-off it is nobody's business.
     *
     * @return array{lat: float, lng: float, recorded_at: string}|null
     */
    protected function riderPosition(Delivery $delivery): ?array
    {
        $visibleDuring = [
            DeliveryStatus::PickedUp,
            DeliveryStatus::InTransit,
            DeliveryStatus::ArrivedAtDestination,
        ];

        if (! in_array($delivery->status, $visibleDuring, true) || $delivery->rider === null) {
            return null;
        }

        $location = DeliveryLocation::query()
            ->where('delivery_id', $delivery->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        $point = $location?->point() ?? $delivery->rider->currentLocation();

        if ($point === null) {
            return null;
        }

        return [
            'lat' => $point->latitude,
            'lng' => $point->longitude,
            'recorded_at' => ($location?->recorded_at ?? $delivery->rider->location_updated_at ?? now())->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function timeline(Delivery $delivery): array
    {
        return $delivery->events()
            ->customerVisible()
            ->chronological()
            ->get()
            ->map(fn (OrderEvent $event) => [
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'status' => $event->to_status?->value,
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * The reports this recipient has already made, told back to them.
     *
     * Status and nothing more. An operator's resolution note is written for
     * the order record and for whoever picks the case up next — it is not a
     * reply to the customer, and publishing it here would turn every internal
     * remark into one.
     *
     * @return array<int, array{category: string, label: string, status: string,
     *     status_label: string, tone: string, reported_at: string, resolved_at: ?string}>
     */
    protected function issues(Delivery $delivery): array
    {
        return $delivery->issues()
            ->oldest()
            ->get()
            ->map(fn (DeliveryIssue $issue): array => [
                'category' => $issue->category->value,
                'label' => $issue->category->label(),
                'status' => $issue->status->value,
                'status_label' => $issue->status->label(),
                'tone' => $issue->status->tone(),
                'reported_at' => $issue->created_at->toIso8601String(),
                'resolved_at' => $issue->resolved_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    protected function minutesRemaining(Delivery $delivery): ?int
    {
        if ($delivery->status->isTerminal()) {
            return null;
        }

        $eta = $delivery->estimatedArrival();

        if ($eta === null) {
            return null;
        }

        return max(0, (int) now()->diffInMinutes($eta, false));
    }
}
