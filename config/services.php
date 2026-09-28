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
        'from_email' => env('RESEND_FROM_EMAIL', 'onboarding@resend.dev'),
        'from_name' => env('RESEND_FROM_NAME', env('APP_NAME', 'Knowledge Hub')),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OATH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        'api_url' => env('AI_API_URL', 'https://api.openai.com/v1'),
        'api_key' => env('AI_API_KEY', ''),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
    ],

    'nvidia' => [
        'api_url' => env('NVIDIA_API_URL', 'https://integrate.api.nvidia.com/v1'),
        'api_key' => env('NVIDIA_API_KEY', ''),
        'model' => env('NVIDIA_MODEL', 'deepseek-ai/deepseek-r1'),
    ],

    // Weekly family insight push. Without a server key the FcmChannel stays
    // inert and the app relies on the persisted insight it reads on open.
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY', ''),
        'project_id' => env('FCM_PROJECT_ID', ''),
    ],

];
