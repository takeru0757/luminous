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
        $this->app->singleton('wp', function ($app) {
            $app->register('Illuminate\Pagination\PaginationServiceProvider');
            return new WP();
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $view = $this->app->make('view');

        $view->share('wp', $wp = $this->app->make('wp'));
        $view->share('site', (object) [
            'name'          => $wp->option('blogname'),
            'description'   => $wp->option('blogdescription'),
            'url'           => $wp->option('home'),
        ]);
    }
}
