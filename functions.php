<?php

// -----------------------------------------------------------------------------
// Determine The Current Theme Path
// -----------------------------------------------------------------------------

$themePath = is_child_theme() ? STYLESHEETPATH : TEMPLATEPATH.'/luminous-scaffolding';

spl_autoload_register(function ($className) use ($themePath) {
    $className = ltrim($className, '\\');
    if (strpos($className, 'App\\') === 0) {
        $fileName = str_replace('\\', '/', substr($className, 4));
        require "{$themePath}/app/{$fileName}.php";
    }
});

if (! is_child_theme()) {
    require $themePath.'/functions.php';
}

// -----------------------------------------------------------------------------
// Environment Variables
// -----------------------------------------------------------------------------

// Configure APP_TIMEZONE as "UTC" for WordPress.
// @link https://github.com/WordPress/WordPress/blob/4.3/wp-settings.php#L43
putenv('APP_TIMEZONE='.date_default_timezone_get());

if (defined('WP_DEBUG') && WP_DEBUG) {
    putenv('APP_DEBUG=true');
}

// -----------------------------------------------------------------------------
// Create The Application
// -----------------------------------------------------------------------------

$app = require $themePath.'/bootstrap/app.php';

// Set the routing context.
$app->make('router')->setContext(home_url());

// Run the application.
if (is_wp()) {
    require __DIR__.'/wp-bridges.php';
} else {
    add_action('wp_loaded', function () use ($app) {
        $app->run();
        exit();
    });
}

unset($themePath, $app);
