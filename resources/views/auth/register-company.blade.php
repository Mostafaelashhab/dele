{{--
    Delivery company self-registration.

    The one thing this form must be honest about is that signing up is not the
    same as being in dispatch: the account starts Pending and receives nothing
    until the platform approves it. That is said before the submit button, not
    after it.
--}}
<x-layouts.guest :title="__('app.auth.register_company')" audience="company">
    <a href="{{ route('register') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500
              transition hover:text-ink-800">
        <x-ui.icon name="chevron-end" class="size-3.5 ltr:rotate-180 rtl:rotate-0" />
        {{ __('app.auth.back_to_choice') }}
    </a>

    <div class="mt-5 flex items-start gap-3.5">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl
                     bg-ember-100 text-ember-700">
            <x-ui.icon name="truck" class="size-6" />
        </span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-ink-950">
                {{ __('app.auth.register_company') }}
            </h1>
            <p class="mt-1 text-sm leading-relaxed text-ink-500">
                {{ __('marketing.choose.company_body') }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('register.company') }}" class="mt-7 space-y-7">
        @csrf

        <x-ui.fieldset :legend="__('app.auth.group_about_company')">
            <x-ui.field :label="__('app.auth.company_name')" name="company_name" required>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}"
                       class="field-input" required autofocus>
            </x-ui.field>

            <x-ui.field :label="__('app.auth.fleet_size')" name="fleet_size"
                        :hint="__('app.auth.fleet_size_hint')" required>
                <input type="number" id="fleet_size" name="fleet_size" value="{{ old('fleet_size', 5) }}"
                       class="field-input tnum" dir="ltr" inputmode="numeric" min="1" max="500" required>
            </x-ui.field>

            {{-- Coverage decides whether this company is ever a candidate for
                 an order at all, so it is required, explained, and shows a
                 running count rather than leaving the reader to check. --}}
            <x-ui.field :label="__('app.auth.coverage_zones')" name="zone_ids"
                        :hint="__('app.auth.coverage_zones_hint')" required>
                <div x-data="{ picked: {{ count((array) old('zone_ids', [])) }} }"
                     class="overflow-hidden rounded-lg border border-ink-300 bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-ink-200
                                bg-ink-50 px-3 py-2">
                        <span class="text-xs font-semibold text-ink-600">
                            {{ __('marketing.zones.total') }}
                        </span>
                        <span class="tnum rounded-full bg-white px-2 py-0.5 text-xs font-bold
                                     text-ink-800 ring-1 ring-ink-200"
                              x-text="picked"></span>
                    </div>

                    <div class="max-h-52 space-y-0.5 overflow-y-auto p-2">
                        @foreach ($zones as $zone)
                            @php $checked = in_array($zone->id, (array) old('zone_ids', []), true); @endphp
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2
                                          text-sm text-ink-800 transition hover:bg-ink-50
                                          has-checked:bg-signal-50">
                                <input type="checkbox" name="zone_ids[]" value="{{ $zone->id }}"
                                       @change="picked += $event.target.checked ? 1 : -1"
                                       class="size-4 shrink-0 rounded border-ink-300 text-signal-600
                                              focus:ring-signal-500"
                                       @checked($checked)>
                                <span class="flex-1 truncate">{{ $zone->displayName() }}</span>
                                <span class="tnum shrink-0 text-xs text-ink-400">
                                    {{ $zone->estimated_minutes }}{{ __('marketing.zones.minutes_short') }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </x-ui.field>
        </x-ui.fieldset>

        <x-ui.fieldset :legend="__('app.auth.group_contact')">
            <x-ui.field :label="__('app.auth.contact_name')" name="contact_name" required>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}"
                       class="field-input" required>
            </x-ui.field>

            <x-ui.field :label="__('app.auth.phone')" name="phone" required>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                       class="field-input tnum" dir="ltr" inputmode="numeric"
                       placeholder="01xxxxxxxxx" required>
            </x-ui.field>
        </x-ui.fieldset>

        <x-ui.fieldset :legend="__('app.auth.group_credentials')">
            <x-ui.field :label="__('app.auth.email')" name="email" required>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="field-input" dir="ltr" required autocomplete="username">
            </x-ui.field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('app.auth.password')" name="password" required>
                    <input type="password" id="password" name="password"
                           class="field-input" dir="ltr" required autocomplete="new-password">
                </x-ui.field>
                <x-ui.field :label="__('app.auth.password_confirmation')" name="password_confirmation" required>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="field-input" dir="ltr" required autocomplete="new-password">
                </x-ui.field>
            </div>
        </x-ui.fieldset>

        <p class="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 p-3.5
                  text-xs leading-relaxed text-amber-900">
            <x-ui.icon name="shield" class="mt-0.5 size-4 shrink-0" />
            {{ __('marketing.choose.company_note') }}
        </p>

        <x-ui.button type="submit" size="lg" class="w-full">
            {{ __('app.auth.register_company') }}
        </x-ui.button>
    </form>

    <p class="mt-7 border-t border-ink-200 pt-6 text-center text-sm text-ink-500">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-signal-700 hover:underline">
            {{ __('app.auth.login') }}
        </a>
    </p>
</x-layouts.guest>
