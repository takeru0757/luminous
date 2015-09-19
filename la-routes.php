<?php

// -----------------------------------------------------------------------------
// Utility Responces
// -----------------------------------------------------------------------------

// $app->group(['namespace' => 'App\Http'], function ($app) {
//     $app->get('sitemap.xml', 'Controller@sitemap');
//     $app->get('robots.txt', 'Controller@robots');
// });

// -----------------------------------------------------------------------------
// for Post
// -----------------------------------------------------------------------------

$app->group(['prefix' => 'info', 'namespace' => 'App\Http'], function ($app) {
    $postType = 'post';
    $limit = 10;

    $app->any('{year:\d{4}}/{month:\d{2}}/{day:\d{2}}/{slug}', [
        'query' => ['postType' => $postType],
        'uses' => 'Controller@show',
        'as' => 'post',
    ]);

    $app->any('{year:\d{4}}/{month:\d{2}}/{day:\d{2}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'Controller@archive',
    ]);

    $app->any('{year:\d{4}}/{month:\d{2}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'Controller@archive',
    ]);

    $app->any('{year:\d{4}}', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'Controller@archive',
    ]);

    $app->any('/', [
        'query' => ['postType' => $postType, 'limit' => $limit],
        'uses' => 'Controller@archive',
        'as' => 'post_archive',
    ]);

    // $app->any('category/{term:.+}', [
    //     'query' => ['postType' => $postType, 'limit' => $limit, 'termType' => 'category'],
    //     'uses' => 'Controller@archive',
    // ]);
    //
    // $app->any('tag/{term}', [
    //     'query' => ['postType' => $postType, 'limit' => $limit, 'termType' => 'post_tag'],
    //     'uses' => 'Controller@archive',
    // ]);
});

// -----------------------------------------------------------------------------
// for Page
// -----------------------------------------------------------------------------

$app->any('{path:.+}', [
    'query' => ['postType' => 'page'],
    'uses' => 'App\Http\Controller@show',
    'as' => 'page',
]);

// -----------------------------------------------------------------------------
// for Home
// -----------------------------------------------------------------------------

$app->any('/', [
    'uses' => 'App\Http\Controller@home',
    'as' => 'home',
]);
