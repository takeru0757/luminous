<?php

// -----------------------------------------------------------------------------
// Utility Responces
// -----------------------------------------------------------------------------

$app->get('sitemap.xml', 'RootController@sitemap');
$app->get('robots.txt', 'RootController@robots');

// -----------------------------------------------------------------------------
// for Post
// -----------------------------------------------------------------------------

$app->group(['prefix' => 'posts', 'namespace' => 'Luminous\Http\Controllers'], function ($app) {
    $postType = 'post';
    $limit = 10;

    $app->any('/', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive:post',
    ]);

    $app->any('/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive:post[daily]',
    ]);

    $app->any('/{year:\d{4}}/{month:\d{2}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive:post[monthly]',
    ]);

    $app->any('/{year:\d{4}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'PostController@archive',
        'as' => 'archive:post[yearly]',
    ]);

    $app->any('/category/{term:.+}', [
        'query' => ['postType' => $postType, 'limit' => $limit, 'termType' => 'category'],
        'uses' => 'PostController@archive',
        'as' => 'archive:term:category',
    ]);

    $app->any('/tag/{term}', [
        'query' => ['postType' => $postType, 'limit' => $limit, 'termType' => 'post_tag'],
        'uses' => 'PostController@archive',
        'as' => 'archive:term:post_tag',
    ]);

    $app->any('/{year:\d{4}}/{month:\d{2}}/{day:\d{2}}/{path}', [
        'query' => ['postType' => $postType],
        'uses' => 'PostController@post',
        'as' => 'post:post',
    ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$app->any('/{path:.+}', [
    'query' => ['postType' => 'page'],
    'uses' => 'PostController@post',
    'as' => 'post:page',
]);

// -----------------------------------------------------------------------------
// for Home
// -----------------------------------------------------------------------------

$app->any('/', [
    'uses' => 'PostController@home',
    'as' => 'home',
]);
