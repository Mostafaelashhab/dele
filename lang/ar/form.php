<?php

/**
 * Field labels.
 *
 * Every control in the product names itself from here. Interface labels used
 * to be borrowed from whichever key happened to read close enough — a sort
 * order labelled "الإجمالي", a company timeout labelled "الوقت المتبقي" — which
 * is how an interface starts feeling machine-assembled. A label belongs to the
 * field it names and to nothing else.
 */
return [
    // Orders
    'saved_address' => 'اختر عنوانًا محفوظًا',
    'reference' => 'رقمك المرجعي',
    'reference_hint' => 'رقم الطلب في نظامك الخاص، ليسهل الرجوع إليه لاحقًا',
    'order_notes' => 'ملاحظات للمندوب',
    'order_items' => 'محتويات الطلب',
    'priority' => 'أولوية التوصيل',
    'package_size' => 'حجم الشحنة',
    'payment_method' => 'طريقة الدفع',
    'cod_amount' => 'المبلغ المطلوب تحصيله',
    'pin_location' => 'حدّد موقع العميل على الخريطة',
    'pin_done' => 'تم تحديد الموقع',
    'pin_hint' => 'اضغط على مكان العميل — أدق من العنوان المكتوب',

    // Zones
    'zone_code' => 'كود المنطقة',
    'zone_name_ar' => 'اسم المنطقة بالعربية',
    'zone_name_en' => 'اسم المنطقة بالإنجليزية',
    'zone_radius' => 'نصف قطر المنطقة',
    'zone_radius_hint' => 'بالمتر — يحدد اتساع المنطقة على الخريطة',
    'zone_sort' => 'ترتيب العرض',
    'zone_base_price' => 'السعر الأساسي للمنطقة',
    'zone_eta' => 'الزمن المتوقع للتوصيل',
    'zone_centre_hint' => 'اضغط على الخريطة لتحديد مركز المنطقة',

    // Riders
    'vehicle_type' => 'نوع المركبة',
    'vehicle_plate' => 'رقم اللوحة',
    'max_concurrent_rider' => 'أقصى عدد طلبات في نفس الوقت',
    'create_login' => 'إنشاء حساب دخول للمندوب',
    'create_login_hint' => 'يقدر يستقبل الطلبات من تطبيق المندوب',

    // Companies
    'max_concurrent_company' => 'أقصى عدد طلبات جارية',
    'offer_timeout' => 'مهلة رد الشركة على العرض',
    'offer_timeout_hint' => 'بالثواني — بعدها ينتقل العرض للشركة التالية',
    'auto_assign' => 'تعيين مندوب تلقائيًا عند القبول',
    'auto_assign_hint' => 'المنصة تختار أقرب مندوب متاح بدل الاختيار اليدوي',
    'settlement_period' => 'دورة التسوية المالية',
    'commission' => 'نسبة عمولة المنصة',
    'commission_hint' => 'بالنقاط الأساسية — ١٢٠٠ تساوي ١٢٪',
    'working_hours' => 'مواعيد العمل',
    'day' => 'اليوم',
    'closed' => 'مغلق',
    'opens' => 'من',
    'closes' => 'إلى',

    // Matching & dispatch
    'matching_strategy' => 'طريقة اختيار شركة التوصيل',
    'matching_strategy_hint' => 'كيف ترتّب المنصة الشركات قبل عرض الطلب عليها',
    'matching_balanced' => 'متوازن (الأفضل إجمالًا)',
    'matching_cheapest' => 'الأرخص سعرًا',
    'matching_fastest' => 'الأسرع وصولًا',
    'default_priority' => 'الأولوية الافتراضية للطلبات',
    'companies_per_round' => 'عدد الشركات في الجولة الواحدة',
    'max_rounds' => 'أقصى عدد جولات بحث',
    'rider_offer_timeout' => 'مهلة رد المندوب',
    'ping_interval' => 'معدل تحديث موقع المندوب',
    'ping_interval_hint' => 'بالثواني — كل ما زاد الرقم قلّ استهلاك بطارية المندوب',
    'weights' => 'أوزان اختيار الشركة',
    'weights_hint' => 'تُضبط تلقائيًا ليصبح مجموعها ١٠٠٪ عند الحفظ',
    'weights_total' => 'المجموع قبل الضبط',

    // Pricing
    'rule_name' => 'اسم القاعدة',
    'rule_type' => 'نوع القاعدة',
    'rule_amount' => 'القيمة الثابتة',
    'rule_rate' => 'سعر الكيلومتر',
    'rule_percentage' => 'نسبة مئوية',
    'rule_percentage_hint' => 'تُحتسب على المجموع قبلها — بالسالب تعني خصمًا',
    'rule_free_distance' => 'مسافة مجانية',
    'rule_free_distance_hint' => 'بالمتر — لا تُحتسب ضمن رسوم المسافة',
    'rule_pickup_zone' => 'منطقة الاستلام',
    'rule_dropoff_zone' => 'منطقة التسليم',
    'rule_active' => 'القاعدة مفعّلة',
    'platform_fee' => 'نسبة عمولة المنصة',
    'rider_share' => 'نصيب المندوب من مستحقات الشركة',

    // Finance
    'payment_reference' => 'مرجع التحويل البنكي',
    'payment_reference_hint' => 'اختياري — لتسهيل المراجعة لاحقًا',
    'period_start' => 'بداية الفترة',
    'period_end' => 'نهاية الفترة',

    // Team & API
    'team_role' => 'صلاحية العضو',
    'api_client_name' => 'اسم التطبيق',
    'api_client_name_hint' => 'يساعدك تعرف كل مفتاح بيخدم إيه',
    'webhook_url' => 'رابط استقبال الأحداث',
    'webhook_events' => 'الأحداث المطلوب إرسالها',
    'webhook_secret_notice' => 'انسخ المفتاح الآن — لن يظهر مرة أخرى',

    // Proof of delivery
    'proof_primary' => 'صورة إثبات التسليم',
    'proof_secondary' => 'صورة إضافية (اختياري)',
    'received_by' => 'اسم من استلم الشحنة',

    // Charts
    'chart_table_view' => 'اعرض الأرقام كجدول',
    'chart_more_rows' => 'و:count غيرها لم تظهر',
    'meter_healthy' => 'في المستوى المطلوب',
    'meter_watch' => 'يحتاج متابعة',
    'meter_low' => 'أقل من المطلوب',
];
