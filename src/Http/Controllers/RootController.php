<?php

namespace Luminous\Http\Controllers;

use Carbon\Carbon;
use Laravel\Lumen\Routing\Controller as BaseController;
use Luminous\Bridge\WP;

class RootController extends BaseController
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

    /**
     * Handle requests for '/sitemap.xml'.
     *
     * @param \Luminous\Bridge\WP $wp
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sitemap(WP $wp)
    {
        if (! $wp->isPublic()) {
            abort(404);
        }

        if ($latest = $wp->posts('any')->orderBy('updated_at', 'desc')->first()) {
            $modified = $latest->updated_at;
        } else {
            $modified = Carbon::now();
        }

        $types = $wp->postTypes();

        $view = view('root.sitemap', compact('modified', 'types'));
        return response($view->render())->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
