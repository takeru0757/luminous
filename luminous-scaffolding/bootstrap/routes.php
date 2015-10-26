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

$router->scope(['prefix' => 'posts', 'parameters' => ['post_type' => 'post']], function ($router) {
    $router->any('/', [
        'parameters' => ['limit' => 10],
        'uses' => 'PostController@posts',
    ]);

    $router->any('/{post}', [
        'parameters' => ['post' => '{date_year:\d{4}}/{date_month:\d{2}}/{date_day:\d{2}}/{slug}'],
        'uses' => 'PostController@post',
    ]);

    $router->any('/{date}', [
        'parameters' => ['limit' => 10, 'date' => '{path:\d{4}(?:/\d{2}(?:/\d{2})?)?}'],
        'uses' => 'PostController@posts',
    ]);

    $router->any('/category/{term}', [
        'parameters' => ['limit' => 10, 'term_type' => 'category', 'term' => '{path:.+}'],
        'uses' => 'PostController@posts',
    ]);

    $router->any('/tag/{term}', [
        'parameters' => ['limit' => 10, 'term_type' => 'post_tag', 'term' => '{slug}'],
        'uses' => 'PostController@posts',
    ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$router->any('/{post}', [
    'parameters' => ['post_type' => 'page', 'post' => '{path:.+}'],
    'uses' => 'PostController@post',
]);
