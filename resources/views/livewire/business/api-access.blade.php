<div>
    <x-ui.page-header :title="__('app.nav.api')" :subtitle="$this->tenantLabel()" />

    @if ($revealedKey)
        {{-- Shown exactly once. The warning is the whole point of the panel. --}}
        <div class="mb-5 rounded-card border-2 border-amber-300 bg-amber-50 p-4">
            <div class="flex items-start gap-2.5">
                <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0 text-amber-700" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900">{{ __('app.nav.api') }}</p>
                    <p class="mt-1 text-xs text-amber-800">{{ __('form.webhook_secret_notice') }}</p>
                    <div x-data="{ copied: false }" class="mt-3 flex items-center gap-2">
                        <code class="min-w-0 flex-1 truncate rounded bg-white px-3 py-2 font-mono text-xs
                                     text-ink-900 ring-1 ring-amber-200" dir="ltr">{{ $revealedKey }}</code>
                        <x-ui.button variant="secondary" size="sm"
                                     x-on:click="copied = await window.copyToClipboard(@js($revealedKey))">
                            <span x-text="copied ? @js(__('app.common.copied')) : @js(__('app.common.copy'))"></span>
                        </x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="$set('revealedKey', null)">
                            {{ __('app.common.close') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($revealedSecret)
        <div class="mb-5 rounded-card border-2 border-amber-300 bg-amber-50 p-4">
            <p class="text-sm font-semibold text-amber-900">{{ __('app.nav.webhooks') }}</p>
            <div x-data="{ copied: false }" class="mt-3 flex items-center gap-2">
                <code class="min-w-0 flex-1 truncate rounded bg-white px-3 py-2 font-mono text-xs
                             text-ink-900 ring-1 ring-amber-200" dir="ltr">{{ $revealedSecret }}</code>
                <x-ui.button variant="secondary" size="sm"
                             x-on:click="copied = await window.copyToClipboard(@js($revealedSecret))">
                    <span x-text="copied ? @js(__('app.common.copied')) : @js(__('app.common.copy'))"></span>
                </x-ui.button>
                <x-ui.button variant="ghost" size="sm" wire:click="$set('revealedSecret', null)">
                    {{ __('app.common.close') }}
                </x-ui.button>
            </div>
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-2">
        <x-ui.card :title="__('app.nav.api')">
            @if ($this->clients->isEmpty())
                <form wire:submit="createClient" class="space-y-3">
                    <x-ui.field :label="__('form.api_client_name')" name="clientName"
                        :hint="__('form.api_client_name_hint')" required>
                        <input type="text" wire:model="clientName" class="field-input"
                               placeholder="{{ __('app.name') }}">
                    </x-ui.field>
                    <x-ui.button type="submit">{{ __('app.common.create') }}</x-ui.button>
                </form>
            @else
                <div class="space-y-5">
                    @foreach ($this->clients as $client)
                        <div wire:key="{{ $client->id }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink-900">{{ $client->name }}</p>
                                    <p class="text-2xs text-ink-500">
                                        {{ $client->environment }} · {{ $client->rateLimit() }}/min
                                    </p>
                                </div>
                                <x-ui.button variant="secondary" size="sm"
                                             wire:click="issueKey('{{ $client->id }}')">
                                    {{ __('app.common.create') }}
                                </x-ui.button>
                            </div>

                            <ul class="mt-3 divide-y divide-ink-100 border-t border-ink-100">
                                @foreach ($client->keys as $key)
                                    <li class="flex items-center justify-between gap-3 py-2">
                                        <code class="truncate font-mono text-2xs text-ink-600" dir="ltr">
                                            {{ $key->prefix }}…{{ $key->last_four }}
                                        </code>
                                        <div class="flex shrink-0 items-center gap-2">
                                            @if ($key->isUsable())
                                                <x-ui.badge tone="green" dot>{{ __('app.common.active') }}</x-ui.badge>
                                                <x-ui.button variant="ghost" size="sm"
                                                             wire:click="revokeKey('{{ $key->id }}')"
                                                             wire:confirm="{{ __('app.common.confirm') }}"
                                                             class="text-red-600 hover:bg-red-50">
                                                    {{ __('audit.action.api_key_revoked') }}
                                                </x-ui.button>
                                            @else
                                                <x-ui.badge tone="slate">{{ __('app.common.inactive') }}</x-ui.badge>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        <x-ui.card :title="__('app.nav.webhooks')">
            <form wire:submit="createWebhook" class="space-y-3">
                <x-ui.field :label="__('form.webhook_url')" name="webhookUrl" required>
                    <input type="url" wire:model="webhookUrl" class="field-input" dir="ltr"
                           placeholder="https://example.com/webhooks/banha">
                </x-ui.field>

                <x-ui.field :label="__('form.webhook_events')" name="webhookEvents" required>
                    <div class="grid max-h-44 grid-cols-1 gap-1.5 overflow-y-auto rounded-md border
                                border-ink-200 p-2 sm:grid-cols-2">
                        @foreach ($availableEvents as $event)
                            <label class="flex items-center gap-2 text-xs text-ink-700">
                                <input type="checkbox" value="{{ $event->value }}" wire:model="webhookEvents"
                                       class="size-3.5 rounded border-ink-300 text-signal-600">
                                <code class="font-mono" dir="ltr">{{ $event->value }}</code>
                            </label>
                        @endforeach
                    </div>
                </x-ui.field>

                <x-ui.button type="submit">{{ __('app.common.create') }}</x-ui.button>
            </form>

            @if ($this->endpoints->isNotEmpty())
                <ul class="mt-5 divide-y divide-ink-100 border-t border-ink-100">
                    @foreach ($this->endpoints as $endpoint)
                        <li class="flex items-center justify-between gap-3 py-2.5" wire:key="{{ $endpoint->id }}">
                            <div class="min-w-0">
                                <p class="truncate font-mono text-xs text-ink-800" dir="ltr">{{ $endpoint->url }}</p>
                                <p class="text-2xs text-ink-500">{{ count($endpoint->events) }} events</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-ui.badge :tone="$endpoint->disabled_at ? 'red' : 'green'" dot>
                                    {{ $endpoint->disabled_at ? __('app.common.inactive') : __('app.common.active') }}
                                </x-ui.badge>
                                <x-ui.button variant="ghost" size="sm"
                                             wire:click="deleteWebhook('{{ $endpoint->id }}')"
                                             wire:confirm="{{ __('app.common.confirm') }}"
                                             class="text-red-600 hover:bg-red-50">
                                    {{ __('app.common.delete') }}
                                </x-ui.button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</div>
