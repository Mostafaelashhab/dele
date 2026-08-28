{{--
    The door chooser.

    Four people, four doors. It renders straight onto the dark ground rather
    than inside a lit panel: a panel is for a form you fill in, and this is a
    decision you make before there is anything to fill in.

    The individual door was missing here entirely — the page offered three
    doors while the product had four, so the person the landing page speaks to
    first had nowhere to go from this screen.
--}}
<x-layouts.guest :title="__('app.auth.register')" audience="both" wide bare>
    <div class="text-center">
        <p class="text-xs font-bold uppercase tracking-widest text-ember-400">
            {{ __('marketing.choose.eyebrow') }}
        </p>
        <h1 class="mx-auto mt-3 max-w-2xl text-3xl font-bold leading-tight tracking-tight
                   text-white sm:text-4xl">
            {{ __('marketing.choose.title') }}
        </h1>
        <p class="mx-auto mt-4 max-w-lg text-base leading-relaxed text-ink-300">
            {{ __('marketing.choose.body') }}
        </p>
    </div>

    <div class="mt-10 grid gap-4 sm:grid-cols-2">
        @foreach ([
            ['key' => 'individual', 'icon' => 'user', 'route' => 'register.individual', 'accent' => 'signal'],
            ['key' => 'business', 'icon' => 'store', 'route' => 'register.business', 'accent' => 'signal'],
            ['key' => 'company', 'icon' => 'truck', 'route' => 'register.company', 'accent' => 'ember'],
            ['key' => 'rider', 'icon' => 'motorcycle', 'route' => 'register.rider', 'accent' => 'emerald'],
        ] as $door)
            @php
                $tone = [
                    'signal' => [
                        'chip' => 'border-signal-500/30 bg-signal-500/10 text-signal-300',
                        'edge' => 'hover:border-signal-500/50',
                        'button' => 'bg-signal-600 group-hover:bg-signal-700',
                        'check' => 'text-signal-400',
                    ],
                    'ember' => [
                        'chip' => 'border-ember-500/30 bg-ember-500/10 text-ember-400',
                        'edge' => 'hover:border-ember-500/50',
                        'button' => 'bg-ember-500 group-hover:bg-ember-600',
                        'check' => 'text-ember-400',
                    ],
                    'emerald' => [
                        'chip' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                        'edge' => 'hover:border-emerald-500/50',
                        'button' => 'bg-emerald-600 group-hover:bg-emerald-700',
                        'check' => 'text-emerald-400',
                    ],
                ][$door['accent']];
            @endphp

            {{-- The whole card is the target: at this size the card is the
                 decision, not the button inside it. --}}
            <a href="{{ route($door['route']) }}"
               class="panel group flex flex-col p-6 transition hover:bg-white/[0.055] {{ $tone['edge'] }}">
                <span class="flex size-12 items-center justify-center rounded-xl border {{ $tone['chip'] }}">
                    <x-ui.icon :name="$door['icon']" class="size-6" />
                </span>

                <h2 class="mt-5 text-lg font-bold text-white">
                    {{ __('marketing.choose.'.$door['key'].'_title') }}
                </h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-400">
                    {{ __('marketing.choose.'.$door['key'].'_body') }}
                </p>

                <ul class="mt-5 flex-1 space-y-2.5 border-t border-white/10 pt-5">
                    @foreach (__('marketing.choose.'.$door['key'].'_points') as $point)
                        <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-300">
                            <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 {{ $tone['check'] }}" />
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>

                <span class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg
                             px-6 py-3.5 text-sm font-bold text-white transition {{ $tone['button'] }}">
                    {{ __('marketing.choose.'.$door['key'].'_cta') }}
                    <x-ui.icon name="chevron-end"
                               class="size-4 transition group-hover:translate-x-0.5 rtl:rotate-180
                                      rtl:group-hover:-translate-x-0.5" />
                </span>

                {{-- Whether the account works immediately or waits for review
                     is said before signing up, not discovered after. --}}
                <span class="mt-2.5 block text-center text-xs leading-relaxed text-ink-400">
                    {{ __('marketing.choose.'.$door['key'].'_note') }}
                </span>
            </a>
        @endforeach
    </div>

    <p class="mt-8 text-center text-sm leading-relaxed text-ink-400">
        {{ __('marketing.choose.closer') }}
    </p>

    <p class="mt-6 border-t border-white/10 pt-6 text-center text-sm text-ink-400">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-white hover:underline">
            {{ __('app.auth.login') }}
        </a>
    </p>
</x-layouts.guest>
