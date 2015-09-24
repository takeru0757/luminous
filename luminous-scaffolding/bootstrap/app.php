<?php

// Dotenv::load(__DIR__.'/../');

// -----------------------------------------------------------------------------
// Create The Application
// -----------------------------------------------------------------------------

// Configure APP_TIMEZONE as "UTC" for WordPress.
// @link https://github.com/WordPress/WordPress/blob/4.3/wp-settings.php#L43
putenv('APP_TIMEZONE='.date_default_timezone_get());

$app = new Luminous\Application(
    realpath(__DIR__.'/../')
);

// -----------------------------------------------------------------------------
// Configuration
// -----------------------------------------------------------------------------

// $app->make('config')->set('assets', [
//     'manifest' => base_path('public/assets/rev-manifest.json'),
//     'prefix' => '/assets',
// ]);

// -----------------------------------------------------------------------------
// Register Container Bindings
// -----------------------------------------------------------------------------

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Luminous\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Luminous\Console\Kernel::class
);

// -----------------------------------------------------------------------------
// Register Middleware
// -----------------------------------------------------------------------------

// $theme->middleware([
//     // Illuminate\Cookie\Middleware\EncryptCookies::class,
//     // Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
//     // Illuminate\Session\Middleware\StartSession::class,
//     // Illuminate\View\Middleware\ShareErrorsFromSession::class,
//     // Laravel\Lumen\Http\Middleware\VerifyCsrfToken::class,
// ]);

// $theme->routeMiddleware([

// ]);

// -----------------------------------------------------------------------------
// Load Routes
// -----------------------------------------------------------------------------

$app->group(['namespace' => 'Luminous\Http\Controllers'], function ($app) {
    require __DIR__.'/routes.php';
});

return $app;
