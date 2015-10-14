<?php

// $dotenv = new Dotenv\Dotenv(__DIR__.'/../');
// $dotenv->load();

// -----------------------------------------------------------------------------
// Create The Application
// -----------------------------------------------------------------------------

$app = new Luminous\Application(
    realpath(__DIR__.'/../')
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

$app->make('router')->scope(['namespace' => 'App\Http\Controllers'], function ($router) {
    require __DIR__.'/routes.php';
});

return $app;
