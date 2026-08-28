@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->only('class')->merge(['class' => 'min-w-0']) }}>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="field-label">
            {{ $label }}
            @if ($required)
                <span class="text-red-600" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="mt-1 text-xs text-ink-400">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="field-error">{{ $message }}</p>
        @enderror
    @endif
</div>
