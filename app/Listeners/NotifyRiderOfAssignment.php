<?php

namespace App\Listeners;

use App\Domain\Deliveries\Events\RiderAssignmentOffered;
use App\Notifications\RiderAssignmentReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;

class NotifyRiderOfAssignment implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(RiderAssignmentOffered $event): void
    {
        $rider = $event->assignment->rider;
        $user = $rider->user;

        if ($user !== null) {
            $user->notify(new RiderAssignmentReceived($event->assignment));

            return;
        }

        // A rider without a portal account still gets the job by SMS.
        (new AnonymousNotifiable)
            ->route('sms', $rider->phone)
            ->notify(new RiderAssignmentReceived($event->assignment));
    }
}
