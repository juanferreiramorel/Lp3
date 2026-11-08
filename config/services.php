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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ai_ocr' => [
        'base_url' => env('AI_OCR_API_BASE', 'http://localhost:3005'),
        'jwt' => env('AI_OCR_JWT', ''),
        'socket_secret' => env('AI_OCR_SOCKET_SECRET', ''),
    ],

    /*
     * People: the DNIT (ex SET) taxpayer registry, exposed as a private REST
     * API. Used to resolve a CI or RUC into a name before a client is created.
     * The token is a JWT the service issues; it never reaches the browser.
     */
    'people' => [
        'base_url' => env('PEOPLE_API_BASE', 'https://people.lanzapy.com'),
        'jwt' => env('PEOPLE_API_JWT', ''),
        'timeout' => (int) env('PEOPLE_API_TIMEOUT', 8),
    ],

];
