<?php

namespace App\Actions\Deliveries;

use App\Domain\Deliveries\Actor;
use App\Domain\Deliveries\DeliveryTransitioner;
use App\Enums\DeliveryIssueCategory;
use App\Enums\DeliveryIssueStatus;
use App\Enums\OrderEventType;
use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\DeliveryIssue;
use App\Models\User;
use App\Notifications\DeliveryIssueReported;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

/**
 * Records a problem reported by the person waiting for a delivery.
 *
 * The report arrives from a page with no login on it, so this is the one
 * write path in the application a complete stranger can reach with nothing
 * but a tracking token. That shapes it in three ways:
 *
 * - the caller supplies a category from a fixed list and at most a short
 *   note, and nothing else on the row is taken from the request;
 * - a delivery accepts a limited number of reports, and only one open report
 *   per category, so a link that leaks cannot be turned into a flood;
 * - the parcel's own state is never touched. A complaint is a thing said
 *   about a delivery, not a thing that happens to it — moving a delivery to
 *   "failed" because somebody tapped a button would hand strangers control of
 *   the network.
 */
class ReportDeliveryIssueAction
{
    /** Reports one delivery will take, across every category. */
    public const MAX_PER_DELIVERY = 5;

    /** How long after a delivery closes reports are still accepted. */
    public const REPORTABLE_DAYS = 14;

    public function __construct(
        private readonly DeliveryTransitioner $transitioner,
    ) {}

    public function handle(
        Delivery $delivery,
        DeliveryIssueCategory $category,
        ?string $note = null,
        ?string $reporterIp = null,
    ): DeliveryIssue {
        if (! $this->isReportable($delivery)) {
            throw new RuntimeException('This delivery is no longer accepting reports.');
        }

        $issue = DB::transaction(function () use ($delivery, $category, $note, $reporterIp): DeliveryIssue {
            // Locked for the count: two taps on a slow connection are the
            // ordinary case here, not the exotic one.
            $existing = DeliveryIssue::query()
                ->where('delivery_id', $delivery->id)
                ->lockForUpdate()
                ->get();

            if ($existing->count() >= self::MAX_PER_DELIVERY) {
                throw new RuntimeException('This delivery has reached its report limit.');
            }

            $duplicate = $existing->first(fn (DeliveryIssue $issue): bool => $issue->category === $category
                && ! $issue->isResolved());

            if ($duplicate !== null) {
                return $duplicate;
            }

            return DeliveryIssue::create([
                'order_id' => $delivery->order_id,
                'delivery_id' => $delivery->id,
                'delivery_company_id' => $delivery->delivery_company_id,
                'rider_id' => $delivery->rider_id,
                'category' => $category,
                'status' => DeliveryIssueStatus::Open,
                'note' => $this->cleanNote($note),
                'delivery_status' => $delivery->status,
                'reporter_ip' => $reporterIp,
            ]);
        });

        // A duplicate returns the report they already made rather than an
        // error: they tapped twice, and telling them off for it helps nobody.
        if ($issue->wasRecentlyCreated) {
            $this->recordEvent($delivery, $issue);
            $this->notify($delivery, $issue);
        }

        return $issue;
    }

    /**
     * Whether this delivery is still open to reports at all.
     *
     * A parcel that arrived a month ago is somebody's accounting problem, not
     * an operational one, and reopening it here would only produce reports
     * nobody is in a position to act on.
     */
    public function isReportable(Delivery $delivery): bool
    {
        $closedAt = $delivery->delivered_at ?? ($delivery->status->isTerminal() ? $delivery->updated_at : null);

        return $closedAt === null
            || $closedAt->greaterThan(now()->subDays(self::REPORTABLE_DAYS));
    }

    /**
     * Internal only. The recipient sees their report on the tracking page as
     * its own thing; putting it in the delivery's journey would mix what
     * happened to the parcel with what was said about it.
     */
    private function recordEvent(Delivery $delivery, DeliveryIssue $issue): void
    {
        $this->transitioner->recordEvent(
            $delivery,
            $delivery->status,
            $delivery->status,
            OrderEventType::IssueReported,
            Actor::customer(),
            [
                'issue_id' => $issue->id,
                'category' => $issue->category->value,
                'note' => $issue->note,
            ],
        );
    }

    /**
     * Everyone who could actually do something about it: the company carrying
     * the parcel, and platform staff — who are the only recipients when no
     * company has taken it yet, which is itself a report worth reading.
     */
    private function notify(Delivery $delivery, DeliveryIssue $issue): void
    {
        $recipients = $delivery->deliveryCompany?->users()
            ->wherePivot('is_active', true)
            ->get() ?? new Collection;

        $staff = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn(
                'slug',
                array_map(fn (UserRole $role): string => $role->value, UserRole::platformRoles()),
            ))
            ->get();

        $all = $recipients->merge($staff)->unique('id');

        if ($all->isNotEmpty()) {
            Notification::send($all, new DeliveryIssueReported($issue));
        }
    }

    private function cleanNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : mb_substr($note, 0, 500);
    }
}
