<?php

return [
    
    // -------------------------------------------------------------------------
    // Encryption Key
    // -------------------------------------------------------------------------

    'key' => env('APP_KEY', defined('NONCE_SALT') ? NONCE_SALT : 'SomeRandomString!!!'),
    'cipher' => 'AES-256-CBC',

    // -------------------------------------------------------------------------
    // Application Locale Configuration
    // -------------------------------------------------------------------------

    'locale' => env('APP_LOCALE', defined('WPLANG') ? WPLANG : 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

];
