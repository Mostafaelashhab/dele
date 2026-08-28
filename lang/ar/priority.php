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
        'body' => 'التوصيل العادي داخل بنها، وأغلب الطلبات بتمشي بيه.',
        'points' => [
            'يتعرض على كل الشركات المتاحة',
            'تتبّع مباشر للعميل',
            'إثبات تسليم بالصورة',
            'الدفع مقدّم أو عند الاستلام',
        ],
    ],
    'express' => [
        'body' => 'لما الطلب مستعجل — بيتعرض على شركات أكتر وبيترتب بالأسرع وصولًا.',
        'points' => [
            'يتعرض على شركات أكتر في نفس الوقت',
            'الترتيب بالأسرع مش بالأرخص',
            'أولوية للمندوب الأقرب',
            'كل مميزات التوصيل العادي',
        ],
    ],
    'scheduled' => [
        'body' => 'لطلبات مش مستعجلة، بسعر أقل لأن الشبكة بتوزّعها على وقت أهدى.',
        'points' => [
            'أرخص من التوصيل العادي',
            'مناسب للطلبات المجمّعة',
            'نفس التتبع وإثبات التسليم',
            'تحدد وقت الاستلام بنفسك',
        ],
    ],
];
