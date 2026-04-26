<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Caronte Server Configuration
    |--------------------------------------------------------------------------
    */
    'URL'        => env('CARONTE_URL', ''),
    'APP_ID'     => env('CARONTE_APP_ID', ''),
    'APP_SECRET' => env('CARONTE_APP_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | JWT Token Configuration
    |--------------------------------------------------------------------------
    */
    'ISSUER_ID'      => env('CARONTE_ISSUER_ID', ''),
    'ENFORCE_ISSUER' => env('CARONTE_ENFORCE_ISSUER', false),

    /*
    |--------------------------------------------------------------------------
    | Authentication Features
    |--------------------------------------------------------------------------
    */
    'USE_2FA'               => env('CARONTE_2FA', false),
    'ALLOW_HTTP_REQUESTS'   => env('CARONTE_ALLOW_HTTP_REQUESTS', false),
    'UPDATE_LOCAL_USER'     => env('CARONTE_UPDATE_LOCAL_USER', false),
    'NOTIFICATION_DELIVERY' => env('CARONTE_NOTIFICATION_DELIVERY', 'server'),
    'SESSION_KEY'           => env('CARONTE_SESSION_KEY', 'caronte.user_token'),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'ROUTES_PREFIX' => env('CARONTE_ROUTES_PREFIX', ''),
    'SUCCESS_URL'   => env('CARONTE_SUCCESS_URL', '/'),
    'LOGIN_URL'     => env('CARONTE_LOGIN_URL', '/login'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */
    'HTTP' => [
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
    'USE_INERTIA' => env('CARONTE_USE_INERTIA', false),
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
