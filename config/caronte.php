<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Caronte Server Configuration
    |--------------------------------------------------------------------------
    */
    'url'        => env('CARONTE_URL', ''),
    'app_cn'     => env('CARONTE_APP_CN', ''),
    'app_secret' => env('CARONTE_APP_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | JWT Token Configuration
    |--------------------------------------------------------------------------
    */
    'issuer_id'      => env('CARONTE_ISSUER_ID', 'caronte'),
    'enforce_issuer' => env('CARONTE_ENFORCE_ISSUER', true),

    /*
    |--------------------------------------------------------------------------
    | Authentication Features
    |--------------------------------------------------------------------------
    */
    'use_2fa'               => env('CARONTE_2FA', false),
    'allow_http_requests'   => env('CARONTE_ALLOW_HTTP_REQUESTS', false),
    'tls_verify'            => env('CARONTE_TLS_VERIFY', true),
    'update_local_user'     => env('CARONTE_UPDATE_LOCAL_USER', false),
    'notification_delivery' => env('CARONTE_NOTIFICATION_DELIVERY', 'server'),
    'session_key'           => env('CARONTE_SESSION_KEY', 'caronte.user_token'),

    'notifications' => [
        'two_factor_sender'       => env(
            'CARONTE_TWO_FACTOR_SENDER',
            Ometra\Caronte\Notifications\TwoFactorChallengeSender::class
        ),
        'password_recovery_sender' => env(
            'CARONTE_PASSWORD_RECOVERY_SENDER',
            Ometra\Caronte\Notifications\PasswordRecoverySender::class
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'routes_prefix' => env('CARONTE_ROUTES_PREFIX', ''),
    'success_url'   => env('CARONTE_SUCCESS_URL', '/'),
    'login_url'     => env('CARONTE_LOGIN_URL', '/login'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout'     => (int) env('CARONTE_HTTP_TIMEOUT', 10),
        'retries'     => (int) env('CARONTE_HTTP_RETRIES', 1),
        'retry_sleep' => (int) env('CARONTE_HTTP_RETRY_SLEEP', 150),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Roles
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'root' => 'Default super administrator role',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */
    'management' => [
        'enabled'      => env('CARONTE_MANAGEMENT_ENABLED', true),
        'route_prefix' => env('CARONTE_MANAGEMENT_ROUTE_PREFIX', 'caronte/management'),
        'access_roles' => array_values(
            array_filter(
                array_map('trim', explode(',', (string) env('CARONTE_MANAGEMENT_ACCESS_ROLES', 'root')))
            )
        ),
        'use_inertia' => env('CARONTE_MANAGEMENT_USE_INERTIA', env('CARONTE_USE_INERTIA', false)),
        'features' => [
            'metadata'         => env('CARONTE_MANAGEMENT_METADATA', true),
            'profile_pictures' => env('CARONTE_MANAGEMENT_PROFILE_PICTURES', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | View & UI Configuration
    |--------------------------------------------------------------------------
    */
    'use_inertia' => env('CARONTE_USE_INERTIA', false),
    'ui' => [
        'branding' => [
            'app_name'      => env('CARONTE_UI_APP_NAME', env('APP_NAME', 'Caronte')),
            'headline'      => env('CARONTE_UI_HEADLINE', 'Secure access with Caronte'),
            'subheadline'   => env(
                'CARONTE_UI_SUBHEADLINE',
                'Authenticate users and administer access from a polished package surface.'
            ),
            'support_email' => env('CARONTE_UI_SUPPORT_EMAIL', ''),
            'logo_url'      => env('CARONTE_UI_LOGO_URL', ''),
            'accent'        => env('CARONTE_UI_ACCENT', '#0f766e'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table Prefix
    |--------------------------------------------------------------------------
    */
    'table_prefix' => env('CARONTE_TABLE_PREFIX', ''),
];
