<div x-data="{ toast: null }" @toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 4000)">
    <x-ui.page-header :title="__('app.nav.team')" :subtitle="$this->tenantLabel()">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="$set('inviting', true)">{{ __('app.common.create') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card flush>
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('app.common.name') }}</th>
                    <th>{{ __('app.common.email') }}</th>
                    <th>{{ __('form.team_role') }}</th>
                    <th class="text-center">{{ __('app.common.status') }}</th>
                    <th class="text-end">{{ __('app.common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->members as $member)
                    <tr wire:key="{{ $member->id }}">
                        <td class="font-medium text-white">
                            {{ $member->user?->name }}
                            @if ($member->is_primary_contact)
                                <x-ui.badge tone="blue" class="ms-1">{{ __('app.common.active') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="text-ink-300" dir="ltr">{{ $member->user?->email }}</td>
                        <td class="text-ink-200">{{ __('account.role.'.$member->role) }}</td>
                        <td class="text-center">
                            <x-ui.badge :tone="$member->is_active ? 'green' : 'slate'" dot>
                                {{ $member->is_active ? __('app.common.active') : __('app.common.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="text-end">
                            <x-ui.button variant="ghost" size="sm" wire:click="toggleActive({{ $member->id }})">
                                {{ $member->is_active ? __('audit.action.suspended') : __('audit.action.reinstated') }}
                            </x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-ui.card>

    @if ($inviting)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/50 p-4"
             wire:click.self="$set('inviting', false)">
            <div class="w-full max-w-md rounded-card bg-white p-5 shadow-xl">
                <h2 class="text-sm font-semibold text-white">{{ __('app.nav.team') }}</h2>
                <form wire:submit="save" class="mt-4 space-y-4">
                    <x-ui.field :label="__('app.common.name')" name="name" required>
                        <input type="text" wire:model="name" class="field-input">
                    </x-ui.field>
                    <x-ui.field :label="__('app.common.email')" name="email" required>
                        <input type="email" wire:model="email" class="field-input" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('app.common.phone')" name="phone" required>
                        <input type="tel" wire:model="phone" class="field-input tnum" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('app.auth.password')" name="password" required>
                        <input type="text" wire:model="password" class="field-input" dir="ltr">
                    </x-ui.field>
                    <x-ui.field :label="__('form.team_role')" name="role">
                        <select wire:model="role" class="field-input">
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                    <div class="flex gap-2">
                        <x-ui.button type="submit" class="flex-1">{{ __('app.common.save') }}</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="$set('inviting', false)">
                            {{ __('app.common.cancel') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div x-show="toast" x-cloak
         class="fixed bottom-5 z-50 rounded-md bg-red-700 px-4 py-3 text-sm text-white shadow-lg ltr:right-5 rtl:left-5">
        <span x-text="toast"></span>
    </div>
</div>
