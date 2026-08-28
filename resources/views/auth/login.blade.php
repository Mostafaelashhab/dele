{{-- Login is the one auth page both audiences share, so its panel makes the
     case for the system rather than for either side of it. --}}
<x-layouts.guest :title="__('app.auth.login')" audience="both">
    <h1 class="text-2xl font-bold tracking-tight text-ink-950">{{ __('app.auth.login_title') }}</h1>
    <p class="mt-1.5 text-sm leading-relaxed text-ink-500">{{ __('app.tagline') }}</p>

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-4">
        @csrf

        <x-ui.field :label="__('app.auth.email')" name="email" required>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="field-input" dir="ltr" required autofocus autocomplete="username">
        </x-ui.field>

        <x-ui.field :label="__('app.auth.password')" name="password" required>
            <input type="password" id="password" name="password"
                   class="field-input" dir="ltr" required autocomplete="current-password">
        </x-ui.field>

        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="remember"
                   class="size-4 rounded border-ink-300 text-signal-600 focus:ring-signal-500">
            {{ __('app.auth.remember') }}
        </label>

        <x-ui.button type="submit" size="lg" class="w-full">{{ __('app.auth.login') }}</x-ui.button>
    </form>

    {{-- Both doors, because a courier fleet is as likely to arrive here as a
         shop and the old copy sent everyone to shop registration. --}}
    <div class="mt-7 border-t border-ink-200 pt-6">
        <p class="text-center text-sm text-ink-500">
            {{ __('app.auth.no_account') }}
        </p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <a href="{{ route('register.business') }}"
               class="rounded-lg border border-ink-300 px-4 py-2.5 text-center text-sm font-semibold
                      text-ink-800 transition hover:border-ink-400 hover:bg-ink-50">
                {{ __('marketing.choose.business_cta') }}
            </a>
            <a href="{{ route('register.company') }}"
               class="rounded-lg border border-ink-300 px-4 py-2.5 text-center text-sm font-semibold
                      text-ink-800 transition hover:border-ink-400 hover:bg-ink-50">
                {{ __('marketing.choose.company_cta') }}
            </a>
        </div>
    </div>
</x-layouts.guest>
