<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Location Settings
    |--------------------------------------------------------------------------
    */
    'location' => [
        'update_interval'    => env('LOCATION_UPDATE_INTERVAL', 900), // 15 minutes
        'accuracy_threshold' => env('LOCATION_ACCURACY_THRESHOLD', 50), // meters
        'google_maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Commands Settings
    |--------------------------------------------------------------------------
    */
    'commands' => [
        'check_interval'  => env('COMMAND_CHECK_INTERVAL', 60), // 1 minute
        'expiration_time' => env('COMMAND_EXPIRATION', 3600), // 1 hour
        'max_retries'     => env('COMMAND_MAX_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'max_history_days'       => env('MAX_HISTORY_DAYS', 90),
        'screenshot_quality'     => env('SCREENSHOT_QUALITY', 80),
        'enable_call_recording'  => env('ENABLE_CALL_RECORDING', false),
        'enable_sms_monitoring'  => env('ENABLE_SMS_MONITORING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Limits
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'free_plan_devices'    => env('FREE_PLAN_DEVICES', 100),
        'premium_plan_devices' => env('PREMIUM_PLAN_DEVICES', 100),
        'family_plan_devices'  => env('FAMILY_PLAN_DEVICES', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'fcm_server_key' => env('FCM_SERVER_KEY'),
        'channels'       => ['database', 'fcm', 'broadcast'],
        'queue'          => env('NOTIFICATION_QUEUE', 'notifications'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encrypt_sensitive_data'   => env('ENCRYPT_SENSITIVE_DATA', true),
        'max_failed_login_attempts' => env('MAX_FAILED_LOGIN_ATTEMPTS', 5),
        'lockout_duration'          => env('LOCKOUT_DURATION', 900), // 15 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'user_ttl'     => env('CACHE_USER_TTL', 300),    // 5 minutes
        'device_ttl'   => env('CACHE_DEVICE_TTL', 600),  // 10 minutes
        'settings_ttl' => env('CACHE_SETTINGS_TTL', 900), // 15 minutes
    ],
];
