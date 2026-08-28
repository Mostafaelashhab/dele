@props(['delivery'])

@php
    /**
     * What actually happened at the door.
     *
     * The photographs and the handover code were being captured and then
     * shown to nobody — the rider took them, the database kept them, and the
     * shop that would use them in a dispute had no way to look. This panel is
     * the other half of that feature.
     */
    $photos = collect(['proof_photo_path', 'proof_photo_secondary_path'])
        ->map(fn (string $attribute) => $delivery->mediaUrl($attribute))
        ->filter()
        ->values();

    $verifiedByCode = $delivery->confirmation_code_verified_at !== null;
@endphp

@if ($delivery->delivered_at !== null)
    <x-ui.card :title="__('delivery.proof.title')">
        <div class="flex flex-wrap items-center gap-2">
            @if ($verifiedByCode)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1
                             text-xs font-bold text-emerald-800">
                    <x-ui.icon name="shield" class="size-3.5" />
                    {{ __('delivery.proof.by_code') }}
                </span>
            @endif

            @if ($photos->isNotEmpty())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-signal-100 px-2.5 py-1
                             text-xs font-bold text-signal-800">
                    <x-ui.icon name="camera" class="size-3.5" />
                    {{ trans_choice('delivery.proof.photo_count', $photos->count(), ['count' => $photos->count()]) }}
                </span>
            @endif

            @if (! $verifiedByCode && $photos->isEmpty())
                <span class="inline-flex items-center gap-1.5 rounded-full bg-ink-100 px-2.5 py-1
                             text-xs font-medium text-ink-600">
                    <x-ui.icon name="alert" class="size-3.5" />
                    {{ __('delivery.proof.none') }}
                </span>
            @endif
        </div>

        @if ($delivery->received_by)
            <p class="mt-3 text-sm text-ink-700">
                <span class="text-ink-500">{{ __('delivery.proof.received_by') }}:</span>
                <span class="font-semibold">{{ $delivery->received_by }}</span>
            </p>
        @endif

        @if ($photos->isNotEmpty())
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($photos as $index => $url)
                    {{-- Opens full size in a new tab: a shop settling a
                         dispute needs to actually read the label. --}}
                    <a href="{{ $url }}" target="_blank" rel="noopener"
                       class="group relative block overflow-hidden rounded-lg border border-ink-200">
                        <img src="{{ $url }}" alt="{{ __('delivery.proof.title') }} {{ $index + 1 }}"
                             loading="lazy"
                             class="aspect-4/3 w-full object-cover transition group-hover:opacity-90">
                        <span class="absolute inset-x-0 bottom-0 bg-ink-950/60 px-2 py-1 text-center
                                     text-2xs font-semibold text-white opacity-0 transition
                                     group-hover:opacity-100">
                            {{ __('delivery.proof.open_full') }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.card>
@endif
