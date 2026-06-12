<?php

return [

    'nav' => [
        'dashboard' => 'لوحة التحكم',
        'log_in' => 'تسجيل الدخول',
        'create_workspace' => 'إنشاء مساحة عمل',
        'team' => 'الفريق',
        'billing' => 'الفوترة',
    ],

    'landing' => [
        'title' => 'منصة SaaS متعددة المستأجرين',
        'meta_description' => 'بداية Laravel متعددة المستأجرين مع قواعد بيانات معزولة، Breeze، Filament، فرق، وفوترة Stripe.',
        'hero_title' => 'بداية SaaS متعددة المستأجرين لـ',
        'hero_highlight' => 'Laravel',
        'hero_subtitle' => 'قاعدة بيانات معزولة لكل مساحة عمل، توجيه نطاقات فرعية، فرق، فوترة Stripe، لوحة Filament.',
        'live_demo' => 'عرض تجريبي',
        'architecture' => 'البنية',
        'central' => 'المركزي',
        'central_desc' => 'الإدارة، الفوترة، تسجيل مساحات العمل',
        'tenant' => 'المستأجر',
        'tenant_desc' => 'المصادقة، الفرق، قاعدة بيانات معزولة',
        'included' => 'المتضمن',
        'features' => [
            'multi_tenancy' => ['title' => 'تعدد المستأجرين', 'desc' => 'قاعدة بيانات معزولة، نطاقات فرعية ومخصصة'],
            'authentication' => ['title' => 'المصادقة', 'desc' => 'Breeze على المركزي والمستأجر'],
            'teams' => ['title' => 'الفرق والأدوار', 'desc' => 'مالك، مدير، عضو + دعوات'],
            'billing' => ['title' => 'فوترة Stripe', 'desc' => 'Cashier لكل مساحة عمل'],
            'filament' => ['title' => 'لوحة Filament', 'desc' => 'إدارة مساحات العمل على /admin'],
            'cli' => ['title' => 'سطر الأوامر', 'desc' => 'أمر tenant:provision'],
        ],
        'try_demo' => 'جرّب العرض التجريبي',
        'admin_panel' => 'لوحة الإدارة',
        'demo_login' => 'دخول العرض التجريبي',
    ],

    'dashboard' => [
        'title' => 'لوحة التحكم',
        'admin_panel' => 'لوحة الإدارة',
        'create_workspace' => 'إنشاء مساحة عمل',
        'demo_workspace' => 'مساحة العرض التجريبي',
    ],

    'tenant' => [
        'sign_in' => 'تسجيل الدخول',
        'register' => 'إنشاء حساب',
        'members' => 'الأعضاء',
        'url' => 'الرابط',
        'suspended' => 'تم تعليق مساحة العمل هذه. تواصل مع الدعم للمساعدة.',
    ],

    'workspace' => [
        'create_title' => 'أنشئ مساحة العمل الخاصة بك',
        'create_subtitle' => 'ابدأ مستأجراً جديداً على :domain',
        'name' => 'اسم مساحة العمل',
        'url' => 'رابط مساحة العمل',
        'create_button' => 'إنشاء مساحة عمل',
        'created' => 'تم إنشاء مساحة العمل بنجاح.',
    ],

    'team' => [
        'title' => 'أعضاء الفريق',
        'members' => 'الأعضاء',
        'pending_invitations' => 'دعوات معلقة',
        'invite_title' => 'ادعُ زميلاً',
        'email' => 'البريد الإلكتروني',
        'role' => 'الدور',
        'role_member' => 'عضو',
        'role_admin' => 'مدير',
        'send_invitation' => 'إرسال الدعوة',
        'member_exists' => 'هذا المستخدم عضو بالفعل في مساحة العمل.',
        'invitation_sent' => 'تم إرسال الدعوة إلى :email',
        'invitation_subject' => 'دعوة لمساحة العمل',
        'invitation_body' => 'تمت دعوتك للانضمام إلى :workspace. اقبل الدعوة هنا: :url',
    ],

    'invitations' => [
        'expired' => 'انتهت صلاحية هذه الدعوة.',
        'register_to_accept' => 'أنشئ حساباً لقبول دعوتك.',
        'wrong_email' => 'سجّل الدخول بـ :email لقبول هذه الدعوة.',
        'accepted' => 'مرحباً بك في الفريق!',
    ],

    'billing' => [
        'title' => 'الفوترة — :name',
        'checkout_success' => 'تم تحديث الاشتراك بنجاح.',
        'stripe_not_configured' => 'Stripe غير مُعدّ. أضف المفاتيح في .env (راجع README).',
        'workspace' => 'مساحة العمل',
        'active_subscription' => 'اشتراك نشط: :price',
        'manage_payment' => 'إدارة طريقة الدفع والفواتير ←',
        'no_subscription' => 'لا يوجد اشتراك نشط.',
        'per_month' => '/شهر',
        'subscribe' => 'اشترك',
        'back_to_workspace' => '← العودة لمساحة العمل',
        'stripe_not_configured_error' => 'Stripe غير مُعدّ.',
    ],

    'plans' => [
        'starter' => [
            'name' => 'المبتدئ',
            'description' => 'للفرق الصغيرة التي تبدأ.',
        ],
        'pro' => [
            'name' => 'احترافي',
            'description' => 'للفرق المتنامية التي تحتاج المزيد.',
        ],
    ],

    'oauth' => [
        'or_continue_with' => 'أو تابع باستخدام',
        'google' => 'Google',
        'github' => 'GitHub',
    ],

    'filament' => [
        'analytics_total_workspaces' => 'إجمالي مساحات العمل',
        'analytics_new_this_month' => ':count جديدة هذا الشهر',
        'analytics_active_subscriptions' => 'الاشتراكات النشطة',
        'analytics_stripe_subscribers' => 'مشتركو Stripe',
        'analytics_platform_users' => 'مستخدمي المنصة',
        'analytics_central_users' => 'حسابات التطبيق المركزي',
        'analytics_growth_chart' => 'نمو مساحات العمل (6 أشهر)',
        'workspaces' => 'مساحات العمل',
        'workspace' => 'مساحة عمل',
        'workspaces_plural' => 'مساحات العمل',
        'subdomain' => 'النطاق الفرعي',
        'url' => 'الرابط',
        'open' => 'فتح',
        'domains' => 'النطاقات',
        'domain' => 'النطاق',
        'domain_helper' => 'استخدم اسم النطاق الفرعي (مثل demo) أو نطاقاً مخصصاً (مثل app.acme.com).',
        'type' => 'النوع',
        'subdomain_type' => 'نطاق فرعي',
        'custom_domain_type' => 'نطاق مخصص',
        'stats_workspaces' => 'مساحات العمل',
        'stats_workspaces_desc' => 'إجمالي مساحات المستأجرين',
        'stats_admins' => 'مديرو المنصة',
        'stats_admins_desc' => 'مستخدمو التطبيق المركزي',
        'stats_domain' => 'النطاق المركزي',
        'stats_domain_desc' => 'تُنشأ النطاقات الفرعية تحت هذا المضيف',
        'status' => 'الحالة',
        'suspend' => 'تعليق',
        'unsuspend' => 'إلغاء التعليق',
        'suspended' => 'معلّقة',
        'active' => 'نشطة',
    ],

];
