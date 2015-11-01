<?php

return [

    // -------------------------------------------------------------------------
    // Encryption Key
    // -------------------------------------------------------------------------

    'key' => env('APP_KEY', 'SomeRandomString!!!'),
    'cipher' => 'AES-256-CBC',

    // -------------------------------------------------------------------------
    // Application Locale Configuration
    // -------------------------------------------------------------------------

    'locale' => env('APP_LOCALE', get_bloginfo('language')),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

];
