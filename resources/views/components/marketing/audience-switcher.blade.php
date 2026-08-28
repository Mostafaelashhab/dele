@props(['audiences'])

@php
    /**
     * The page's spine: four people, four answers, one surface.
     *
     * A shop, a courier fleet, a rider working alone and somebody posting a
     * parcel are not looking for the same sentence. Rather than stacking four
     * sections and hoping each reader scrolls to theirs, the page asks who
     * they are and answers only that.
     *
     * Every panel is rendered server-side and hidden with Alpine rather than
     * fetched, so switching is instant, the whole thing survives with
     * JavaScript disabled, and a crawler sees all four.
     */
    $accents = [
        'signal' => [
            'tab' => 'border-signal-500 bg-signal-500/10 text-white',
            'chip' => 'border-signal-500/30 bg-signal-500/10 text-signal-300',
            'button' => 'bg-signal-600 hover:bg-signal-700 shadow-signal-600/25',
            'check' => 'text-signal-400',
            'glow' => 'rgb(51 102 242 / 18%)',
        ],
        'ember' => [
            'tab' => 'border-ember-500 bg-ember-500/10 text-white',
            'chip' => 'border-ember-500/30 bg-ember-500/10 text-ember-400',
            'button' => 'bg-ember-500 hover:bg-ember-600 shadow-ember-500/25',
            'check' => 'text-ember-400',
            'glow' => 'rgb(249 92 19 / 18%)',
        ],
        'emerald' => [
            'tab' => 'border-emerald-500 bg-emerald-500/10 text-white',
            'chip' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
            'button' => 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/25',
            'check' => 'text-emerald-400',
            'glow' => 'rgb(16 185 129 / 18%)',
        ],
    ];

    $first = $audiences[0]['key'];
@endphp

<div x-data="{ who: @js($first) }" {{ $attributes }}>

    {{-- The choice. A real tablist, so it works from the keyboard and reads
         correctly to a screen reader. --}}
    <div role="tablist" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($audiences as $audience)
            @php $tone = $accents[$audience['accent']]; @endphp

            <button type="button"
                    role="tab"
                    :aria-selected="who === @js($audience['key'])"
                    :tabindex="who === @js($audience['key']) ? 0 : -1"
                    @click="who = @js($audience['key'])"
                    @keydown.right.prevent="$el.nextElementSibling?.focus()"
                    @keydown.left.prevent="$el.previousElementSibling?.focus()"
                    :class="who === @js($audience['key'])
                        ? @js($tone['tab'])
                        : 'border-white/10 bg-white/[0.02] text-ink-400 hover:border-white/20 hover:text-white'"
                    class="group flex items-center gap-3 rounded-xl border p-4 text-start transition">
                <span :class="who === @js($audience['key']) ? @js($tone['chip']) : 'border-white/10 bg-white/5 text-ink-400'"
                      class="flex size-10 shrink-0 items-center justify-center rounded-lg border transition">
                    <x-ui.icon :name="$audience['icon']" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold">
                        {{ __('marketing.who.'.$audience['key'].'_tab') }}
                    </span>
                    <span class="mt-0.5 block truncate text-xs text-ink-500">
                        {{ __('marketing.who.'.$audience['key'].'_line') }}
                    </span>
                </span>
            </button>
        @endforeach
    </div>

    {{-- The answer. --}}
    @foreach ($audiences as $audience)
        @php $tone = $accents[$audience['accent']]; @endphp

        <div x-show="who === @js($audience['key'])"
             x-cloak
             x-transition:enter="transition duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             role="tabpanel"
             class="relative mt-4 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02]">

            {{-- One pool of the panel's own accent, so switching tab visibly
                 changes the temperature of the section. --}}
            <span class="pointer-events-none absolute inset-x-0 top-0 h-64"
                  style="background: radial-gradient(50% 60% at 50% 0%, {{ $tone['glow'] }}, transparent 70%)"
                  aria-hidden="true"></span>

            <div class="relative grid gap-10 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <h3 class="text-2xl font-bold leading-tight tracking-tight text-white lg:text-3xl">
                        {{ __('marketing.choose.'.$audience['key'].'_title') }}
                    </h3>
                    <p class="mt-3 max-w-xl text-base leading-relaxed text-ink-300">
                        {{ __('marketing.choose.'.$audience['key'].'_body') }}
                    </p>

                    <ul class="mt-6 grid gap-2.5 sm:grid-cols-2">
                        @foreach (__('marketing.choose.'.$audience['key'].'_points') as $point)
                            <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-300">
                                <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 {{ $tone['check'] }}" />
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2">
                        <a href="{{ route($audience['route']) }}"
                           class="inline-flex items-center gap-2 rounded-lg px-6 py-3.5 text-sm
                                  font-bold text-white shadow-lg transition {{ $tone['button'] }}">
                            {{ __('marketing.choose.'.$audience['key'].'_cta') }}
                            <x-ui.icon name="chevron-end" class="size-4 rtl:rotate-180" />
                        </a>
                        {{-- The landing argues; the guide explains. Somebody
                             who wants the detail before committing should not
                             have to sign up to find it. --}}
                        <a href="{{ route('learn.show', $audience['key']) }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-white/15
                                  px-5 py-3.5 text-sm font-semibold text-white transition
                                  hover:border-white/30 hover:bg-white/5">
                            {{ __('learn.hub.cta') }}
                        </a>

                        <span class="w-full text-xs leading-relaxed text-ink-400 sm:w-auto">
                            {{ __('marketing.choose.'.$audience['key'].'_note') }}
                        </span>
                    </div>
                </div>

                {{-- The screen this person would actually be looking at. --}}
                <div class="justify-self-center">
                    @if ($audience['screen'] === 'tracking')
                        <x-marketing.phone-frame :width="248">
                            <x-marketing.mock-tracking />
                        </x-marketing.phone-frame>
                    @elseif ($audience['screen'] === 'rider')
                        <x-marketing.phone-frame :width="248">
                            <x-marketing.mock-rider />
                        </x-marketing.phone-frame>
                    @else
                        <div class="w-full lg:w-[26rem]">
                            <x-marketing.browser-frame
                                :label="$audience['screen'] === 'order'
                                    ? 'banha.shop/app/orders/create'
                                    : 'banha.shop/company/offers'">
                                @if ($audience['screen'] === 'order')
                                    <x-marketing.mock-order-form />
                                @else
                                    <x-marketing.mock-dispatch />
                                @endif
                            </x-marketing.browser-frame>
                        </div>
                    @endif

                    <p class="mx-auto mt-3 max-w-[17rem] text-center text-xs leading-relaxed text-ink-400">
                        {{ __('marketing.who.'.$audience['key'].'_screen') }}
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>
