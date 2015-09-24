<?php
require __DIR__.'/wp-helpers.php';

// -----------------------------------------------------------------------------
// Rewrite Rules
// -----------------------------------------------------------------------------

// Enable pretty permalinks
add_filter('pre_option_permalink_structure', function ($structure) {
    return get_template() === 'luminous' ? '/%postname%' : $structure;
});

// Replace rewite rules
add_filter('mod_rewrite_rules', function ($rules) {
    return get_template() === 'luminous' ? luminous_mod_rewrite_rules() : $rules;
});

// Force to reload "permalink_structure"
add_action('after_setup_theme', function () {
    global $wp_rewrite;
    $wp_rewrite->init();
});

// Activation
add_action('after_switch_theme', function () {
    add_action('admin_init', function () {
        flush_rewrite_rules();
    });
});

// Deactivation
add_action('switch_theme', function () {
    global $wp_rewrite;
    $wp_rewrite->init();
    $wp_rewrite->flush_rules();
});

// -----------------------------------------------------------------------------
// Apply Routes
// -----------------------------------------------------------------------------

// @link https://developer.wordpress.org/reference/functions/get_permalink/
add_filter('post_link', function ($permalink, $post, $leavename) {
    if (strpos($permalink, '?p=') !== false) {
        return $permalink;
    }
    $placeholder = $leavename ? '%postname%' : null;
    return luminous_post_url(app('wp')->post($post), $placeholder);
}, 10, 3);

// @link https://developer.wordpress.org/reference/functions/_get_page_link/
add_filter('_get_page_link', function ($permalink, $postId) {
    if ($permalink === home_url('/') || strpos($permalink, '?page_id=') !== false) {
        return $permalink;
    }
    $placeholder = strpos($permalink, '%pagename%') !== false ? '%pagename%' : null;
    return luminous_post_url(app('wp')->post($postId), $placeholder);
}, 10, 2);

// @link https://developer.wordpress.org/reference/functions/get_post_permalink/
add_filter('post_type_link', function ($permalink, $post, $leavename) {
    if (strpos($permalink, '?post_type=') !== false) {
        return $permalink;
    }
    $placeholder = $leavename ? "%{$post->post_type}%" : null;
    return luminous_post_url(app('wp')->post($post), $placeholder);
}, 10, 3);

// @link https://developer.wordpress.org/reference/functions/get_term_link/
add_filter('term_link', function ($termlink, $term, $taxonomy) {
    return luminous_archive_url(app('wp')->term($term));
}, 10, 3);
