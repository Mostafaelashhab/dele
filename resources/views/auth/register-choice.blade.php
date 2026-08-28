{{--
    The door chooser.

    This page exists because the two audiences were previously funnelled into
    one form: the public pages pitch delivery companies hard, and the CTA used
    to land them on a shop-registration form asking for their business
    category. Splitting the door is the fix; the copy is split with it,
    because a shop is buying the end of phone calls and a courier fleet is
    buying access to order flow.

    It runs wide, so it opts out of the layout's pitch column and carries its
    own argument in the two cards.
--}}
<x-layouts.guest :title="__('app.auth.register')" audience="both" wide>
    <div class="text-center">
        <p class="text-xs font-bold uppercase tracking-widest text-signal-700">
            {{ __('marketing.choose.eyebrow') }}
        </p>
        <h1 class="mt-3 text-2xl font-bold tracking-tight text-ink-950 sm:text-3xl">
            {{ __('marketing.choose.title') }}
        </h1>
        <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-ink-500">
            {{ __('marketing.choose.body') }}
        </p>
    </div>

    <div class="mt-8 grid gap-4 sm:mt-10 lg:grid-cols-2">
        @foreach ([
            [
                'key' => 'business',
                'icon' => 'store',
                'route' => 'register.business',
                'tone' => [
                    'card' => 'border-signal-200 hover:border-signal-400',
                    'chip' => 'bg-signal-100 text-signal-700',
                    'button' => 'bg-signal-600 hover:bg-signal-700 shadow-signal-600/20',
                    'check' => 'text-signal-600',
                ],
            ],
            [
                'key' => 'company',
                'icon' => 'truck',
                'route' => 'register.company',
                'tone' => [
                    'card' => 'border-ember-200 hover:border-ember-400',
                    'chip' => 'bg-ember-100 text-ember-700',
                    'button' => 'bg-ember-500 hover:bg-ember-600 shadow-ember-500/25',
                    'check' => 'text-ember-600',
                ],
            ],
        ] as $door)
            {{-- The whole card is the target, not just the button: at this
                 size the card is the decision. --}}
            <a href="{{ route($door['route']) }}"
               class="group flex flex-col rounded-2xl border-2 bg-white p-6 transition
                      hover:shadow-lg {{ $door['tone']['card'] }}">
                <span class="flex size-12 items-center justify-center rounded-xl {{ $door['tone']['chip'] }}">
                    <x-ui.icon :name="$door['icon']" class="size-6" />
                </span>

                <h2 class="mt-4 text-lg font-bold text-ink-950">
                    {{ __('marketing.choose.'.$door['key'].'_title') }}
                </h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">
                    {{ __('marketing.choose.'.$door['key'].'_body') }}
                </p>

                <ul class="mt-5 flex-1 space-y-2.5">
                    @foreach (__('marketing.choose.'.$door['key'].'_points') as $point)
                        <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-700">
                            <x-ui.icon name="check"
                                       class="mt-0.5 size-4 shrink-0 {{ $door['tone']['check'] }}" />
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>

                <span class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg
                             px-6 py-3.5 text-sm font-bold text-white shadow-lg transition
                             {{ $door['tone']['button'] }}">
                    {{ __('marketing.choose.'.$door['key'].'_cta') }}
                    <x-ui.icon name="chevron-end"
                               class="size-4 transition group-hover:translate-x-0.5 rtl:rotate-180
                                      rtl:group-hover:-translate-x-0.5" />
                </span>

                {{-- The activation difference between the two doors is stated
                     up front rather than discovered after signing up. --}}
                <span class="mt-2.5 block text-center text-xs leading-relaxed text-ink-500">
                    {{ __('marketing.choose.'.$door['key'].'_note') }}
                </span>
            </a>
        @endforeach
    </div>

    <p class="mt-7 text-center text-xs leading-relaxed text-ink-500">
        {{ __('marketing.choose.closer') }}
    </p>

    <p class="mt-6 border-t border-ink-200 pt-6 text-center text-sm text-ink-500">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-signal-700 hover:underline">
            {{ __('app.auth.login') }}
        </a>
    </p>
</x-layouts.guest>
