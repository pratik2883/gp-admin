<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fcm' => [
        'enabled' => (bool) env('FCM_ENABLED', false),
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
        'vapid_key' => env('FCM_VAPID_KEY'),
    ],

    'message_central' => [
        'base_url' => env('MESSAGE_CENTRAL_BASE_URL', env('MESSAGECENTRAL_BASE_URL', 'https://cpaas.messagecentral.com')),
        'customer_id' => env('MESSAGECENTRAL_CUSTOMER_ID', env('MESSAGE_CENTRAL_CUSTOMER_ID')),
        'password' => env('MESSAGECENTRAL_PASSWORD', env('MESSAGE_CENTRAL_PASSWORD')),
        'email' => env('MESSAGECENTRAL_EMAIL', env('MESSAGE_CENTRAL_EMAIL')),
        'auth_token' => env('MESSAGECENTRAL_AUTH_TOKEN', env('MESSAGE_CENTRAL_AUTH_TOKEN')),
        'country' => env('MESSAGECENTRAL_COUNTRY', env('MESSAGE_CENTRAL_COUNTRY', '91')),
        'otp_sender_id' => env('MESSAGECENTRAL_OTP_SENDER_ID', env('MESSAGE_CENTRAL_OTP_SENDER_ID')),
        'otp_flow_type' => env('MESSAGECENTRAL_OTP_FLOW_TYPE', env('MESSAGE_CENTRAL_OTP_FLOW_TYPE', 'SMS')),
        'otp_expiry' => (int) env('MESSAGECENTRAL_OTP_EXPIRY', env('MESSAGE_CENTRAL_OTP_EXPIRY', 5)),
        'otp_resend_cooldown' => (int) env('MESSAGECENTRAL_OTP_RESEND_COOLDOWN', env('MESSAGE_CENTRAL_OTP_RESEND_COOLDOWN', 30)),
        'otp_max_attempts' => (int) env('MESSAGECENTRAL_OTP_MAX_ATTEMPTS', env('MESSAGE_CENTRAL_OTP_MAX_ATTEMPTS', 5)),
        'otp_length' => (int) env('MESSAGECENTRAL_OTP_LENGTH', env('MESSAGE_CENTRAL_OTP_LENGTH', 4)),
    ],

];
