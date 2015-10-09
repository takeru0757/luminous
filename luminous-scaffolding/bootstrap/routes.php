<?php

// -----------------------------------------------------------------------------
// for Error (Debug)
// -----------------------------------------------------------------------------

if (env('APP_DEBUG', false)) {
    $router->get('/404', function () {
        abort(404);
    });
    $router->get('/500', function () {
        abort(500);
    });
}

// -----------------------------------------------------------------------------
// for Root
// -----------------------------------------------------------------------------

$router->get('/', 'RootController@home');

$router->get('/sitemap.xml', 'RootController@sitemap');
$router->get('/robots.txt', 'RootController@robots');

// -----------------------------------------------------------------------------
// for Post
// -----------------------------------------------------------------------------

$router->scope(['prefix' => 'posts', 'query' => ['post_type' => 'post']], function ($router) {
    $router->any('/', [
        'query' => ['limit' => 10],
        'uses' => 'PostController@index',
    ]);

    $router->any('/{post}', [
        'query' => ['post' => '{date_year:\d{4}}/{date_month:\d{2}}/{date_day:\d{2}}/{slug}'],
        'uses' => 'PostController@show',
    ]);

    $router->any('/{archive}', [
        'query' => ['limit' => 10, 'archive' => '{path:\d{4}(?:/\d{2}(?:/\d{2})?)?}'],
        'uses' => 'PostController@index',
    ]);

    $router->any('/category/{term}', [
        'query' => ['limit' => 10, 'term_type' => 'category', 'term' => '{path:.+}'],
        'uses' => 'PostController@index',
    ]);

    $router->any('/tag/{term}', [
        'query' => ['limit' => 10, 'term_type' => 'post_tag', 'term' => '{slug}'],
        'uses' => 'PostController@index',
    ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$router->any('/{post}', [
    'query' => ['post_type' => 'page', 'post' => '{path:.+}'],
    'uses' => 'PostController@show',
]);
