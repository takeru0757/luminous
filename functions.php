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

// -----------------------------------------------------------------------------
// Create The Application
// -----------------------------------------------------------------------------

$app = require $themePath.'/bootstrap/app.php';

if (is_wp()) {
    require __DIR__.'/wp-bridges.php';
} else {
    add_action('wp_loaded', function () use ($app) {
        $app->run();
        exit();
    });
}

unset($themePath, $app);
