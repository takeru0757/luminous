<?php

namespace Luminous\Bridge;

use Illuminate\Support\ServiceProvider;
use Luminous\Bridge\Post\Builder as PostBuilder;

class BridgeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('wp', function () {
            return new WP();
        });

        $this->registerPaginationBindings();
        $this->registerPostBuilderBindings();
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    public function registerPaginationBindings()
    {
        $this->app->register('Illuminate\Pagination\PaginationServiceProvider');
    }

    /**
     * Register the post builder instance.
     *
     * @return void
     */
    public function registerPostBuilderBindings()
    {
        $this->app->singleton('wp.post.builder', function () {
            return new PostBuilder();
        });
    }
}
