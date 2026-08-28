<?php

return [
    'errors' => [
        'missing_key' => 'مفتاح الوصول مفقود. أرسله في ترويسة Authorization.',
        'invalid_key' => 'مفتاح الوصول غير صالح أو ملغي.',
        'client_suspended' => 'تم إيقاف هذا التطبيق عن الوصول للواجهة.',
        'rate_limited' => 'تجاوزت الحد المسموح من الطلبات. حاول بعد :seconds ثانية.',
        'idempotency_mismatch' => 'تم استخدام نفس مفتاح Idempotency مع محتوى مختلف.',
        'idempotency_in_progress' => 'طلب بنفس المفتاح قيد المعالجة حالياً.',
        'not_found' => 'المورد المطلوب غير موجود.',
        'forbidden' => 'لا تملك صلاحية الوصول لهذا المورد.',
        'business_inactive' => 'حساب النشاط التجاري غير مفعّل.',
        'not_cancellable' => 'لا يمكن إلغاء هذا الطلب في حالته الحالية.',
    ],
];
