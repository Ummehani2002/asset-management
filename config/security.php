<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Registration
    |--------------------------------------------------------------------------
    |
    | Disable public self-registration in production. New users should be
    | created by administrators via the /users module.
    |
    */

    'allow_public_registration' => env('ALLOW_PUBLIC_REGISTRATION', false),

    /*
    |--------------------------------------------------------------------------
    | Allowed Email Domains
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of permitted email domains for registration.
    | Leave empty to allow any domain (development only).
    |
    */

    'allowed_email_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ALLOWED_EMAIL_DOMAINS', 'tanseeqinvestment.com'))
    ))),

];
