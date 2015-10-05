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

$router->any('/', ['uses' => 'RootController@home', 'as' => 'home']);

$router->get('/sitemap.xml', 'RootController@sitemap');
$router->get('/robots.txt', 'RootController@robots');

// -----------------------------------------------------------------------------
// for Post
// -----------------------------------------------------------------------------

$router->scope(['prefix' => 'posts', 'query' => ['postType' => 'post', 'limit' => 10]], function ($router) {
    $router->any('[/{date:\d{4}(?:/\d{2}(?:/\d{2})?)?}]', [
        'uses' => 'PostController@archive',
        'as' => 'archive_url@post',
    ]);

    $router->any('/category/{term:.+}', [
        'query' => ['termType' => 'category'],
        'uses' => 'PostController@archive',
        'as' => 'term_url@category',
    ]);

    $router->any('/tag/{term}', [
        'query' => ['termType' => 'post_tag'],
        'uses' => 'PostController@archive',
        'as' => 'term_url@post_tag',
    ]);

    $router->any('/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}/{path}', [
        'uses' => 'PostController@post',
        'as' => 'post_url@post',
    ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$router->any('/{path:.+}', [
    'query' => ['postType' => 'page'],
    'uses' => 'PostController@post',
    'as' => 'post_url@page',
]);
