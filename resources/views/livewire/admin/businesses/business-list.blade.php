<div>
    <x-ui.page-header :title="__('app.nav.businesses')"
                      :subtitle="__('app.common.showing', ['count' => $businesses->count(), 'total' => $businesses->total()])" />

    <x-ui.card class="mb-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-ui.field :label="__('app.common.search')" class="sm:col-span-2">
                <input type="search" wire:model.live.debounce.400ms="search" class="field-input">
            </x-ui.field>
            <x-ui.field :label="__('app.common.status')">
                <select wire:model.live="status" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card flush>
        @if ($businesses->isEmpty())
            <x-ui.empty icon="store" :title="__('app.common.empty')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.name') }}</th>
                            <th>{{ __('app.auth.category') }}</th>
                            <th>{{ __('app.common.phone') }}</th>
                            <th class="text-end">{{ __('app.nav.deliveries') }}</th>
                            <th>{{ __('app.common.status') }}</th>
                            <th class="text-end">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($businesses as $business)
                            <tr wire:key="{{ $business->id }}">
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <x-ui.avatar
                                            :src="$business->mediaUrl('logo_path')"
                                            :name="$business->displayName()"
                                            :icon="$business->hasMedia('logo_path') ? null : $business->categoryIcon()"
                                            size="sm" square />
                                        <a href="{{ route('admin.businesses.show', $business->id) }}" wire:navigate
                                           class="font-medium text-signal-700 hover:underline">
                                            {{ $business->displayName() }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-ink-300">
                                        <x-ui.icon :name="$business->categoryIcon()" class="size-3.5 text-ink-400" />
                                        {{ $business->categoryLabel() }}
                                    </span>
                                </td>
                                <td class="tnum text-ink-300" dir="ltr">{{ $business->phone }}</td>
                                <td class="tnum text-end">{{ $business->deliveries_count }}</td>
                                <td>
                                    <x-ui.badge :tone="$business->status->tone()" dot>
                                        {{ $business->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="text-end">
                                    <x-ui.button variant="ghost" size="sm"
                                                 wire:click="toggleStatus('{{ $business->id }}')"
                                                 wire:confirm="{{ __('app.common.confirm') }}">
                                        {{ $business->status->value === 'active'
                                            ? __('audit.action.suspended')
                                            : __('audit.action.reinstated') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($businesses->hasPages())
                <div class="border-t border-white/10 px-4 py-3">{{ $businesses->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
