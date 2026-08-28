<div>
    @if ($this->issues->isNotEmpty())
        <x-ui.card @class(['border-red-500/30' => $this->openCount > 0])>
            <div class="flex items-center justify-between gap-3">
                <p class="flex items-center gap-2 text-sm font-bold text-white">
                    <x-ui.icon name="alert" class="size-4 shrink-0 {{ $this->openCount > 0 ? 'text-red-400' : 'text-ink-400' }}" />
                    {{ __('tracking.issue.panel_title') }}
                </p>

                @if ($this->openCount > 0)
                    <x-ui.badge tone="red" dot>
                        {{ trans_choice('tracking.issue.open_count', $this->openCount, ['count' => $this->openCount]) }}
                    </x-ui.badge>
                @endif
            </div>

            <ul class="mt-3 space-y-2.5">
                @foreach ($this->issues as $issue)
                    <li class="rounded-lg bg-white/[0.04] p-3 ring-1 ring-inset ring-white/10">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-white">
                                    {{ $issue->category->label() }}

                                    @if ($issue->category->isUrgent() && ! $issue->isResolved())
                                        <x-ui.badge tone="red">{{ __('tracking.issue.urgent') }}</x-ui.badge>
                                    @endif
                                </p>

                                {{-- What the delivery was doing when they
                                     reported it. Read an hour later, the
                                     complaint is unintelligible without it. --}}
                                <p class="tnum mt-1 text-2xs text-ink-400">
                                    {{ __('tracking.issue.reported_when', ['status' => $issue->delivery_status->label()]) }}
                                    · {{ $issue->created_at->shortTime() }}
                                </p>
                            </div>

                            <x-ui.badge :tone="$issue->status->tone()" class="shrink-0">
                                {{ $issue->status->label() }}
                            </x-ui.badge>
                        </div>

                        @if ($issue->note)
                            <p class="mt-2 rounded-md bg-black/20 px-2.5 py-2 text-xs leading-relaxed text-ink-200">
                                {{ $issue->note }}
                            </p>
                        @endif

                        @if ($issue->isResolved())
                            <div class="mt-2 border-t border-white/10 pt-2">
                                <p class="text-xs leading-relaxed text-emerald-200/90">{{ $issue->resolution_note }}</p>
                                @if ($issue->resolvedBy)
                                    <p class="mt-1 text-2xs text-ink-500">
                                        {{ __('tracking.issue.resolved_by', ['name' => $issue->resolvedBy->name]) }}
                                        · {{ $issue->resolved_at->shortTime() }}
                                    </p>
                                @endif
                            </div>
                        @elseif ($resolving === $issue->id)
                            <form wire:submit="resolve" class="mt-3 border-t border-white/10 pt-3">
                                <label for="resolution-{{ $issue->id }}" class="field-label">
                                    {{ __('tracking.issue.resolution_label') }}
                                </label>
                                <textarea id="resolution-{{ $issue->id }}" wire:model="resolution" rows="2"
                                          maxlength="1000"
                                          placeholder="{{ __('tracking.issue.resolution_placeholder') }}"
                                          class="field-input"></textarea>
                                @error('resolution')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror

                                <div class="mt-2 flex gap-2">
                                    <x-ui.button type="submit" size="sm" variant="success">
                                        {{ __('tracking.issue.resolve') }}
                                    </x-ui.button>
                                    <x-ui.button type="button" size="sm" variant="ghost" wire:click="cancelResolve">
                                        {{ __('tracking.issue.cancel') }}
                                    </x-ui.button>
                                </div>
                            </form>
                        @else
                            <div class="mt-2.5 flex flex-wrap gap-2">
                                @if ($issue->acknowledged_at === null)
                                    <x-ui.button size="sm" variant="secondary"
                                                 wire:click="acknowledge('{{ $issue->id }}')">
                                        {{ __('tracking.issue.acknowledge') }}
                                    </x-ui.button>
                                @endif

                                <x-ui.button size="sm" variant="secondary"
                                             wire:click="startResolve('{{ $issue->id }}')">
                                    {{ __('tracking.issue.resolve') }}
                                </x-ui.button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif
</div>
