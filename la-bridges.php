<?php

// -----------------------------------------------------------------------------
// Rewrite Rules
// -----------------------------------------------------------------------------

// Enable pretty permalinks
add_filter('pre_option_permalink_structure', function () {
    return '/%postname%';
});

// Activation
add_action('after_switch_theme', function () {
    add_action('admin_init', function () {
        global $wp_rewrite;
        $old = $wp_rewrite->permalink_structure;
        $wp_rewrite->set_permalink_structure('/%postname%');
        $wp_rewrite->flush_rules();
        $wp_rewrite->set_permalink_structure($old); // restore
    }, 9999);
});

// Deactivation
add_action('switch_theme', 'flush_rewrite_rules');

// Fix Stylesheet URI
add_filter('stylesheet_directory_uri', function ($uri) {
    return $uri.(!is_child_theme() ? '/luminous-scaffolding' : '');
});

// Filter rewrite rules
add_filter('mod_rewrite_rules', function ($rules) {
    if (get_template() !== 'luminous') {
        return $rules;
    }

    $rewriteBase = parse_url(home_url(), PHP_URL_PATH) ?: '/';
    $uploads = ltrim(parse_url(wp_upload_dir()['baseurl'], PHP_URL_PATH), '/');
    $assets  = ltrim(parse_url(get_stylesheet_directory_uri().'/assets', PHP_URL_PATH), '/');

    $format = <<<EOT

# BEGIN Luminous
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews
    </IfModule>

    RewriteEngine On
    RewriteBase %s

    # Redirect Trailing Slashes
    RewriteCond %%{REQUEST_FILENAME} !-f
    RewriteCond %%{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)/$ /$1 [L,R=301]

    # Hide Using WordPress
    RewriteCond %%{REQUEST_FILENAME} !-f
    RewriteCond %%{REQUEST_FILENAME} !-d
    RewriteRule ^uploads/(.*)$ %s/$1 [L]
    RewriteCond %%{REQUEST_FILENAME} !-f
    RewriteCond %%{REQUEST_FILENAME} !-d
    RewriteRule ^assets/(.*)$ %s/$1 [L]
</IfModule>
# END Luminous

EOT;

    return sprintf($format, $rewriteBase, $uploads, $assets)."\n".$rules;
});

// -----------------------------------------------------------------------------
// Apply Routes
// -----------------------------------------------------------------------------

$route = function ($post, $placeholder) {
    $entity = app('wp')->post($post);

    $uri = route($entity->type->name);
    $uri = home_url(substr($uri, strlen(app('request')->root())));

    return preg_replace_callback('/\{(.*?)(:.*?)?(\{[0-9,]+\})?\}/', function ($m) use ($entity, $placeholder) {
        return $placeholder && in_array($m[1], ['slug', 'path']) ? $placeholder : $entity->parameter($m[1]);
    }, $uri);
};

// @link https://developer.wordpress.org/reference/functions/get_permalink/
add_filter('post_link', function ($permalink, $post, $leavename) use ($route) {
    if (strpos($permalink, '?p=') !== false) {
        return $permalink;
    }
    return $route($post, $leavename ? '%postname%' : null);
}, 10, 3);

// @link https://developer.wordpress.org/reference/functions/_get_page_link/
add_filter('_get_page_link', function ($permalink, $postId) use ($route) {
    if ($permalink === home_url('/') || strpos($permalink, '?page_id=') !== false) {
        return $permalink;
    }
    return $route($postId, strpos($permalink, '%pagename%') !== false ? '%pagename%' : null);
}, 10, 2);

// @link https://developer.wordpress.org/reference/functions/get_post_permalink/
add_filter('post_type_link', function ($permalink, $post, $leavename) use ($route) {
    if (strpos($permalink, '?post_type=') !== false) {
        return $permalink;
    }
    return $route($post, $leavename ? "%{$post->post_type}%" : null);
}, 10, 3);

unset($route);
