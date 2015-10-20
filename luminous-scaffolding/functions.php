<?php
/**
 * This file is loaded BEFORE 'luminous/functions.php'.
 *
 * @link https://codex.wordpress.org/Child_Themes
 */

// Theme Features
// @link https://codex.wordpress.org/Theme_Features
add_action('after_setup_theme', function () {
    add_theme_support('html5', ['comment-list', 'comment-form', 'search-form', 'gallery', 'caption']);
    add_theme_support('post-thumbnails');
    // set_post_thumbnail_size(50, 50, true);
});

// Change the label of "Post" post type.
// @link http://wordpress.stackexchange.com/questions/9211/changing-admin-menu-labels
// add_filter('post_type_labels_post', function ($labels) {
//     $p = _x('Posts', 'post type general name');
//     $s = _x('Post', 'post type singular name');
//
//     return (object) array_map(function ($label) use ($p, $s) {
//         return str_replace([$p, $s], ['Articles', 'Article'], $label);
//     }, (array) $labels);
// });
// add_action('admin_menu', function () {
//     global $menu;
//     global $submenu;
//     $menu[5][0] = 'Articles';
//     $submenu['edit.php'][5][0] = 'All Articles';
// });
