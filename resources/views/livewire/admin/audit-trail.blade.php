<div>
    <x-ui.page-header :title="__('app.nav.audit')"
                      :subtitle="__('app.common.showing', ['count' => $entries->count(), 'total' => $entries->total()])" />

    <x-ui.card class="mb-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-ui.field :label="__('app.common.search')">
                <input type="search" wire:model.live.debounce.400ms="search" class="field-input">
            </x-ui.field>
            <x-ui.field :label="__('app.common.actions')">
                <select wire:model.live="action" class="field-input">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach ($actions as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </x-ui.field>
            <x-ui.field :label="__('app.common.from')">
                <input type="date" wire:model.live="from" class="field-input tnum">
            </x-ui.field>
        </div>
    </x-ui.card>

    <x-ui.card flush>
        @if ($entries->isEmpty())
            <x-ui.empty icon="shield" :title="__('app.common.empty')" />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.common.date') }}</th>
                            <th>{{ __('app.common.actions') }}</th>
                            <th>{{ __('app.nav.team') }}</th>
                            <th>{{ __('app.common.details') }}</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr wire:key="{{ $entry->id }}" class="cursor-pointer"
                                wire:click="toggle('{{ $entry->id }}')">
                                <td class="tnum whitespace-nowrap text-ink-400">
                                    {{ $entry->created_at->translatedFormat('d M g:i:s A') }}
                                </td>
                                <td><x-ui.badge>{{ $entry->action->label() }}</x-ui.badge></td>
                                <td class="text-ink-100">
                                    {{ $entry->actor_label ?? $entry->actor_type }}
                                    <p class="text-2xs text-ink-400">{{ $entry->actor_type }}</p>
                                </td>
                                <td class="max-w-md truncate text-ink-300">
                                    {{ $entry->description ?? class_basename($entry->entity_type ?? '') }}
                                </td>
                                <td class="tnum text-2xs text-ink-400" dir="ltr">{{ $entry->ip_address }}</td>
                            </tr>

                            @if ($expanded === $entry->id)
                                <tr class="bg-white/[0.03]">
                                    <td colspan="5" class="px-4 py-3">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            @if ($entry->old_values)
                                                <div>
                                                    <p class="mb-1 text-2xs font-semibold uppercase text-ink-400">
                                                        {{ __('app.common.updated') }} —
                                                    </p>
                                                    <pre class="overflow-x-auto rounded bg-white p-2 font-mono text-2xs
                                                                text-ink-200 ring-1 ring-white/10" dir="ltr">{{ json_encode($entry->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                            @if ($entry->new_values)
                                                <div>
                                                    <p class="mb-1 text-2xs font-semibold uppercase text-ink-400">
                                                        {{ __('app.common.updated') }} +
                                                    </p>
                                                    <pre class="overflow-x-auto rounded bg-white p-2 font-mono text-2xs
                                                                text-ink-200 ring-1 ring-white/10" dir="ltr">{{ json_encode($entry->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                            @if ($entry->context)
                                                <div class="sm:col-span-2">
                                                    <pre class="overflow-x-auto rounded bg-white p-2 font-mono text-2xs
                                                                text-ink-200 ring-1 ring-white/10" dir="ltr">{{ json_encode($entry->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($entries->hasPages())
                <div class="border-t border-white/10 px-4 py-3">{{ $entries->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</div>
