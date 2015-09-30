<?php

// -----------------------------------------------------------------------------
// for Error (Debug)
// -----------------------------------------------------------------------------

if (env('APP_DEBUG', false)) {
    $app->get('/404', function () {
        abort(404);
    });
    $app->get('/500', function () {
        abort(500);
    });
}

// -----------------------------------------------------------------------------
// for Root
// -----------------------------------------------------------------------------

$app->any('/', ['uses' => 'RootController@home', 'as' => 'home']);

$app->get('/sitemap.xml', 'RootController@sitemap');
$app->get('/robots.txt', 'RootController@robots');

// -----------------------------------------------------------------------------
// for Post
// -----------------------------------------------------------------------------

$app->group(['prefix' => 'posts', 'namespace' => 'Luminous\Http\Controllers'], function ($app) {
    $postType = 'post';
    $limit = 10;

    $app->any('/', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive_url@post',
    ]);

    $app->any('/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive_url@post[daily]',
    ]);

    $app->any('/{year:\d{4}}/{month:\d{2}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive_url@post[monthly]',
    ]);

    $app->any('/{year:\d{4}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive_url@post[yearly]',
    ]);

    $app->any('/category/{term:.+}', [
        'query' => ['postType' => $postType, 'limit' => $limit, 'termType' => 'category'],
        'uses' => 'PostController@archive',
        'as' => 'term_url@category',
    ]);

    $app->any('/tag/{term}', [
        'query' => ['postType' => $postType, 'limit' => $limit, 'termType' => 'post_tag'],
        'uses' => 'PostController@archive',
        'as' => 'term_url@post_tag',
    ]);

    $app->any('/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}/{path}', [
        'query' => ['postType' => $postType],
        'uses' => 'PostController@post',
        'as' => 'post_url@post',
    ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$app->any('/{path:.+}', [
    'query' => ['postType' => 'page'],
    'uses' => 'PostController@post',
    'as' => 'post_url@page',
]);
