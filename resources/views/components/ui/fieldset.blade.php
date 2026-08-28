@props(['legend'])

{{--
    A labelled group inside a long form.

    Registration asks for eight things, and eight stacked inputs read as one
    undifferentiated wall. Grouping them lets someone see how far along they
    are without counting fields.
--}}
<fieldset {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <legend class="mb-3 flex w-full items-center gap-3">
        <span class="text-xs font-bold uppercase tracking-wider text-ink-400">{{ $legend }}</span>
        <span class="h-px flex-1 bg-ink-200" aria-hidden="true"></span>
    </legend>

    <div class="space-y-4">
        {{ $slot }}
    </div>
</fieldset>
