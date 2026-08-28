<div @if ($this->shouldPoll()) wire:poll.15s="load" @endif
     class="mx-auto flex min-h-dvh max-w-lg flex-col">

    <header class="safe-top flex items-center gap-2.5 px-5 py-4">
        <span class="flex size-7 items-center justify-center rounded bg-ink-900 text-white">
            <x-ui.icon name="truck" class="size-4" />
        </span>
        <span class="text-sm font-semibold text-ink-900">{{ __('app.name') }}</span>
    </header>

    @if ($notFound)
        <div class="flex flex-1 items-center justify-center px-5">
            <x-ui.card class="w-full">
                <x-ui.empty icon="alert" :title="__('app.tracking.not_found')" />
            </x-ui.card>
        </div>
    @else
        @php
            $step = $tracking['timeline_step'];
            $failed = $tracking['is_failed'];
            $complete = $tracking['is_complete'];

            $steps = [
                ['key' => 'order_received',   'label' => __('app.tracking.order_received'),   'at' => 1],
                ['key' => 'company_assigned', 'label' => __('app.tracking.company_assigned'), 'at' => 2],
                ['key' => 'rider_assigned',   'label' => __('app.tracking.rider_assigned'),   'at' => 3],
                ['key' => 'on_the_way',       'label' => __('app.tracking.on_the_way'),       'at' => 4],
                ['key' => 'delivered',        'label' => __('app.tracking.delivered'),        'at' => 5],
            ];
        @endphp

        <main class="flex-1 space-y-4 px-5 pb-8">

            {{-- The single most important thing on the page: where is it,
                 and when does it get here. Everything else is supporting. --}}
            <section @class([
                'rounded-card px-5 py-6 text-white',
                'bg-ink-900' => ! $failed && ! $complete,
                'bg-emerald-700' => $complete,
                'bg-red-800' => $failed,
            ])>
                <p class="text-xs font-medium opacity-75">
                    {{ $tracking['business']['name'] }}
                </p>
                <h1 class="mt-1.5 text-2xl font-bold tracking-tight">
                    {{ $tracking['status_label'] }}
                </h1>

                @if ($complete)
                    <p class="mt-2 text-sm opacity-90">
                        {{ __('app.tracking.delivered_at', [
                            'time' => \Illuminate\Support\Carbon::parse($tracking['delivered_at'])->translatedFormat('H:i'),
                        ]) }}
                    </p>
                @elseif ($failed)
                    <p class="mt-2 text-sm opacity-90">{{ __('app.tracking.failed') }}</p>
                @elseif ($tracking['estimated_minutes_remaining'] !== null)
                    <p class="mt-2 text-sm opacity-90">
                        @if ($tracking['estimated_minutes_remaining'] <= 2)
                            {{ __('app.tracking.arriving_now') }}
                        @else
                            {{ __('app.tracking.minutes_remaining', ['minutes' => $tracking['estimated_minutes_remaining']]) }}
                        @endif
                    </p>
                @endif

                <p class="mt-4 border-t border-white/20 pt-3 text-2xs opacity-70">
                    {{ __('app.common.order') ?? '' }} {{ $tracking['order_number'] }}
                </p>
            </section>

            {{-- The handover code.

                 Deliberately the loudest thing on the page while a rider is
                 carrying the parcel: the recipient has to be able to read it
                 out at a doorstep, on a phone, possibly in the dark. It
                 disappears once the delivery closes — see the presenter. --}}
            @if ($tracking['confirmation_code'] !== null)
                <section class="rounded-card border-2 border-signal-600 bg-white p-5 text-center">
                    <p class="text-sm font-bold text-ink-900">{{ __('tracking.code.title') }}</p>
                    <p class="tnum mt-3 text-5xl font-bold tracking-[0.3em] text-signal-700" dir="ltr">
                        {{ $tracking['confirmation_code'] }}
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ __('tracking.code.body') }}</p>
                    <p class="mt-2 flex items-center justify-center gap-1.5 text-xs text-ink-500">
                        <x-ui.icon name="shield" class="size-3.5 shrink-0" />
                        {{ __('tracking.code.warning') }}
                    </p>
                </section>
            @endif

            @if ($complete && $tracking['proof_recorded'])
                <section class="rounded-card border border-emerald-200 bg-emerald-50 p-4">
                    <p class="flex items-center gap-2 text-sm font-bold text-emerald-900">
                        <x-ui.icon name="check" class="size-4 shrink-0" />
                        {{ __('tracking.code.proof_title') }}
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-emerald-800">
                        {{ __('tracking.code.proof_body') }}
                    </p>
                </section>
            @endif

            @if (! $failed)
                <x-ui.card>
                    <ol class="space-y-0">
                        @foreach ($steps as $index => $item)
                            @php
                                $done = $step >= $item['at'];
                                $current = $step === $item['at'] && ! $complete;
                                $isLast = $index === count($steps) - 1;
                            @endphp
                            <li class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <span @class([
                                        'flex size-6 shrink-0 items-center justify-center rounded-full border-2',
                                        'border-emerald-600 bg-emerald-600 text-white' => $done && ! $current,
                                        'border-signal-600 bg-signal-600 text-white' => $current,
                                        'border-ink-200 bg-white' => ! $done,
                                    ])>
                                        @if ($done && ! $current)
                                            <x-ui.icon name="check" class="size-3.5" />
                                        @elseif ($current)
                                            <span class="size-2 rounded-full bg-white"></span>
                                        @endif
                                    </span>
                                    @unless ($isLast)
                                        <span @class([
                                            'w-0.5 flex-1',
                                            'bg-emerald-600' => $step > $item['at'],
                                            'bg-ink-200' => $step <= $item['at'],
                                        ])></span>
                                    @endunless
                                </div>
                                <div class="{{ $isLast ? 'pb-0' : 'pb-6' }} pt-0.5">
                                    <p @class([
                                        'text-sm',
                                        'font-semibold text-ink-900' => $done,
                                        'text-ink-400' => ! $done,
                                    ])>{{ $item['label'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-ui.card>
            @endif

            @if ($tracking['rider'])
                <x-ui.card>
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-ink-100">
                            <x-ui.icon name="user" class="size-5 text-ink-500" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-ink-900">
                                {{ $tracking['rider']['first_name'] }}
                            </p>
                            <p class="text-xs text-ink-500">
                                {{ $tracking['rider']['vehicle'] }}
                                @if ($tracking['delivery_company'])
                                    · {{ $tracking['delivery_company']['name'] }}
                                @endif
                            </p>
                        </div>
                        @if ($tracking['rider']['rating'])
                            <span class="tnum text-sm font-semibold text-ink-700">
                                {{ number_format($tracking['rider']['rating'], 1) }}
                            </span>
                        @endif
                    </div>

                    @if ($tracking['rider_position'])
                        <div class="mt-3 flex items-center gap-2 rounded-md bg-signal-50 px-3 py-2">
                            <x-ui.icon name="navigation" class="size-4 shrink-0 text-signal-600" />
                            <p class="text-xs text-signal-900">
                                {{ __('app.tracking.last_updated') }}
                                {{ \Illuminate\Support\Carbon::parse($tracking['rider_position']['recorded_at'])->diffForHumans() }}
                            </p>
                        </div>
                    @endif
                </x-ui.card>
            @endif

            {{-- The map.

                 The page already sends `referrer: no-referrer`, so requesting
                 tiles cannot leak the tracking token to the tile host, and the
                 payload itself carries the rider's position only while they
                 are actually carrying the parcel. --}}
            @if ($this->hasMap())
                <x-ui.map
                    :id="\App\Livewire\Tracking\TrackDelivery::MAP_ID"
                    :markers="$map['markers']"
                    :height="260"
                    :zoom="14"
                    :max-zoom="15"
                    style="muted" />
            @endif

            <x-ui.card :title="__('delivery.labels.timeline')" flush>
                <ul class="divide-y divide-ink-100">
                    @foreach (array_reverse($tracking['timeline']) as $event)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="text-xs text-ink-700">{{ $event['label'] }}</span>
                            <span class="tnum shrink-0 text-2xs text-ink-400">
                                {{ \Illuminate\Support\Carbon::parse($event['occurred_at'])->translatedFormat('H:i') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        </main>
    @endif

    <footer class="safe-bottom px-5 pt-2 text-center">
        <p class="text-2xs text-ink-400">
            {{ __('app.tracking.powered_by', ['name' => __('app.name')]) }}
        </p>
    </footer>
</div>
