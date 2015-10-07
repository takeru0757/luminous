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

$router->scope(['prefix' => 'posts', 'query' => ['post_type' => 'post']], function ($router) {
    $router->any('[/{date_year:\d{4}}[/{date_month:\d{2}}[/{date_day:\d{2}}]]]', [
        'query' => ['limit' => 10],
        'uses' => 'PostController@index',
        'as' => 'posts_url[post]',
    ]);

    $router->any('/category/{term_path:.+}', [
        'query' => ['limit' => 10, 'term_type' => 'category'],
        'uses' => 'PostController@index',
        'as' => 'term_url[category]',
    ]);

    $router->any('/tag/{term_path}', [
        'query' => ['limit' => 10, 'term_type' => 'post_tag'],
        'uses' => 'PostController@index',
        'as' => 'term_url[post_tag]',
    ]);

    $router->any('/{post_date_year:\d{4}}/{post_date_month:\d{2}}/{post_date_day:\d{2}}/{post_path}', [
        'uses' => 'PostController@show',
        'as' => 'post_url[post]',
    ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$router->any('/{post_path:.+}', [
    'query' => ['post_type' => 'page'],
    'uses' => 'PostController@show',
    'as' => 'post_url[page]',
]);
