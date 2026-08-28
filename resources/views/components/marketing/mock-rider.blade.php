{{--
    The rider app, as it actually looks mid-delivery.

    Shown because the rider screen is the part of the product a shop owner
    never sees but always asks about — "how does the guy know where to go?"
--}}
<div class="flex h-full flex-col bg-ink-100" {{-- The mock mirrors the real interface, so it follows the reader's
     direction rather than being pinned to Arabic. --}}
     dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <header class="bg-ink-950 px-3.5 pb-3.5 pt-5 text-white">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[13px] font-semibold">محمد إبراهيم</p>
                <p class="text-[9px] text-ink-400">بنها إكسبريس</p>
            </div>
            <span class="rounded bg-white/10 px-1.5 py-1 text-[9px] font-semibold">الأرباح</span>
        </div>

        <div class="mt-3 flex items-center justify-center gap-1.5 rounded-lg bg-emerald-500 py-2.5">
            <span class="size-2 rounded-full bg-white"></span>
            <span class="text-[12px] font-bold">متاح للعمل</span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2">
            <div class="rounded bg-white/5 px-2 py-1.5">
                <p class="text-[8px] text-ink-400">طلبات اليوم</p>
                <p class="text-[15px] font-semibold">٧</p>
            </div>
            <div class="rounded bg-white/5 px-2 py-1.5">
                <p class="text-[8px] text-ink-400">أرباح اليوم</p>
                <p class="text-[15px] font-semibold">١٦٤.٥٠</p>
            </div>
        </div>
    </header>

    <div class="flex-1 space-y-2.5 p-3">
        {{-- A live offer: payout first, because that is what a rider decides
             on, then the timer. --}}
        <article class="rounded-lg border-2 border-signal-500 bg-white p-3 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-2">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded bg-ink-100">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#4c5769" stroke-width="2"
                             class="size-3.5" aria-hidden="true">
                            <path d="M3 2v7a3 3 0 0 0 6 0V2M6 2v20M18 2c-1.7 0-3 2.2-3 5s1.3 5 3 5v10"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[12px] font-bold text-ink-900">مطعم زاد</p>
                        <p class="text-[9px] text-ink-500">وسط البلد ← المنشية</p>
                    </div>
                </div>
                <p class="text-[15px] font-bold text-emerald-700">٢٤.٥٠</p>
            </div>

            <div class="mt-2.5 flex items-center justify-between border-t border-ink-100 pt-2">
                <span class="text-[9px] text-ink-500">٢.٤ كم</span>
                <span class="text-[9px] font-bold text-amber-700">٤٨ث</span>
            </div>
        </article>

        <div class="rounded-lg bg-white p-3 ring-1 ring-ink-200">
            <p class="text-[9px] font-bold uppercase tracking-wide text-ink-400">الاستلام</p>
            <p class="mt-1 text-[12px] font-semibold text-ink-900">مطعم زاد</p>
            <p class="text-[10px] leading-relaxed text-ink-500">شارع فريد ندا، بجوار الصيدلية</p>
            <div class="mt-2 grid grid-cols-2 gap-1.5">
                <span class="rounded bg-ink-100 py-1.5 text-center text-[10px] font-semibold text-ink-700">
                    اتصال
                </span>
                <span class="rounded bg-ink-100 py-1.5 text-center text-[10px] font-semibold text-ink-700">
                    الاتجاهات
                </span>
            </div>
        </div>
    </div>

    <div class="border-t border-ink-200 bg-white p-3">
        <div class="rounded-lg bg-signal-600 py-3 text-center text-[13px] font-bold text-white">
            وصلت لنقطة الاستلام
        </div>
    </div>
</div>
