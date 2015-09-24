<?php

namespace Luminous\Http\Controllers\Actions;

use Luminous\Bridge\WP;

trait RobotsActionTrait
{
    /**
     * Handle requests for '/robots.txt'.
     *
     * @param \Luminous\Bridge\WP $wp
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function robots(WP $wp)
    {
        if (! $wp->isPublic()) {
            $view = view('root.robots-noindex');
        } else {
            $view = view('root.robots');
        }

        return response($view->render())->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
