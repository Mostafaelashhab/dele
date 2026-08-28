@props(['width' => 240])

<div {{ $attributes->merge(['class' => 'device-phone']) }} style="width: {{ $width }}px">
    {{-- The notch. Small, but without it the frame reads as a plain rounded
         rectangle rather than a phone. --}}
    <div class="relative bg-ink-950 pt-2">
        <span class="absolute left-1/2 top-1 h-1 w-14 -translate-x-1/2 rounded-full bg-ink-800"
              aria-hidden="true"></span>
    </div>

    <div class="overflow-hidden bg-ink-100" style="height: {{ round($width * 1.9) }}px">
        {{ $slot }}
    </div>
</div>
