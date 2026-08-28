<?php

return [
    'account' => [
        'platform' => 'المنصة',
        'business' => 'النشاط التجاري',
        'delivery_company' => 'شركة التوصيل',
        'rider' => 'المندوب',
        'customer' => 'العميل',
    ],

    'category' => [
        'delivery_fee' => 'رسوم التوصيل',
        'platform_fee' => 'عمولة المنصة',
        'company_payout' => 'مستحقات الشركة',
        'rider_payout' => 'مستحقات المندوب',
        'business_charge' => 'مستحق على النشاط',
        'cod_collected' => 'نقدية محصّلة',
        'cod_remittance' => 'توريد نقدية',
        'commission' => 'عمولة',
        'refund' => 'استرداد',
        'adjustment' => 'تسوية',
    ],

    'settlement' => [
        'draft' => 'مسودة',
        'open' => 'مفتوحة',
        'locked' => 'مقفلة',
        'paid' => 'مدفوعة',
        'voided' => 'ملغاة',
    ],

    'period' => [
        'daily' => 'يومي',
        'weekly' => 'أسبوعي',
        'biweekly' => 'كل أسبوعين',
        'monthly' => 'شهري',
    ],

    'description' => [
        'business_charge' => 'رسوم توصيل الطلب :order',
        'platform_fee' => 'عمولة المنصة عن الطلب :order',
        'company_payout' => 'مستحقات التوصيل عن الطلب :order',
        'rider_payout' => 'مستحقات المندوب عن الطلب :order',
        'rider_earning' => 'أرباح توصيل الطلب :order',
        'cod_held' => 'نقدية محصّلة بعهدة الشركة — الطلب :order',
        'cod_owed' => 'نقدية مستحقة للنشاط — الطلب :order',
        'settlement_payout' => 'تسوية مستحقات :reference',
    ],
];
