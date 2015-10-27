<?php

namespace Luminous\Bridge;

use Illuminate\Support\ServiceProvider;
use Luminous\Bridge\Post\Builder as PostBuilder;
use Luminous\Bridge\Post\Paginator;
use Luminous\Bridge\Term\Builder as TermBuilder;

class BridgeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        WP::setContainer($this->app);

        $this->registerPostBuilder();
        $this->registerTermBuilder();

        $this->app->singleton('wp', function () {
            return new WP();
        });
    }

    /**
     * Register the post builder in the container.
     *
     * @return void
     */
    protected function registerPostBuilder()
    {
        $this->app->bind(['Luminous\Bridge\Post\Entities\AttachmentEntity' => 'wp.post.entities.attachment']);
        $this->app->bind(['Luminous\Bridge\Post\Entities\HierarchicalEntity' => 'wp.post.entities.page']);
        $this->app->bind(['Luminous\Bridge\Post\Entities\NonHierarchicalEntity' => 'wp.post.entities.post']);

        Paginator::currentPathResolver(function () {
            return $this->app['request']->url();
        });

        Paginator::currentPageResolver(function ($pageName = 'page') {
            return $this->app['request']->input($pageName);
        });

        $this->app->singleton(PostBuilder::class, function ($app) {
            return new PostBuilder($app);
        });
    }

    /**
     * Register the term builder in the container.
     *
     * @return void
     */
    protected function registerTermBuilder()
    {
        $this->app->bind(['Luminous\Bridge\Term\Entities\HierarchicalEntity' => 'wp.term.entities.category']);
        $this->app->bind(['Luminous\Bridge\Term\Entities\NonHierarchicalEntity' => 'wp.term.entities.post_tag']);

        $this->app->singleton(TermBuilder::class, function ($app) {
            return new TermBuilder($app);
        });
    }
}
