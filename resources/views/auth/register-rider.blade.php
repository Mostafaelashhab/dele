{{--
    Independent rider registration.

    The only door that asks for identity documents, because it is the only one
    where nobody is vouching for the person. That is said plainly on the form
    rather than left to be discovered.
--}}
<x-layouts.guest :title="__('app.auth.register_rider')" audience="company">
    <a href="{{ route('register') }}"
       class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-500
              transition hover:text-ink-800">
        <x-ui.icon name="chevron-end" class="size-3.5 ltr:rotate-180 rtl:rotate-0" />
        {{ __('app.auth.back_to_choice') }}
    </a>

    <div class="mt-5 flex items-start gap-3.5">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl
                     bg-emerald-100 text-emerald-700">
            <x-ui.icon name="motorcycle" class="size-6" />
        </span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold tracking-tight text-ink-950">
                {{ __('app.auth.register_rider') }}
            </h1>
            <p class="mt-1 text-sm leading-relaxed text-ink-500">
                {{ __('marketing.choose.rider_body') }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('register.rider') }}" enctype="multipart/form-data"
          class="mt-7 space-y-7">
        @csrf

        <x-ui.fieldset :legend="__('app.auth.group_contact')">
            <x-ui.field :label="__('app.auth.contact_name')" name="name" required>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       class="field-input" required autofocus>
            </x-ui.field>

            <x-ui.field :label="__('app.auth.phone')" name="phone" required>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                       class="field-input tnum" dir="ltr" inputmode="numeric"
                       placeholder="01xxxxxxxxx" required>
            </x-ui.field>
        </x-ui.fieldset>

        <x-ui.fieldset :legend="__('app.auth.vehicle')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('app.auth.vehicle')" name="vehicle_type" required>
                    <select id="vehicle_type" name="vehicle_type" class="field-input" required>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->value }}" @selected(old('vehicle_type') === $vehicle->value)>
                                {{ $vehicle->label() }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.field>

                <x-ui.field :label="__('app.auth.vehicle_identifier')" name="vehicle_identifier"
                            :hint="__('app.auth.vehicle_identifier_hint')">
                    <input type="text" id="vehicle_identifier" name="vehicle_identifier"
                           value="{{ old('vehicle_identifier') }}" class="field-input">
                </x-ui.field>
            </div>

            <x-ui.field :label="__('app.auth.coverage_zones')" name="zone_ids"
                        :hint="__('app.auth.coverage_zones_hint')" required>
                <div class="max-h-44 space-y-0.5 overflow-y-auto rounded-lg border border-ink-300 bg-white p-2">
                    @foreach ($zones as $zone)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-md px-2.5 py-2
                                      text-sm text-ink-800 transition hover:bg-ink-50 has-checked:bg-signal-50">
                            <input type="checkbox" name="zone_ids[]" value="{{ $zone->id }}"
                                   class="size-4 shrink-0 rounded border-ink-300 text-signal-600 focus:ring-signal-500"
                                   @checked(in_array($zone->id, (array) old('zone_ids', []), true))>
                            <span class="flex-1 truncate">{{ $zone->displayName() }}</span>
                        </label>
                    @endforeach
                </div>
            </x-ui.field>
        </x-ui.fieldset>

        <x-ui.fieldset :legend="__('app.auth.group_identity')">
            <p class="flex items-start gap-2.5 rounded-lg border border-signal-200 bg-signal-50 p-3.5
                      text-xs leading-relaxed text-signal-900">
                <x-ui.icon name="shield" class="mt-0.5 size-4 shrink-0" />
                {{ __('app.auth.identity_hint') }}
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.field :label="__('app.auth.id_card_front')" name="id_card_front" required>
                    <input type="file" id="id_card_front" name="id_card_front"
                           accept="image/*" capture="environment" class="field-input" required>
                </x-ui.field>

                <x-ui.field :label="__('app.auth.id_card_back')" name="id_card_back" required>
                    <input type="file" id="id_card_back" name="id_card_back"
                           accept="image/*" capture="environment" class="field-input" required>
                </x-ui.field>
            </div>

            <x-ui.field :label="__('app.auth.face_photo')" name="face_photo" required>
                <input type="file" id="face_photo" name="face_photo"
                       accept="image/*" capture="user" class="field-input" required>
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

        <p class="flex items-start gap-2.5 rounded-lg border border-warn-200 bg-warn-50 p-3.5
                  text-xs leading-relaxed text-warn-900">
            <x-ui.icon name="clock" class="mt-0.5 size-4 shrink-0" />
            {{ __('app.auth.rider_pending_body') }}
        </p>

        <x-ui.button type="submit" size="lg" class="w-full">
            {{ __('app.auth.register_rider') }}
        </x-ui.button>
    </form>

    <p class="mt-7 border-t border-ink-200 pt-6 text-center text-sm text-ink-500">
        {{ __('app.auth.have_account') }}
        <a href="{{ route('login') }}" class="font-semibold text-signal-700 hover:underline">
            {{ __('app.auth.login') }}
        </a>
    </p>
</x-layouts.guest>
