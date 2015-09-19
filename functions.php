<?php

$composer = require __DIR__ . '/vendor/autoload.php';

// -----------------------------------------------------------------------------
// Determine The Current Theme Path
// -----------------------------------------------------------------------------

$themePath = is_child_theme() ? STYLESHEETPATH : TEMPLATEPATH.'/luminous-scaffolding';
$composer->addPsr4('App\\', $themePath.'/app');

// -----------------------------------------------------------------------------
// Create The Application
// -----------------------------------------------------------------------------

$app = require $themePath.'/la-bootstrap.php';

/**
 * @SuppressWarnings(PHPMD.ExitExpression)
 */
if (! function_exists('luminous_hook_wp_loaded')) {
    /**
     * Run The Application
     *
     * @return void
     */
    function luminous_hook_wp_loaded()
    {
        app()->run();
        exit();
    }
}

if (is_wp()) {
    require base_path('la-bridges.php');
} else {
    add_action('wp_loaded', 'luminous_hook_wp_loaded');
}

unset($composer, $themePath, $app);
