<?php

require __DIR__.'/wp-helpers.php';

use Luminous\Bridge\WP;
use Luminous\Bridge\Post\Entities\AttachmentEntity;

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
    $parameters = $placeholder ? ['post__path' => $placeholder, 'post__slug' => $placeholder] : [];
    return post_url(app('wp')->post($post), $parameters, true);
}, 10, 3);

// @link https://developer.wordpress.org/reference/functions/_get_page_link/
add_filter('_get_page_link', function ($permalink, $postId) {
    if ($permalink === home_url() || strpos($permalink, '?page_id=') !== false) {
        return $permalink;
    }
    $placeholder = strpos($permalink, '%pagename%') !== false ? '%pagename%' : null;
    $parameters = $placeholder ? ['post__path' => $placeholder, 'post__slug' => $placeholder] : [];
    return post_url(app('wp')->post($postId), $parameters, true);
}, 10, 2);

// @link https://developer.wordpress.org/reference/functions/get_post_permalink/
add_filter('post_type_link', function ($permalink, $post, $leavename) {
    if (strpos($permalink, '?post_type=') !== false) {
        return $permalink;
    }
    $placeholder = $leavename ? "%{$post->post_type}%" : null;
    $parameters = $placeholder ? ['post__path' => $placeholder, 'post__slug' => $placeholder] : [];
    return post_url(app('wp')->post($post), $parameters, true);
}, 10, 3);

// @link https://developer.wordpress.org/reference/functions/get_term_link/
add_filter('term_link', function ($termlink, $term, $taxonomy) {
    $term = app('wp')->term($term, $taxonomy);
    return posts_url($term->type->post_type, $term->forUrl(), true);
}, 10, 3);

// @link https://developer.wordpress.org/reference/hooks/wp_get_attachment_url/
add_filter('wp_get_attachment_url', function ($url, $postId) {
    return url(AttachmentEntity::attachmentPath($url), true);
}, 10, 2);

// -----------------------------------------------------------------------------
// Last Modified
// -----------------------------------------------------------------------------

foreach (['save_post', 'deleted_post'] as $_action) {
    add_action($_action, function ($id) {
        if (wp_is_post_revision($id)) {
            return;
        }
        update_option(WP::OPTION_LAST_MODIFIED, time());
    });
}

foreach (['comment_post', 'edit_comment', 'deleted_comment'] as $_action) {
    add_action($_action, function ($id, $approved = null) {
        if (! is_null($approved) && $approved !== 1) {
            return;
        }
        update_option(WP::OPTION_LAST_MODIFIED, time());
    });
}

foreach (['created_term', 'edited_term', 'delete_term'] as $_action) {
    add_action($_action, function ($id) {
        update_option(WP::OPTION_LAST_MODIFIED, time());
    });
}

// Activation
add_action('after_switch_theme', function () {
    update_option(WP::OPTION_LAST_MODIFIED, time());
});

// Deactivation
add_action('switch_theme', function () {
    delete_option(WP::OPTION_LAST_MODIFIED);
});
