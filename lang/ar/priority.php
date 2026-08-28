<?php

/**
 * Copy for the three delivery priorities as presented publicly.
 *
 * The prices beside these come from the live pricing engine, so the words
 * only describe what each priority actually changes — nothing here promises
 * a service level the dispatcher does not implement.
 */
return [
    'standard' => [
        'body' => 'التوصيل المعتاد داخل بنها، وهو الخيار الأنسب لمعظم الطلبات.',
        'points' => [
            'يتعرض على كل الشركات المتاحة',
            'تتبّع مباشر للعميل',
            'إثبات تسليم بالصورة',
            'الدفع مقدّم أو عند الاستلام',
        ],
    ],
    'express' => [
        'body' => 'للطلبات العاجلة — يُعرض على عدد أكبر من الشركات ويُرتَّب حسب أسرع وصول.',
        'points' => [
            'يتعرض على شركات أكتر في نفس الوقت',
            'الترتيب بالأسرع مش بالأرخص',
            'أولوية للمندوب الأقرب',
            'كل مميزات التوصيل العادي',
        ],
    ],
    'scheduled' => [
        'body' => 'للطلبات غير العاجلة، بسعر أقل لأن الشبكة توزّعها في أوقات أخفّ ازدحامًا.',
        'points' => [
            'أرخص من التوصيل العادي',
            'مناسب للطلبات المجمّعة',
            'نفس التتبع وإثبات التسليم',
            'تحدد وقت الاستلام بنفسك',
        ],
    ],
];
