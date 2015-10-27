<?php

namespace Luminous\Bridge;

use Illuminate\Support\ServiceProvider;
use Luminous\Bridge\Post\DateArchive;
use Luminous\Bridge\Post\Paginator;

class BridgeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPostEntities();
        $this->registerTermEntities();

        $this->app->singleton('wp', function ($app) {
            DateArchive::timezoneResolver(function () {
                return $this->app['wp']->timezone();
            });

            Paginator::currentPathResolver(function () {
                return $this->app['request']->url();
            });

            Paginator::currentPageResolver(function ($pageName = 'page') {
                return $this->app['request']->input($pageName);
            });

            return new WP($app);
        });
    }

    /**
     * Register the post entities in the container.
     *
     * @return void
     */
    protected function registerPostEntities()
    {
        $this->app->bind(['Luminous\Bridge\Post\Entities\AttachmentEntity' => 'wp.post.entities.attachment']);
        $this->app->bind(['Luminous\Bridge\Post\Entities\HierarchicalEntity' => 'wp.post.entities.page']);
        $this->app->bind(['Luminous\Bridge\Post\Entities\NonHierarchicalEntity' => 'wp.post.entities.post']);
    }

    /**
     * Register the term entities in the container.
     *
     * @return void
     */
    protected function registerTermEntities()
    {
        $this->app->bind(['Luminous\Bridge\Term\Entities\HierarchicalEntity' => 'wp.term.entities.category']);
        $this->app->bind(['Luminous\Bridge\Term\Entities\NonHierarchicalEntity' => 'wp.term.entities.post_tag']);
    }
}
