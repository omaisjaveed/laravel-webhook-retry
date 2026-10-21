<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Delivery Settings
    |--------------------------------------------------------------------------
    |
    | These defaults apply when an endpoint does not override them.
    | retry_intervals are seconds to wait before each subsequent attempt
    | after the first failure (attempt 2 uses index 0, etc.).
    |
    */

    'timeout' => (int) env('WEBHOOK_TIMEOUT', 30),

    'max_attempts' => (int) env('WEBHOOK_MAX_ATTEMPTS', 5),

    'retry_intervals' => array_map(
        'intval',
        array_filter(explode(',', env('WEBHOOK_RETRY_INTERVALS', '60,300,900,3600')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Signature Header (optional HMAC)
    |--------------------------------------------------------------------------
    |
    | When an endpoint has a secret, the payload is signed with HMAC-SHA256
    | and sent in this header so receivers can verify authenticity.
    |
    */

    'signature_header' => env('WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),

    'event_id_header' => env('WEBHOOK_EVENT_ID_HEADER', 'X-Webhook-Event-Id'),

    'event_name_header' => env('WEBHOOK_EVENT_NAME_HEADER', 'X-Webhook-Event'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Authentication
    |--------------------------------------------------------------------------
    */

    'dashboard' => [
        'username' => env('WEBHOOK_DASHBOARD_USER', 'admin'),
        'password' => env('WEBHOOK_DASHBOARD_PASSWORD', 'secret'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response body storage limit (characters)
    |--------------------------------------------------------------------------
    */

    'response_body_limit' => (int) env('WEBHOOK_RESPONSE_BODY_LIMIT', 5000),

];
