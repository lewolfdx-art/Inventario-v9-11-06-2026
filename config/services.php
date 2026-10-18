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

    /*
    |--------------------------------------------------------------------------
    | ImpresiÃ³n de etiquetas ZPL (Zebra)
    |--------------------------------------------------------------------------
    | driver: cups    -> Linux/Mac, impresora USB vÃ­a cola CUPS (lp -d <cola> -o raw)
    |         network -> Impresora de red (socket TCP al puerto 9100)
    |         windows -> Windows con Zebra Setup Utilities (PrnUtils.exe)
    | Si no se define, se autodetecta: windows en Windows, cups en Linux/Mac.
    */

    'zpl' => [
        'driver' => env('ZPL_DRIVER', 'cups'),
        'queue' => env('ZPL_QUEUE', env('ZPL_PRINTER_QUEUE', 'Zebra')),
        'host' => env('ZPL_HOST', env('ZPL_PRINTER_HOST', '192.168.1.100')),
        'port' => (int) env('ZPL_PORT', env('ZPL_PRINTER_PORT', 9100)),
        'windows_prn_utils' => env('ZPL_WINDOWS_PRNUTILS', 'C:\\Program Files (x86)\\Zebra Technologies\\Zebra Setup Utilities\\App\\PrnUtils.exe'),
        'windows_port' => env('ZPL_WINDOWS_PORT', 'USB001'),
    ],

];


