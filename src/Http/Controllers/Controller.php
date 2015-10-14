<?php

namespace Luminous\Http\Controllers;

use Luminous\Bridge\WP;
use Luminous\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * The WP instance.
     *
     * @var \Luminous\Bridge\WP
     */
    protected $wp;

    /**
     * Create a new controller instance.
     *
     * @param \Luminous\Bridge\WP $wp
     * @return void
     */
    public function __construct(WP $wp)
    {
        $this->wp = $wp;
    }
}
