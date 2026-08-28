{{--
    Creating a delivery, with the price forming as the form is filled in.

    The live quote is the single most persuasive thing in the product for a
    shop owner — it is the answer to "how much will this cost me?" given
    before they commit — so it is what the mock leads with.
--}}
<div class="bg-ink-100 p-4" {{-- The mock mirrors the real interface, so it follows the reader's
     direction rather than being pinned to Arabic. --}}
     dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="grid gap-3 lg:grid-cols-3">
        <div class="space-y-2.5 lg:col-span-2">
            <div class="rounded-lg bg-white p-3 ring-1 ring-ink-200">
                <p class="mb-2 text-[10px] font-bold text-ink-700">الاستلام</p>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="mb-1 text-[8px] font-semibold text-ink-500">اسم المستلم</p>
                        <div class="rounded border border-ink-200 px-2 py-1.5 text-[10px] text-ink-800">
                            مطعم زاد
                        </div>
                    </div>
                    <div>
                        <p class="mb-1 text-[8px] font-semibold text-ink-500">رقم الهاتف</p>
                        <div class="rounded border border-ink-200 px-2 py-1.5 text-left text-[10px] text-ink-800"
                             dir="ltr">01012000101</div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-3 ring-1 ring-ink-200">
                <p class="mb-2 text-[10px] font-bold text-ink-700">العميل</p>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <p class="mb-1 text-[8px] font-semibold text-ink-500">اسم العميل</p>
                        <div class="rounded border border-ink-200 px-2 py-1.5 text-[10px] text-ink-800">
                            سارة محمود
                        </div>
                    </div>
                    <div>
                        <p class="mb-1 text-[8px] font-semibold text-ink-500">المنطقة</p>
                        <div class="flex items-center justify-between rounded border border-signal-400
                                    bg-signal-50 px-2 py-1.5 text-[10px] font-medium text-signal-900">
                            المنشية
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                 class="size-2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>
                </div>

                {{-- The map strip: a pin is how an address is actually given
                     here, so the form shows one being dropped. --}}
                <div class="relative mt-2 h-14 overflow-hidden rounded border border-ink-200 bg-ink-100">
                    <div class="absolute inset-0 opacity-60"
                         style="background-image:
                            linear-gradient(rgb(148 163 184 / 35%) 1px, transparent 1px),
                            linear-gradient(90deg, rgb(148 163 184 / 35%) 1px, transparent 1px);
                            background-size: 14px 14px"></div>
                    <span class="absolute left-[30%] top-[45%] size-2.5 rounded-full bg-signal-600
                                 ring-2 ring-white"></span>
                    <span class="absolute left-[68%] top-[30%] size-2.5 rounded-full bg-emerald-600
                                 ring-2 ring-white"></span>
                    <svg class="absolute inset-0 size-full" aria-hidden="true">
                        <line x1="31%" y1="48%" x2="68%" y2="33%" stroke="#1e46d6" stroke-width="1.5"
                              stroke-dasharray="4 4" opacity="0.6"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- The quote, sticky in the real screen. --}}
        <div class="rounded-lg bg-white p-3 ring-1 ring-ink-200">
            <p class="text-[10px] font-bold text-ink-700">السعر</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-ink-900">
                ٢٧.٥٠ <span class="text-[11px] font-medium text-ink-400">ج.م</span>
            </p>

            <dl class="mt-2.5 space-y-1 border-t border-ink-100 pt-2">
                @foreach ([
                    ['السعر الأساسي', '١٥.٠٠'],
                    ['رسوم المسافة', '٩.٠٠'],
                    ['رسوم تحصيل النقدية', '٣.٠٠'],
                    ['تقريب', '٠.٥٠'],
                ] as [$label, $value])
                    <div class="flex justify-between text-[9px]">
                        <dt class="text-ink-500">{{ $label }}</dt>
                        <dd class="text-ink-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            <dl class="mt-2 space-y-1 border-t border-ink-100 pt-2">
                <div class="flex justify-between text-[9px]">
                    <dt class="text-ink-500">المسافة</dt>
                    <dd class="text-ink-800">٢.٤ كم</dd>
                </div>
                <div class="flex justify-between text-[9px]">
                    <dt class="text-ink-500">الوقت المتوقع</dt>
                    <dd class="text-ink-800">~٢٢ دقيقة</dd>
                </div>
            </dl>

            <div class="mt-3 rounded bg-signal-600 py-2 text-center text-[11px] font-bold text-white">
                إنشاء طلب توصيل
            </div>
        </div>
    </div>
</div>
