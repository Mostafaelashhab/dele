<?php

return [
    'channel' => [
        'database' => 'داخل المنصة',
        'mail' => 'بريد إلكتروني',
        'sms' => 'رسالة نصية',
        'whatsapp' => 'واتساب',
        'push' => 'إشعار فوري',
        'broadcast' => 'مباشر',
    ],

    'sms' => [
        'offer_received' => 'طلب توصيل جديد :order من :area — العائد :amount. ادخل على لوحة التحكم للرد.',
        'delivery_accepted' => 'تم قبول طلبك :order من :company وجارٍ تعيين مندوب.',
        'rider_assignment' => 'طلب جديد :order — العائد :amount. افتح التطبيق للقبول.',
        'delivery_progressed' => 'تحديث الطلب :order: :status',
        'customer_picked_up' => 'طلبك من :business في الطريق إليك. تابع المندوب: :url',
        'customer_arriving' => 'مندوب التوصيل وصل لعنوانك.',
        'customer_delivered' => 'تم تسليم طلبك من :business. شكراً لك.',
        'customer_update' => 'تحديث طلبك: :status — :url',
    ],

    'empty' => 'لا توجد إشعارات جديدة.',
    'mark_all_read' => 'تعليم الكل كمقروء',
];
