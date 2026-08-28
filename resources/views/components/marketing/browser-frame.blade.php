@props(['label' => null])

{{-- A window chrome wrapper, so a screenshot of the interface reads as "this
     is the product" rather than as a decorative panel. --}}
<div {{ $attributes->merge(['class' => 'device-browser']) }}>
    <div class="flex items-center gap-2 border-b border-ink-200 bg-ink-50 px-3 py-2">
        <span class="flex gap-1.5" aria-hidden="true">
            <span class="size-2 rounded-full bg-ink-300"></span>
            <span class="size-2 rounded-full bg-ink-300"></span>
            <span class="size-2 rounded-full bg-ink-300"></span>
        </span>
        @if ($label)
            <span class="mx-auto rounded bg-white px-2.5 py-0.5 font-mono text-[9px] text-ink-400
                         ring-1 ring-ink-200" dir="ltr">{{ $label }}</span>
        @endif
    </div>

    {{ $slot }}
</div>
