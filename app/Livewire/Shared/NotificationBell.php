<?php

namespace App\Livewire\Shared;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Unread notification indicator.
 *
 * Polls rather than holding a socket: the platform runs on shared hosting,
 * where a long-lived connection per open tab is not available. A 20-second
 * poll is well inside the reaction time an operator needs, and the query
 * behind it is a single indexed count.
 */
class NotificationBell extends Component
{
    public bool $open = false;

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function recent(): Collection
    {
        return auth()->user()
            ?->notifications()
            ->latest()
            ->limit(8)
            ->get() ?? collect();
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);

        unset($this->unreadCount, $this->recent);
    }

    public function render(): View
    {
        return view('livewire.shared.notification-bell');
    }
}
