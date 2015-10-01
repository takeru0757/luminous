<?php

namespace Luminous\Bridge;

use Illuminate\Support\ServiceProvider;
use Luminous\Bridge\Post\Builder as PostBuilder;
use Luminous\Bridge\Term\Builder as TermBuilder;

class BridgeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $wp = $this->app['wp'];

        $site = (object) [
            'name'          => $wp->option('blogname'),
            'description'   => $wp->option('blogdescription'),
            'url'           => $wp->option('home'),
            'dateFormat'    => $wp->option('date_format'),
            'timeFormat'    => $wp->option('time_format'),
        ];

        $this->app['view']->share(compact('wp', 'site'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPostBuilder();
        $this->registerTermBuilder();

        $this->app->singleton('wp', function ($app) {
            $app->register('Illuminate\Pagination\PaginationServiceProvider');

            $wp = new WP();
            $wp->setPostBuilder($app['wp.post']);
            $wp->setTermBuilder($app['wp.term']);

            return $wp;
        });
    }

    /**
     * Register the post builder instance in the container.
     *
     * @return void
     */
    protected function registerPostBuilder()
    {
        $this->app->bind('wp.post.entities.attachment', 'Luminous\Bridge\Post\Entities\AttachmentEntity');
        $this->app->bind('wp.post.entities.hierarchical', 'Luminous\Bridge\Post\Entities\HierarchicalEntity');
        $this->app->bind('wp.post.entities.nonhierarchical', 'Luminous\Bridge\Post\Entities\NonHierarchicalEntity');

        $this->app->singleton('wp.post', function ($app) {
            return new PostBuilder($app);
        });
    }

    /**
     * Register the term builder instance in the container.
     *
     * @return void
     */
    protected function registerTermBuilder()
    {
        $this->app->bind('wp.term.entities.hierarchical', 'Luminous\Bridge\Term\Entities\HierarchicalEntity');
        $this->app->bind('wp.term.entities.nonhierarchical', 'Luminous\Bridge\Term\Entities\NonHierarchicalEntity');

        $this->app->singleton('wp.term', function ($app) {
            return new TermBuilder($app);
        });
    }
}
