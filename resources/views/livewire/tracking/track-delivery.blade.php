<div @if ($this->shouldPoll()) wire:poll.15s="load" @endif
     class="mx-auto flex min-h-dvh max-w-lg flex-col">

    <header class="safe-top px-5 py-4">
        <a href="{{ route('home') }}">
            <x-ui.logo size="sm" wordmark class="text-white" />
        </a>
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
                'relative overflow-hidden rounded-card border px-5 py-6 text-white',
                // A slow sheen, and only while it is actually on its way: a
                // delivered parcel is a fact, not an event.
                'track-sheen' => ! $failed && ! $complete,
                'border-ember-500/30 bg-gradient-to-br from-ember-500/20 to-transparent' => ! $failed && ! $complete,
                'border-emerald-500/30 bg-gradient-to-br from-emerald-500/20 to-transparent' => $complete,
                'border-red-500/30 bg-gradient-to-br from-red-500/20 to-transparent' => $failed,
            ])>
                <p class="text-xs font-medium text-ink-300">
                    {{ $tracking['business']['name'] }}
                </p>
                <h1 class="mt-1.5 text-2xl font-bold tracking-tight">
                    {{ $tracking['status_label'] }}
                </h1>

                @if ($complete)
                    <p class="mt-2 text-sm opacity-90">
                        {{ __('app.tracking.delivered_at', [
                            'time' => \Illuminate\Support\Carbon::parse($tracking['delivered_at'])->shortTime(),
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

                <p class="mt-4 border-t border-white/15 pt-3 text-2xs text-ink-400">
                    {{ __('app.common.order') ?? '' }} {{ $tracking['order_number'] }}
                </p>
            </section>

            {{-- The handover code.

                 Deliberately the loudest thing on the page while a rider is
                 carrying the parcel: the recipient has to be able to read it
                 out at a doorstep, on a phone, possibly in the dark. It
                 disappears once the delivery closes — see the presenter. --}}
            @if ($tracking['confirmation_code'] !== null)
                <section class="rounded-card border-2 border-ember-500/60 bg-ember-500/[0.08] p-5 text-center">
                    <p class="text-sm font-bold text-white">{{ __('tracking.code.title') }}</p>
                    <p class="tnum mt-3 text-5xl font-bold tracking-[0.3em] text-ember-400" dir="ltr">
                        {{ $tracking['confirmation_code'] }}
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-ink-300">{{ __('tracking.code.body') }}</p>
                    <p class="mt-2 flex items-center justify-center gap-1.5 text-xs text-ink-400">
                        <x-ui.icon name="shield" class="size-3.5 shrink-0" />
                        {{ __('tracking.code.warning') }}
                    </p>
                </section>
            @endif

            {{-- The proof, shown to the person who was waiting for it.

                 Being told a parcel arrived and being able to see where it was
                 left are different things, and the second is what settles an
                 argument. The photographs reveal nothing this page does not
                 already carry — the recipient's own address is on it. --}}
            @if ($complete && $tracking['proof_recorded'])
                <section class="track-step overflow-hidden rounded-card border border-emerald-500/25
                                bg-emerald-500/[0.07]"
                         style="animation-delay: 240ms">
                    <div class="p-4">
                        <p class="flex items-center gap-2 text-sm font-bold text-emerald-300">
                            <x-ui.icon name="check" class="size-4 shrink-0" />
                            {{ __('tracking.code.proof_title') }}
                        </p>
                        <p class="mt-1 text-xs leading-relaxed text-emerald-200/80">
                            {{ __('tracking.code.proof_body') }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @if ($tracking['proof_by_code'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15
                                             px-2.5 py-1 text-2xs font-bold text-emerald-300
                                             ring-1 ring-emerald-500/30">
                                    <x-ui.icon name="shield" class="size-3" />
                                    {{ __('tracking.code.proof_by_code') }}
                                </span>
                            @endif

                            @if ($tracking['received_by'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15
                                             px-2.5 py-1 text-2xs font-medium text-emerald-200
                                             ring-1 ring-emerald-500/30">
                                    {{ __('tracking.code.proof_received_by') }}:
                                    <span class="font-bold">{{ $tracking['received_by'] }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($tracking['proof_photos'] !== [])
                        <div class="border-t border-emerald-500/20 bg-white/[0.03] p-4">
                            <p class="text-2xs font-semibold uppercase tracking-wider text-emerald-300">
                                {{ __('tracking.code.proof_photos') }}
                            </p>

                            <div class="mt-2.5 grid gap-2 {{ count($tracking['proof_photos']) > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
                                @foreach ($tracking['proof_photos'] as $index => $photo)
                                    {{-- Opens full size: the point of a proof
                                         photo is being able to actually look
                                         at it. --}}
                                    <a href="{{ $photo }}" target="_blank" rel="noopener"
                                       class="track-step group relative block overflow-hidden rounded-lg
                                              ring-1 ring-emerald-500/30"
                                       style="animation-delay: {{ 320 + $index * 90 }}ms">
                                        <img src="{{ $photo }}"
                                             alt="{{ __('tracking.code.proof_photos') }} {{ $index + 1 }}"
                                             loading="lazy"
                                             class="aspect-4/3 w-full bg-white/5 object-cover
                                                    transition duration-300 group-hover:scale-[1.03]">

                                        <span class="absolute inset-x-0 bottom-0 flex items-center justify-center
                                                     gap-1 bg-ink-950/70 py-1.5 text-2xs font-semibold text-white
                                                     opacity-0 transition group-hover:opacity-100">
                                            <x-ui.icon name="search" class="size-3" />
                                            {{ __('tracking.code.proof_open') }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>

                            <p class="mt-2.5 text-2xs leading-relaxed text-emerald-200/70">
                                {{ __('tracking.code.proof_photos_hint') }}
                            </p>
                        </div>
                    @endif
                </section>
            @endif

            {{-- The journey, drawn from what actually happened.

                 This was two lists: a five-step summary here and a separate
                 event log at the bottom, both describing the same delivery in
                 different words. One list now, built from the real events, so
                 nothing is summarised away and nothing is repeated.

                 Every stage carries its own symbol. Drawing the same tick
                 against each one said only "done, done, done" and left the
                 shape carrying no information at all. --}}
            @if ($tracking['timeline'] !== [])
                <x-ui.card>
                    <ol class="space-y-0">
                        @foreach ($tracking['timeline'] as $index => $event)
                            @php
                                $isLast = $index === count($tracking['timeline']) - 1;
                                // The most recent event is where the parcel is
                                // now, unless the journey has finished.
                                $current = $isLast && ! $complete && ! $failed;
                                $delay = $index * 90;
                                $at = \Illuminate\Support\Carbon::parse($event['occurred_at']);
                            @endphp

                            <li class="track-step flex gap-3" style="animation-delay: {{ $delay }}ms">
                                <div class="flex flex-col items-center">
                                    <span @class([
                                        'flex size-8 shrink-0 items-center justify-center rounded-full border-2',
                                        'track-live border-ember-500 bg-ember-500 text-white' => $current,
                                        'border-emerald-600 bg-emerald-600 text-white' => ! $current && ! $failed,
                                        'border-red-500 bg-red-500 text-white' => $failed && $isLast,
                                    ])>
                                        <x-tracking.event-icon :type="$event['type']"
                                                               class="track-tick size-4"
                                                               style="animation-delay: {{ $delay + 140 }}ms" />
                                    </span>

                                    @unless ($isLast)
                                        <span class="relative w-0.5 flex-1 bg-white/10">
                                            <span class="track-rail absolute inset-0 bg-emerald-600"
                                                  style="animation-delay: {{ $delay + 70 }}ms"></span>
                                        </span>
                                    @endunless
                                </div>

                                <div class="{{ $isLast ? 'pb-0' : 'pb-5' }} min-w-0 flex-1 pt-1.5">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <p @class([
                                            'text-sm',
                                            'font-bold text-ember-400' => $current,
                                            'font-semibold text-white' => ! $current,
                                        ])>{{ $event['label'] }}</p>

                                        <time class="tnum shrink-0 text-2xs text-ink-400"
                                              datetime="{{ $event['occurred_at'] }}">
                                            {{ $at->shortTime() }}
                                        </time>
                                    </div>

                                    @if ($current && $tracking['estimated_minutes_remaining'] !== null)
                                        <p class="mt-1 text-xs font-medium text-ember-400">
                                            @if ($tracking['estimated_minutes_remaining'] <= 2)
                                                {{ __('app.tracking.arriving_now') }}
                                            @else
                                                {{ __('app.tracking.minutes_remaining', [
                                                    'minutes' => $tracking['estimated_minutes_remaining'],
                                                ]) }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-ui.card>
            @endif

            @if ($tracking['rider'])
                <x-ui.card>
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white/[0.06]">
                            <x-ui.icon name="user" class="size-5 text-ink-500" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">
                                {{ $tracking['rider']['first_name'] }}
                            </p>
                            <p class="text-xs text-ink-400">
                                {{ $tracking['rider']['vehicle'] }}
                                @if ($tracking['delivery_company'])
                                    · {{ $tracking['delivery_company']['name'] }}
                                @endif
                            </p>
                        </div>
                        @if ($tracking['rider']['rating'])
                            <span class="tnum text-sm font-semibold text-ink-200">
                                {{ number_format($tracking['rider']['rating'], 1) }}
                            </span>
                        @endif
                    </div>

                    @if ($tracking['rider_position'])
                        <div class="mt-3 flex items-center gap-2 rounded-lg bg-white/5 px-3 py-2">
                            <x-ui.icon name="navigation" class="size-4 shrink-0 text-ember-400" />
                            <p class="text-xs text-ink-200">
                                {{ __('app.tracking.last_updated') }}
                                {{ \Illuminate\Support\Carbon::parse($tracking['rider_position']['recorded_at'])->diffForHumans() }}
                            </p>
                        </div>
                    @endif
                </x-ui.card>
            @endif


            {{-- Reporting a problem.

                 Deliberately the last thing on the page and deliberately
                 quiet: most people reading this are simply waiting, and a
                 loud complaint button teaches them to expect trouble. It has
                 to be findable the moment something goes wrong, and invisible
                 until then.

                 It is also the only write path on the site a stranger can
                 reach with no account, so what it accepts is a fixed list and
                 a short note — nothing here decides anything about the
                 parcel itself. --}}
            @if ($tracking['issues'] !== [])
                <x-ui.card>
                    <p class="flex items-center gap-2 text-sm font-bold text-white">
                        <x-ui.icon name="alert" class="size-4 shrink-0 text-warn-400" />
                        {{ $justReported ? __('tracking.issue.received_title') : __('tracking.issue.panel_title') }}
                    </p>

                    <p class="mt-1 text-xs leading-relaxed text-ink-400">
                        {{ __('tracking.issue.received_body') }}
                    </p>

                    <ul class="mt-3 space-y-2">
                        @foreach ($tracking['issues'] as $issue)
                            <li class="flex items-start justify-between gap-3 rounded-lg bg-white/[0.04]
                                       px-3 py-2.5 ring-1 ring-inset ring-white/10">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white">{{ $issue['label'] }}</p>
                                    <p class="tnum mt-0.5 text-2xs text-ink-400">
                                        {{ __('tracking.issue.reported_at', [
                                            'time' => \Illuminate\Support\Carbon::parse($issue['reported_at'])->shortTime(),
                                        ]) }}
                                    </p>
                                </div>

                                <x-ui.badge :tone="$issue['tone']" class="shrink-0">
                                    {{ $issue['status_label'] }}
                                </x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.card>
            @endif

            @if ($tracking['can_report'])
                @if (! $reporting)
                    <button type="button" wire:click="startReport"
                            class="touch-target flex w-full items-center justify-center gap-2 rounded-card
                                   border border-white/10 bg-white/[0.03] px-4 py-3.5 text-sm font-semibold
                                   text-ink-300 transition hover:border-white/20 hover:bg-white/[0.06]
                                   hover:text-white">
                        <x-ui.icon name="alert" class="size-4 shrink-0" />
                        {{ $tracking['issues'] === []
                            ? __('tracking.issue.trigger')
                            : __('tracking.issue.report_another') }}
                    </button>
                @else
                    <x-ui.card class="border-warn-500/30">
                        <p class="text-sm font-bold text-white">{{ __('tracking.issue.title') }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-ink-400">
                            {{ __('tracking.issue.subtitle') }}
                        </p>

                        <form wire:submit="submitReport" class="mt-4">
                            {{-- Real radios: the choice survives a dropped
                                 connection, needs no round trip per tap, and
                                 a screen reader announces it as the single
                                 choice it is. --}}
                            <fieldset>
                                <legend class="sr-only">{{ __('tracking.issue.title') }}</legend>

                                <div class="space-y-1.5">
                                    @foreach ($this->issueCategories as $category)
                                        <label class="block cursor-pointer">
                                            <input type="radio" class="peer sr-only"
                                                   wire:model="issueCategory"
                                                   name="issueCategory"
                                                   value="{{ $category->value }}">

                                            <span class="flex touch-target items-center gap-2.5 rounded-lg border
                                                         border-white/10 bg-white/[0.03] px-3 py-2.5 text-xs
                                                         font-medium text-ink-200 transition
                                                         hover:border-white/20
                                                         peer-checked:border-ember-500 peer-checked:bg-ember-500/10
                                                         peer-checked:font-semibold peer-checked:text-white
                                                         peer-checked:[&_.radio-ring]:border-ember-500
                                                         peer-checked:[&_.radio-dot]:opacity-100
                                                         peer-focus-visible:outline peer-focus-visible:outline-2
                                                         peer-focus-visible:outline-offset-2
                                                         peer-focus-visible:outline-ember-500">
                                                <span class="radio-ring flex size-4 shrink-0 items-center
                                                             justify-center rounded-full border border-white/25
                                                             transition">
                                                    <span class="radio-dot size-2 rounded-full bg-ember-500
                                                                 opacity-0 transition"></span>
                                                </span>
                                                {{ $category->label() }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            @error('issueCategory')
                                <p class="field-error mt-2">{{ $message }}</p>
                            @enderror

                            <div class="mt-3">
                                <label for="issue-note" class="field-label">
                                    {{ __('tracking.issue.note_label') }}
                                </label>
                                <textarea id="issue-note" wire:model="issueNote" rows="3" maxlength="500"
                                          placeholder="{{ __('tracking.issue.note_placeholder') }}"
                                          class="field-input"></textarea>
                                @error('issueNote')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4 flex gap-2">
                                <x-ui.button type="submit" class="flex-1" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="submitReport">
                                        {{ __('tracking.issue.submit') }}
                                    </span>
                                    <span wire:loading wire:target="submitReport">
                                        {{ __('tracking.issue.submit') }}…
                                    </span>
                                </x-ui.button>

                                <x-ui.button type="button" variant="ghost" wire:click="cancelReport">
                                    {{ __('tracking.issue.cancel') }}
                                </x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                @endif
            @endif

        </main>
    @endif

    <footer class="safe-bottom px-5 pt-2 text-center">
        <p class="text-2xs text-ink-400">
            {{ __('app.tracking.powered_by', ['name' => __('app.name')]) }}
        </p>
    </footer>
</div>
