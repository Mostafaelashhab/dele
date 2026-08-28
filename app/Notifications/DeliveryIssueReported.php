<?php

namespace App\Notifications;

use App\Models\DeliveryIssue;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells whoever can act on it that the recipient has reported a problem.
 *
 * Database channel only. The platform sends no SMS or WhatsApp yet, and a
 * complaint is exactly the wrong thing to deliver through a gateway that
 * currently writes to a log file.
 */
class DeliveryIssueReported extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly DeliveryIssue $issue,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $delivery = $this->issue->delivery;

        return [
            'type' => 'delivery_issue',
            'issue_id' => $this->issue->id,
            'delivery_id' => $delivery->id,
            'order_number' => $delivery->order->number,
            'category' => $this->issue->category->value,
            'category_label' => $this->issue->category->label(),
            'is_urgent' => $this->issue->category->isUrgent(),
            'note' => $this->issue->note,
            'url' => $this->url($notifiable),
        ];
    }

    /**
     * Staff get the platform's view of the order; a company gets its own view
     * of the delivery, which is the only one it is allowed to open.
     */
    private function url(object $notifiable): string
    {
        $delivery = $this->issue->delivery;

        if ($notifiable instanceof User && $notifiable->isPlatformStaff()) {
            return route('admin.orders.show', $delivery->order->number);
        }

        return route('company.deliveries.show', $delivery->public_id);
    }
}
