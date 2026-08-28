<div>
    <x-ui.page-header :title="__('review.title')" :subtitle="__('review.subtitle')" />

    @if ($this->pending->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="check" :title="__('review.empty')" :description="__('review.empty_hint')" />
        </x-ui.card>
    @else
        <div class="space-y-4">
            @foreach ($this->pending as $company)
                @php
                    $rider = $company->is_solo ? $company->riders->first() : null;
                @endphp

                <x-ui.card wire:key="review-{{ $company->id }}">
                    <x-slot:title>{{ $company->displayName() }}</x-slot:title>

                    <x-slot:actions>
                        <x-ui.badge :tone="$company->is_solo ? 'green' : 'blue'">
                            {{ $company->is_solo ? __('review.rider') : __('review.company') }}
                        </x-ui.badge>
                    </x-slot:actions>

                    <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-start">
                        <div class="min-w-0">
                            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-2xs font-semibold uppercase tracking-wider text-ink-400">
                                        {{ __('review.contact') }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-white">{{ $company->contact_person }}</dd>
                                    <dd class="tnum text-sm text-ink-300" dir="ltr">{{ $company->phone }}</dd>
                                    <dd class="truncate text-sm text-ink-300" dir="ltr">{{ $company->email }}</dd>
                                </div>

                                <div>
                                    <dt class="text-2xs font-semibold uppercase tracking-wider text-ink-400">
                                        {{ __('review.registered') }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-white">
                                        {{ $company->created_at->diffForHumans() }}
                                    </dd>

                                    <dt class="mt-3 text-2xs font-semibold uppercase tracking-wider text-ink-400">
                                        {{ __('review.fleet') }}
                                    </dt>
                                    <dd class="tnum mt-1 text-sm text-white">
                                        {{ $company->max_concurrent_deliveries }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-4 border-t border-white/10 pt-4">
                                <p class="text-2xs font-semibold uppercase tracking-wider text-ink-400">
                                    {{ __('review.zones') }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($company->serviceAreas as $zone)
                                        <span class="rounded-md bg-white/[0.06] px-2 py-1 text-xs text-ink-200">
                                            {{ $zone->displayName() }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- The documents. Only a solo rider has them: a
                                 company vouches for its own people. --}}
                            @if ($rider)
                                <div class="mt-4 border-t border-white/10 pt-4">
                                    <p class="text-2xs font-semibold uppercase tracking-wider text-ink-400">
                                        {{ __('review.identity') }}
                                    </p>

                                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                        @foreach ([
                                            ['label' => __('review.id_front'), 'doc' => 'id_card_front_path', 'private' => true],
                                            ['label' => __('review.id_back'), 'doc' => 'id_card_back_path', 'private' => true],
                                            ['label' => __('review.face'), 'doc' => 'photo_path', 'private' => false],
                                        ] as $shot)
                                            @php
                                                $url = $shot['private']
                                                    ? ($rider->hasPrivateMedia($shot['doc'])
                                                        ? route('admin.identity.document', [$rider->id, $shot['doc']])
                                                        : null)
                                                    : $rider->mediaUrl($shot['doc']);
                                            @endphp

                                            <figure>
                                                <figcaption class="mb-1.5 text-xs font-medium text-ink-300">
                                                    {{ $shot['label'] }}
                                                </figcaption>

                                                @if ($url)
                                                    <a href="{{ $url }}" target="_blank" rel="noopener"
                                                       class="group block overflow-hidden rounded-lg border border-white/10">
                                                        <img src="{{ $url }}" alt="{{ $shot['label'] }}"
                                                             loading="lazy"
                                                             class="aspect-4/3 w-full bg-white/[0.06] object-cover
                                                                    transition group-hover:opacity-90">
                                                    </a>
                                                @else
                                                    <div class="flex aspect-4/3 items-center justify-center
                                                                rounded-lg border border-dashed border-white/15
                                                                bg-white/[0.03] text-xs text-ink-400">
                                                        {{ __('review.missing') }}
                                                    </div>
                                                @endif
                                            </figure>
                                        @endforeach
                                    </div>

                                    <p class="mt-2.5 flex items-start gap-1.5 text-xs text-ink-400">
                                        <x-ui.icon name="shield" class="mt-0.5 size-3.5 shrink-0" />
                                        {{ __('review.identity_hint') }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- The decision. --}}
                        <div class="lg:w-64 lg:shrink-0">
                            @if ($rejectingId === $company->id)
                                <div class="rounded-card border border-red-200 bg-red-50 p-4">
                                    <x-ui.field :label="__('review.reason')" name="rejectionReason"
                                                :hint="__('review.reason_hint')" required>
                                        <textarea id="rejectionReason" wire:model="rejectionReason" rows="3"
                                                  class="field-input"></textarea>
                                    </x-ui.field>

                                    <div class="mt-3 flex gap-2">
                                        <button type="button" wire:click="reject"
                                                class="flex-1 rounded-lg bg-red-600 px-3 py-2.5 text-sm
                                                       font-bold text-white transition hover:bg-red-700">
                                            {{ __('review.confirm_reject') }}
                                        </button>
                                        <button type="button" wire:click="cancelRejection"
                                                class="rounded-lg border border-white/15 bg-white px-3 py-2.5
                                                       text-sm font-semibold text-ink-200 transition hover:bg-white/5">
                                            {{ __('review.cancel') }}
                                        </button>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs leading-relaxed text-ink-400">
                                    {{ $company->is_solo
                                        ? __('review.approve_rider_hint')
                                        : __('review.approve_company_hint') }}
                                </p>

                                <button type="button" wire:click="approve('{{ $company->id }}')"
                                        wire:loading.attr="disabled"
                                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg
                                               bg-emerald-600 px-4 py-3 text-sm font-bold text-white
                                               transition hover:bg-emerald-700 disabled:opacity-60">
                                    <x-ui.icon name="check" class="size-4" />
                                    {{ __('review.approve') }}
                                </button>

                                <button type="button" wire:click="startRejection('{{ $company->id }}')"
                                        class="mt-2 w-full rounded-lg border border-white/15 bg-white px-4 py-3
                                               text-sm font-semibold text-ink-200 transition hover:bg-white/5">
                                    {{ __('review.reject') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>
