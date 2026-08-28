{{-- The public footer, shared by every page outside the portals. --}}
<footer class="border-t border-white/10 bg-ink-950">
    <div class="mx-auto max-w-6xl px-5 py-14">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-ui.logo wordmark class="text-white" />
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-400">
                    {{ __('marketing.hero_body') }}
                </p>
            </div>

            @foreach (__('marketing.footer.columns') as $column)
                <div>
                    <p class="text-sm font-bold text-white">{{ $column['title'] }}</p>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($column['links'] as $link)
                            <li>
                                <span class="text-sm text-ink-400">{{ $link }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div>
                <p class="text-sm font-bold text-white">{{ __('marketing.footer.contact') }}</p>
                <ul class="mt-4 space-y-3">
                    <li class="flex items-center gap-2.5 text-sm text-ink-400">
                        <x-ui.icon name="pin" class="size-4 shrink-0 text-ink-500" />
                        {{ __('app.city') }}، {{ __('marketing.footer.governorate') }}
                    </li>
                    <li class="flex items-center gap-2.5 text-sm text-ink-400">
                        <x-ui.icon name="money" class="size-4 shrink-0 text-ink-500" />
                        {{ config('platform.currency.code') }}
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-6">
            <p class="text-xs text-ink-400">
                © {{ now()->year }} {{ __('app.name') }}. {{ __('marketing.footer.rights') }}
            </p>
            <nav class="flex gap-5 text-xs text-ink-400">
                <a href="{{ route('login') }}" class="transition hover:text-white">
                    {{ __('marketing.cta_login') }}
                </a>
                <a href="{{ route('register') }}" class="transition hover:text-white">
                    {{ __('app.auth.register') }}
                </a>
            </nav>
        </div>
    </div>
</footer>
