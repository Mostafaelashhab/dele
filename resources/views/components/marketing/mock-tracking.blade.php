{{--
    What the customer receives: a link, no app, no account.

    Worth showing on its own because it is the part of the product a shop
    owner's customers actually experience, and the part that makes a small
    shop look like it has real logistics behind it.
--}}
<div class="bg-ink-100 p-3" {{-- The mock mirrors the real interface, so it follows the reader's
     direction rather than being pinned to Arabic. --}}
     dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="rounded-lg bg-ink-900 px-4 py-4 text-white">
        <p class="text-[9px] opacity-70">مطعم زاد</p>
        <p class="mt-1 text-[17px] font-bold">في الطريق إليك</p>
        <p class="mt-1.5 text-[11px] opacity-80">خلال ١٢ دقيقة تقريباً</p>
    </div>

    <div class="mt-2.5 rounded-lg bg-white p-3.5 ring-1 ring-ink-200">
        <ol class="space-y-0">
            @foreach ([
                ['label' => 'تم استلام الطلب', 'done' => true, 'current' => false],
                ['label' => 'تم تعيين شركة التوصيل', 'done' => true, 'current' => false],
                ['label' => 'تم تعيين المندوب', 'done' => true, 'current' => false],
                ['label' => 'في الطريق إليك', 'done' => true, 'current' => true],
                ['label' => 'تم التسليم', 'done' => false, 'current' => false],
            ] as $step)
                <li class="flex gap-2.5">
                    <div class="flex flex-col items-center">
                        <span @class([
                            'flex size-4 shrink-0 items-center justify-center rounded-full border-2',
                            'border-emerald-600 bg-emerald-600' => $step['done'] && ! $step['current'],
                            'border-signal-600 bg-signal-600' => $step['current'],
                            'border-ink-200 bg-white' => ! $step['done'],
                        ])>
                            @if ($step['done'] && ! $step['current'])
                                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="4"
                                     class="size-2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            @elseif ($step['current'])
                                <span class="size-1.5 rounded-full bg-white"></span>
                            @endif
                        </span>
                        @unless ($loop->last)
                            <span @class([
                                'w-0.5 flex-1',
                                'bg-emerald-600' => $step['done'],
                                'bg-ink-200' => ! $step['done'],
                            ])></span>
                        @endunless
                    </div>
                    <p @class([
                        'pb-3.5 text-[11px] leading-none',
                        'font-semibold text-ink-900' => $step['done'],
                        'text-ink-400' => ! $step['done'],
                    ])>{{ $step['label'] }}</p>
                </li>
            @endforeach
        </ol>

        <div class="mt-1 flex items-center gap-2.5 border-t border-ink-100 pt-3">
            <span class="flex size-7 items-center justify-center rounded-full bg-ink-100 text-[10px]
                         font-semibold text-ink-600">م</span>
            <div class="min-w-0">
                <p class="text-[11px] font-semibold text-ink-900">محمد</p>
                <p class="text-[9px] text-ink-500">دراجة نارية</p>
            </div>
            <span class="ms-auto text-[11px] font-semibold text-ink-700">٤.٨</span>
        </div>
    </div>
</div>
