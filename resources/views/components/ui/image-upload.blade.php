@props([
    'property',
    'label' => null,
    'hint' => null,
    'current' => null,
    'shape' => 'square',
    'maxEdge' => null,
    'icon' => 'image',
])

@php
    $edge = $maxEdge ?? (int) config('platform.media.logo_max_edge', 512);
    $isRound = $shape === 'round';
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'min-w-0']) }}>
    @if ($label)
        <span class="field-label">{{ $label }}</span>
    @endif

    <div x-data="imageUpload({ property: @js($property), maxEdge: {{ $edge }}, existing: @js($current) })"
         data-error-type="{{ __('validation.image', ['attribute' => $label ?? '']) }}"
         data-error-upload="{{ __('app.common.empty') }}"
         class="flex items-center gap-3">

        {{-- The preview is the control: a rider taps the picture itself, which
             is a bigger target than any button beside it. --}}
        <label class="group relative flex shrink-0 cursor-pointer items-center justify-center
                      overflow-hidden border-2 border-dashed border-ink-300 bg-ink-50
                      transition hover:border-signal-400 hover:bg-signal-50
                      {{ $isRound ? 'size-16 rounded-full' : 'size-20 rounded-md' }}">

            <template x-if="preview">
                <img :src="preview" alt="" class="size-full object-cover">
            </template>

            <template x-if="! preview">
                <span class="flex flex-col items-center gap-1 text-ink-400 group-hover:text-signal-600">
                    <x-ui.icon :name="$icon" class="size-5" />
                </span>
            </template>

            {{-- Upload progress sits over the picture, so the rider watches the
                 thing they just took rather than a separate bar. --}}
            <template x-if="uploading">
                <span class="absolute inset-0 flex items-center justify-center bg-ink-950/60 text-2xs
                             font-semibold text-white">
                    <span x-text="progress + '%'"></span>
                </span>
            </template>

            <input type="file" accept="image/*" class="sr-only" @change="choose($event)">
        </label>

        <div class="min-w-0 flex-1">
            @if ($hint)
                <p class="text-xs leading-relaxed text-ink-500">{{ $hint }}</p>
            @endif

            <div class="mt-1.5 flex items-center gap-2">
                <template x-if="preview">
                    <button type="button" @click="clear()"
                            class="text-2xs font-semibold text-red-600 hover:text-red-800">
                        {{ __('app.common.delete') }}
                    </button>
                </template>
            </div>

            <template x-if="error">
                <p class="field-error" x-text="error"></p>
            </template>

            @error($property)
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
