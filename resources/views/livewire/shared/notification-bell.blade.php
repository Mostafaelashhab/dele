<div class="relative" x-data="{ open: false }" @click.outside="open = false" wire:poll.20s>
    <button type="button" @click="open = !open"
            class="relative rounded-md p-1.5 text-ink-600 transition hover:bg-ink-100"
            aria-label="{{ __('app.nav.notifications') }}">
        <x-ui.icon name="bell" class="size-5" />
        @if ($this->unreadCount > 0)
            <span class="absolute -top-0.5 flex min-w-4 items-center justify-center rounded-full bg-red-600
                         px-1 text-2xs font-bold text-white ltr:-right-0.5 rtl:-left-0.5">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition.opacity
         class="absolute z-40 mt-2 w-80 overflow-hidden rounded-card border border-ink-200 bg-white shadow-lg
                ltr:right-0 rtl:left-0">
        <div class="flex items-center justify-between border-b border-ink-200 px-3 py-2">
            <p class="text-xs font-semibold text-ink-900">{{ __('app.nav.notifications') }}</p>
            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllRead"
                        class="text-2xs font-semibold text-signal-600 hover:text-signal-800">
                    {{ __('notification.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse ($this->recent as $notification)
                @php $data = $notification->data; @endphp
                <a href="{{ $data['url'] ?? '#' }}" wire:navigate
                   @class([
                       'block border-b border-ink-100 px-3 py-2.5 transition hover:bg-ink-50',
                       'bg-signal-50/60' => $notification->read_at === null,
                   ])>
                    <p class="text-xs font-semibold text-ink-900">
                        {{ $data['order_number'] ?? __('app.name') }}
                    </p>
                    <p class="mt-0.5 text-2xs text-ink-600">
                        @if (($data['type'] ?? null) === 'delivery_offer')
                            {{ __('delivery.event.DeliveryCompanyOffered') }} — {{ $data['pickup_area'] ?? '' }}
                        @elseif (($data['type'] ?? null) === 'rider_assignment')
                            {{ __('delivery.event.RiderAssigned') }}
                        @elseif (isset($data['status']))
                            {{ __('delivery.status.'.$data['status']) }}
                        @else
                            {{ __('delivery.event.OrderCreated') }}
                        @endif
                    </p>
                    <p class="mt-1 text-2xs text-ink-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-3 py-8 text-center text-xs text-ink-500">{{ __('notification.empty') }}</p>
            @endforelse
        </div>
    </div>
</div>
