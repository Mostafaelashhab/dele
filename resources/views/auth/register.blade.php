@php $heading = $individual ? __('app.auth.register_individual') : __('app.auth.register_business'); @endphp

<x-layouts.guest :title="$heading" audience="business">
    <a href="{{ route('register') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500
              transition hover:text-ink-800">
        <x-ui.icon name="chevron-end" class="size-3.5 ltr:rotate-180 rtl:rotate-0" />
        {{ __('app.auth.back_to_choice') }}
    </a>

    <div class="mt-5 flex items-start gap-3.5">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl
                     bg-signal-100 text-signal-700">
            <x-ui.icon :name="$individual ? 'user' : 'store'" class="size-6" />
        </span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-ink-950">
                {{ $heading }}
            </h1>
            <p class="mt-1 text-sm leading-relaxed text-ink-500">
                {{ $individual ? __('marketing.choose.individual_body') : __('marketing.choose.business_body') }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route($individual ? 'register.individual' : 'register.business') }}" class="mt-7 space-y-7">
        @csrf

        {{-- A person sending a parcel has no trade name and no category. --}}
        @unless ($individual)
        <x-ui.fieldset :legend="__('app.auth.group_about_business')">
            <x-ui.field :label="__('app.auth.business_name')" name="business_name" required>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}"
                       class="field-input" required autofocus>
            </x-ui.field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('app.auth.category')" name="category" required>
                    <select id="category" name="category" class="field-input" required>
                        {{-- Labels come from business.category.* rather than being
                             repeated here, so the English locale reaches them too. --}}
                        @foreach (['restaurant', 'pharmacy', 'grocery', 'clothing', 'electronics', 'online', 'other'] as $value)
                            <option value="{{ $value }}" @selected(old('category') === $value)>
                                {{ __('business.category.'.$value) }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field :label="__('address.zone')" name="zone_id">
                    <select id="zone_id" name="zone_id" class="field-input">
                        <option value="">{{ __('app.common.none') }}</option>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(old('zone_id') === $zone->id)>
                                {{ $zone->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>
            </div>
        </x-ui.fieldset>
        @endunless

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

        <div>
            <x-ui.button type="submit" size="lg" class="w-full">
                {{ $heading }}
            </x-ui.button>
            <p class="mt-2.5 text-center text-xs leading-relaxed text-ink-500">
                {{ __('marketing.choose.business_note') }}
            </p>
        </div>
    </form>

    <p class="mt-7 border-t border-ink-200 pt-6 text-center text-sm text-ink-500">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-signal-700 hover:underline">
            {{ __('app.auth.login') }}
        </a>
    </p>
</x-layouts.guest>
