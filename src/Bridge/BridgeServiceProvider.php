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
        WP::setPostBuilder($this->app['wp.post']);
        WP::setTermBuilder($this->app['wp.term']);

        $wp = $this->app['wp'];

        $site = (object) [
            'name'          => $wp->option('blogname'),
            'description'   => $wp->option('blogdescription'),
            'url'           => $wp->option('home'),
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
            return new WP();
        });
    }

    /**
     * Register the post builder instance in the container.
     *
     * @return void
     */
    public function registerPostBuilder()
    {
        $this->app->bind('wp.post.entities.attachment', 'Luminous\Bridge\Post\Entities\AttachmentEntity');
        $this->app->bind('wp.post.entities.page', 'Luminous\Bridge\Post\Entities\PageEntity');
        $this->app->bind('wp.post.entities.post', 'Luminous\Bridge\Post\Entities\PostEntity');

        $this->app->singleton('wp.post', function ($app) {
            return new PostBuilder($app);
        });
    }

    /**
     * Register the term builder instance in the container.
     *
     * @return void
     */
    public function registerTermBuilder()
    {
        $this->app->bind('wp.term.entities.category', 'Luminous\Bridge\Term\Entities\CategoryEntity');
        $this->app->bind('wp.term.entities.post_tag', 'Luminous\Bridge\Term\Entities\PostTagEntity');

        $this->app->singleton('wp.term', function ($app) {
            return new TermBuilder($app);
        });
    }
}
