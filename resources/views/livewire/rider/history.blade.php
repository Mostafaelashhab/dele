<div class="flex min-h-dvh flex-col bg-ink-100">
    <header class="safe-top sticky top-0 z-10 flex items-center gap-3 border-b border-ink-200 bg-white px-4 py-3">
        <a href="{{ route('rider.home') }}" wire:navigate class="-ms-1 p-1 text-ink-500">
            <x-ui.icon name="chevron-end" class="size-5 rotate-180 rtl:rotate-0" />
        </a>
        <h1 class="text-sm font-bold text-ink-900">{{ __('app.nav.history') }}</h1>
    </header>

    <main class="flex-1 space-y-2 p-4">
        @forelse ($deliveries as $delivery)
            <a href="{{ route('rider.deliveries.show', $delivery->public_id) }}" wire:navigate
               class="flex items-center gap-3 rounded-card border border-ink-200 bg-white px-4 py-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-ink-900">{{ $delivery->order->number }}</p>
                    <p class="mt-0.5 truncate text-xs text-ink-500">
                        {{ $delivery->order->dropoffSnapshot()->area }}
                        · {{ $delivery->assigned_at?->translatedFormat('d M g:i A') }}
                    </p>
                </div>
                <div class="shrink-0 text-end">
                    <p class="tnum text-sm font-semibold text-ink-900">
                        {{ $delivery->riderPayout()->format(false) }}
                    </p>
                    <x-ui.badge :tone="$delivery->status->tone()" class="mt-1">
                        {{ $delivery->status->label() }}
                    </x-ui.badge>
                </div>
            </a>
        @empty
            <x-ui.card>
                <x-ui.empty icon="history" :title="__('app.common.empty')" />
            </x-ui.card>
        @endforelse

        @if ($deliveries->hasPages())
            <div class="pt-2">{{ $deliveries->links() }}</div>
        @endif
    </main>
</div>
