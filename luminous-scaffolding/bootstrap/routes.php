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

$router->scope(['prefix' => 'posts', 'query' => ['postType' => 'post']], function ($router) {
    $router->any('[/{date:\d{4}(?:/\d{2}(?:/\d{2})?)?}]', [
        'query' => ['limit' => 10],
        'uses' => 'PostController@index',
        'as' => 'posts_url[post]',
    ]);

    $router->any('/category/{term:.+}', [
        'query' => ['limit' => 10, 'termType' => 'category'],
        'uses' => 'PostController@index',
        'as' => 'term_url[category]',
    ]);

    $router->any('/tag/{term}', [
        'query' => ['limit' => 10, 'termType' => 'post_tag'],
        'uses' => 'PostController@index',
        'as' => 'term_url[post_tag]',
    ]);

    $router->any('/{post:.+}', [
        'uses' => 'PostController@show',
        'as' => 'post_url[post]',
    ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$router->any('/{post:.+}', [
    'query' => ['postType' => 'page'],
    'uses' => 'PostController@show',
    'as' => 'post_url[page]',
]);
