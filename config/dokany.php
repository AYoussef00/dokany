<?php

return [

    /** URL segment before the store slug, e.g. /shop/my-boutique */
    'storefront_path_prefix' => env('DOKANY_STORE_PATH_PREFIX', 'shop'),

    /**
     * Seconds to cache the public storefront Inertia payload (catalog JSON).
     * Keys include a revision hash so updates invalidate without waiting for TTL.
     * Default 0 = always fresh catalog (مناسب للتطوير ومتاجر صغيرة). للإنتاج يمكن رفع القيمة.
     */
    'storefront_catalog_cache_ttl' => max(0, (int) env('DOKANY_STOREFRONT_CACHE_TTL', 0)),

    /**
     * Default hero copy on the public storefront (merchants can override in store settings).
     *
     * @var array{primary: string, secondary: string}
     */
    'storefront_hero_defaults' => [
        'primary' => 'مرحباً بك — نوفر لك منتجات نختارها بعناية، بجودة نفتخر بها. تصفّح القائمة، أضف ما يعجبك إلى سلّتك، وأرسل طلبك عبر واتساب في لحظات.',
        'secondary' => 'تسوّق بهدوء، بلا تعقيد — كل منتجين بعرض واضح لتجربة بسيطة وأنيقة.',
    ],

    /**
     * Slugs merchants may not use (lowercase), to avoid confusion with platform routes.
     *
     * @var list<string>
     */
    'reserved_store_slugs' => [
        'admin',
        'api',
        'app',
        'dashboard',
        'login',
        'register',
        'logout',
        'merchant',
        'onboarding',
        'settings',
        'storage',
        'vendor',
        'up',
        'password',
        'email',
        'verify',
        'forgot-password',
        'reset-password',
        'two-factor',
        'shop',
        'pay-link',
    ],

    /**
     * Copy shown to buyers on InstaPay payment step after checkout (store orders).
     */
    'storefront_buyer_payment' => [
        'instructions' => env(
            'DOKANY_BUYER_INSTAPAY_INSTRUCTIONS',
            'ادخل على تطبيق InstaPay أو البنك، ثم حوّل المبلغ الإجمالي الظاهر أدناه إلى رقم التاجر. احفظ لقطة شاشة واضحة للإيصال وارفقها قبل إتمام الطلب.',
        ),
    ],

    /**
     * Paths excluded from public page-view analytics (admin / merchant / settings).
     * Used by PageViewController, dashboard aggregates, and any future reporting.
     *
     * @var list<string>
     */
    'analytics' => [
        'public_page_view_exclude_prefixes' => [
            '/dashboard',
            '/merchant',
            '/settings',
        ],
    ],

    /** Public support / WhatsApp (E.164). Shown in SEO JSON-LD and can be shared to the frontend. */
    'support' => [
        'phone_e164' => env('DOKANY_SUPPORT_PHONE', '+966597150026'),
    ],

    /** Landing / SEO asset paths relative to public/ or absolute URLs under base. */
    'seo' => [
        'og_image' => env('DOKANY_SEO_OG_IMAGE', '/dokany_ad_v1_1.png'),
        'organization_logo' => '/favicon_io/android-chrome-512x512.png',
    ],

    'subscription' => [
        'amount' => (int) env('DOKANY_SUBSCRIPTION_AMOUNT', 500),
        'currency_label' => env('DOKANY_SUBSCRIPTION_CURRENCY', 'ج.م'),
        /** Short currency label for admin UI in English (e.g. EGP). */
        'currency_label_en' => env('DOKANY_SUBSCRIPTION_CURRENCY_EN', 'EGP'),
        /** Wallet number shown to merchants for paying the platform subscription (InstaPay). */
        'instapay_wallet' => env('DOKANY_SUBSCRIPTION_INSTAPAY', ''),
        'instructions' => env(
            'DOKANY_SUBSCRIPTION_INSTRUCTIONS',
            'حوّل المبلغ أعلاه إلى الرقم التالي عبر InstaPay واحفظ إيصال التحويل. بعدها ارفع لقطة شاشة للإيصال واضغط «لقد قمت بالدفع».',
        ),
        /** Days of platform access from merchant registration (approved sellers). */
        'merchant_access_days' => (int) env('DOKANY_MERCHANT_ACCESS_DAYS', 30),
    ],

];
