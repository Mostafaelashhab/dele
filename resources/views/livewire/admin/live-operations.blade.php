@php $counts = $this->counts; @endphp

<div wire:poll.10s="refreshBoard">
    <x-ui.page-header :title="__('app.nav.live')" :subtitle="config('platform.city')">
        <x-slot:actions>
            <button type="button" wire:click="toggleRiders"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold transition',
                        'bg-ink-900 text-white' => $showRiders,
                        'bg-white text-ink-600 ring-1 ring-inset ring-ink-300 hover:bg-ink-50' => ! $showRiders,
                    ])>
                <x-ui.icon name="motorcycle" class="size-3.5" />
                {{ __('app.nav.riders') }}
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filters sit in one row above everything they scope, so the map and the
         table always show the same slice. --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        @foreach ([
            'all' => ['label' => __('app.common.all'), 'count' => $counts['active'], 'tone' => 'neutral'],
            'unassigned' => ['label' => __('app.common.unassigned'), 'count' => $counts['unassigned'], 'tone' => 'amber'],
            'late' => ['label' => __('app.dashboard.failed_deliveries'), 'count' => $counts['late'], 'tone' => 'red'],
        ] as $key => $filter)
            <button type="button" wire:click="setFocus('{{ $key }}')"
                    @class([
                        'inline-flex items-center gap-2 rounded-md px-3 py-2 text-xs font-semibold transition',
                        'bg-signal-600 text-white' => $focus === $key,
                        'bg-white text-ink-700 ring-1 ring-inset ring-ink-200 hover:bg-ink-50' => $focus !== $key,
                    ])>
                {{ $filter['label'] }}
                <span @class([
                    'tnum rounded px-1.5 py-0.5 text-[10px]',
                    'bg-white/20' => $focus === $key,
                    'bg-ink-100 text-ink-600' => $focus !== $key && $filter['tone'] === 'neutral',
                    'bg-amber-100 text-amber-800' => $focus !== $key && $filter['tone'] === 'amber',
                    'bg-red-100 text-red-800' => $focus !== $key && $filter['tone'] === 'red',
                ])>{{ $filter['count'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-5">
        {{-- The map is the instrument, so it gets the space. --}}
        <div class="xl:col-span-3">
            <x-ui.map
                :id="\App\Livewire\Admin\LiveOperations::MAP_ID"
                :markers="$this->mapConfig()['markers']"
                :zones="$this->mapConfig()['zones']"
                :height="560"
                :mobile-height="300"
                :zoom="13"
                :fit="false"
                scroll-zoom />

            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 px-1">
                @foreach ([
                    ['class' => 'bg-signal-600', 'label' => __('delivery.labels.pickup')],
                    ['class' => 'bg-emerald-600', 'label' => __('delivery.labels.dropoff')],
                    ['class' => 'bg-ink-900', 'label' => __('delivery.labels.rider')],
                    ['class' => 'bg-red-600', 'label' => __('app.dashboard.failed_deliveries')],
                ] as $key)
                    <span class="flex items-center gap-1.5">
                        <span class="size-2.5 rounded-full {{ $key['class'] }} ring-2 ring-white"></span>
                        <span class="text-2xs text-ink-500">{{ $key['label'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>

        <div class="space-y-4 xl:col-span-2">
            <x-ui.card :title="__('app.dashboard.active_deliveries')" flush>
                @if ($this->deliveries->isEmpty())
                    <x-ui.empty icon="truck" :title="__('app.dashboard.no_active')" compact />
                @else
                    <ul class="max-h-75 divide-y divide-ink-100 overflow-y-auto">
                        @foreach ($this->deliveries as $delivery)
                            <li wire:key="d-{{ $delivery->id }}">
                                <a href="{{ route('admin.orders.show', $delivery->order->number) }}" wire:navigate
                                   @class([
                                       'flex items-center gap-3 px-4 py-2.5 transition hover:bg-ink-50',
                                       'bg-red-50/60' => $delivery->isLate(),
                                   ])>
                                    <x-ui.avatar
                                        :src="$delivery->business->mediaUrl('logo_path')"
                                        :name="$delivery->business->displayName()"
                                        size="sm" square />

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-semibold text-ink-900">
                                            {{ $delivery->order->number }}
                                        </p>
                                        <p class="truncate text-2xs text-ink-500">
                                            {{ $delivery->order->dropoffSnapshot()->area ?? '—' }}
                                            @if ($delivery->rider)
                                                · {{ $delivery->rider->name }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="shrink-0 text-end">
                                        <x-ui.badge :tone="$delivery->status->tone()" dot>
                                            {{ $delivery->status->label() }}
                                        </x-ui.badge>
                                        <p class="tnum mt-0.5 text-[10px] {{ $delivery->isLate() ? 'font-semibold text-red-600' : 'text-ink-400' }}">
                                            {{ $delivery->estimatedArrival()?->translatedFormat('H:i') ?? '—' }}
                                        </p>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card :title="__('app.dashboard.online_riders')" flush>
                @if ($this->riders->isEmpty())
                    <x-ui.empty icon="users" :title="__('app.common.empty')" compact />
                @else
                    <ul class="max-h-55 divide-y divide-ink-100 overflow-y-auto">
                        @foreach ($this->riders as $rider)
                            <li class="flex items-center gap-3 px-4 py-2.5" wire:key="r-{{ $rider->id }}">
                                <x-ui.avatar
                                    :src="$rider->mediaUrl('photo_path')"
                                    :name="$rider->name"
                                    size="sm"
                                    :tone="$rider->active_deliveries_count > 0 ? 'signal' : 'neutral'" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-medium text-ink-900">{{ $rider->name }}</p>
                                    <p class="flex items-center gap-1 truncate text-2xs text-ink-500">
                                        <x-ui.icon :name="$rider->vehicle_type->value === 'car' ? 'car' : 'motorcycle'"
                                                   class="size-3 shrink-0" />
                                        {{ $rider->deliveryCompany->displayName() }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-end">
                                    <span @class([
                                        'inline-block size-2 rounded-full',
                                        'bg-emerald-500' => $rider->status->value === 'online',
                                        'bg-amber-500' => $rider->status->value === 'busy',
                                    ])></span>
                                    <p class="tnum text-[10px] text-ink-400">
                                        {{ $rider->location_updated_at?->diffForHumans(short: true) }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
