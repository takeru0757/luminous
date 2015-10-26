<?php

namespace Luminous\Http\Controllers;

use Luminous\Application;
use Luminous\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * The application instance.
     *
     * @var \Luminous\Application
     */
    protected $app;

    /**
     * The WP instance.
     *
     * @var \Luminous\Bridge\WP
     */
    protected $wp;

    /**
     * Create a new controller instance.
     *
     * @param \Luminous\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->wp = $this->app['wp'];
    }
}
