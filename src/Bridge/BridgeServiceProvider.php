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
            $wp->setPostBuilder($app['Luminous\Bridge\Post\Builder']);
            $wp->setTermBuilder($app['Luminous\Bridge\Term\Builder']);

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
        $this->app->bind(['Luminous\Bridge\Post\Entities\AttachmentEntity' => 'wp.post.entities.attachment']);
        $this->app->bind(['Luminous\Bridge\Post\Entities\HierarchicalEntity' => 'wp.post.entities.page']);
        $this->app->bind(['Luminous\Bridge\Post\Entities\NonHierarchicalEntity' => 'wp.post.entities.post']);

        $this->app->singleton('Luminous\Bridge\Post\Builder', function ($app) {
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
        $this->app->bind(['Luminous\Bridge\Term\Entities\HierarchicalEntity' => 'wp.term.entities.category']);
        $this->app->bind(['Luminous\Bridge\Term\Entities\NonHierarchicalEntity' => 'wp.term.entities.post_tag']);

        $this->app->singleton('Luminous\Bridge\Term\Builder', function ($app) {
            return new TermBuilder($app);
        });
    }
}
