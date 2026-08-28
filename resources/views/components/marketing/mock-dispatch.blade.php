{{--
    A dispatcher's offer board, rendered from the product's own tokens.

    This is the screen a delivery company lives in, so it is the screen worth
    showing: an order arrives, a countdown runs, and two buttons decide it.
    Built as real markup rather than an image so it stays sharp at any density
    and never drifts out of date with the interface it depicts.
--}}
<div class="bg-ink-100 p-4" {{-- The mock mirrors the real interface, so it follows the reader's
     direction rather than being pinned to Arabic. --}}
     dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="mb-3 flex items-center justify-between">
        <div>
            <p class="text-[13px] font-semibold text-ink-900">العروض الواردة</p>
            <p class="text-[10px] text-ink-500">بنها إكسبريس</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-md bg-white px-2 py-1 text-[10px]
                     font-semibold text-ink-700 ring-1 ring-ink-200">
            <span class="size-1.5 rounded-full bg-emerald-500"></span>
            ٩ مندوبين متاحين
        </span>
    </div>

    <div class="grid grid-cols-2 gap-2.5">
        @foreach ([
            ['shop' => 'مطعم زاد', 'from' => 'وسط البلد', 'to' => 'المنشية', 'pay' => '٢٤.٥٠', 'km' => '٢.٤', 'left' => 62, 'hot' => true],
            ['shop' => 'صيدلية النور', 'from' => 'فريد ندا', 'to' => 'جامعة بنها', 'pay' => '١٨.٠٠', 'km' => '١.٦', 'left' => 41, 'hot' => false],
        ] as $offer)
            <article @class([
                'overflow-hidden rounded-lg bg-white shadow-sm',
                'ring-2 ring-signal-400' => $offer['hot'],
                'ring-1 ring-ink-200' => ! $offer['hot'],
            ])>
                <div @class([
                    'flex items-center justify-between px-2.5 py-1.5',
                    'bg-signal-50' => $offer['hot'],
                    'bg-ink-50' => ! $offer['hot'],
                ])>
                    <span class="text-[10px] font-semibold {{ $offer['hot'] ? 'text-signal-800' : 'text-ink-600' }}">
                        {{ $offer['shop'] }}
                    </span>
                    <span class="text-[10px] font-bold {{ $offer['left'] < 45 ? 'text-red-600' : 'text-signal-800' }}">
                        {{ $offer['left'] }}ث
                    </span>
                </div>

                <div class="px-2.5 py-2">
                    <div class="flex items-baseline justify-between">
                        <p class="text-[15px] font-bold text-emerald-700">{{ $offer['pay'] }}</p>
                        <p class="text-[9px] text-ink-400">{{ $offer['km'] }} كم</p>
                    </div>
                    <p class="mt-1 truncate text-[10px] text-ink-600">
                        {{ $offer['from'] }} <span class="text-ink-300">←</span> {{ $offer['to'] }}
                    </p>

                    {{-- The countdown, shown as a depleting rule. --}}
                    <div class="mt-2 h-1 overflow-hidden rounded-full bg-ink-100">
                        <div class="h-full rounded-full {{ $offer['left'] < 45 ? 'bg-red-500' : 'bg-signal-500' }}"
                             style="width: {{ $offer['left'] }}%"></div>
                    </div>

                    <div class="mt-2 grid grid-cols-3 gap-1.5">
                        <span class="col-span-2 rounded bg-emerald-600 py-1 text-center text-[10px]
                                     font-bold text-white">قبول</span>
                        <span class="rounded bg-ink-100 py-1 text-center text-[10px] font-semibold
                                     text-ink-600">رفض</span>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-2.5 overflow-hidden rounded-lg bg-white ring-1 ring-ink-200">
        <div class="border-b border-ink-100 px-2.5 py-1.5">
            <p class="text-[10px] font-semibold text-ink-700">التوصيلات الجارية</p>
        </div>
        @foreach ([
            ['no' => 'BN‑4821', 'rider' => 'محمد إبراهيم', 'state' => 'في الطريق', 'tone' => 'indigo'],
            ['no' => 'BN‑4819', 'rider' => 'أحمد سمير', 'state' => 'تم الاستلام', 'tone' => 'blue'],
            ['no' => 'BN‑4816', 'rider' => 'مصطفى جمال', 'state' => 'تم التسليم', 'tone' => 'green'],
        ] as $row)
            <div class="flex items-center gap-2 border-b border-ink-50 px-2.5 py-1.5 last:border-0">
                <span class="size-4 shrink-0 rounded-full bg-ink-100"></span>
                <span class="text-[10px] font-medium text-ink-800">{{ $row['no'] }}</span>
                <span class="truncate text-[10px] text-ink-500">{{ $row['rider'] }}</span>
                <span @class([
                    'ms-auto shrink-0 rounded px-1.5 py-0.5 text-[9px] font-semibold',
                    'bg-indigo-50 text-indigo-800' => $row['tone'] === 'indigo',
                    'bg-signal-50 text-signal-800' => $row['tone'] === 'blue',
                    'bg-emerald-50 text-emerald-800' => $row['tone'] === 'green',
                ])>{{ $row['state'] }}</span>
            </div>
        @endforeach
    </div>
</div>
