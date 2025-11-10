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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'wablas' => [
        'token' => env('WABLAS_TOKEN'),
        'secret_key' => env('WABLAS_SECRET_KEY'),
        'base_url' => env('WABLAS_BASE_URL', 'https://texas.wablas.com/api'),
        'auth_header' => env('WABLAS_AUTH_HEADER', 'concat'),
    ],

    // KPI external API (used for syncing CaseProject data)
    'kpi' => [
        'base_url' => env('KPI_BASE_URL', 'https://kpi.rafatax.id/api'),
        'login_path' => env('KPI_LOGIN_PATH', '/login'), // POST email,password -> token
        'case_projects_path' => env('KPI_CASE_PROJECTS_PATH', '/case-projects'), // GET list of case projects
        'username' => env('KPI_USERNAME'), // optional default credential (email)
        'password' => env('KPI_PASSWORD'), // optional default credential
        'username_field' => env('KPI_LOGIN_USERNAME_FIELD', 'email'), // 'username' or 'email'
        // Timeout in seconds for HTTP requests
        'timeout' => env('KPI_TIMEOUT', 15),
    ],

];
