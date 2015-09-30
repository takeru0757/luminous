<?php

namespace Luminous\Http\Controllers;

use Carbon\Carbon;
use Luminous\Routing\Controller as BaseController;
use Luminous\Bridge\WP;

class RootController extends BaseController
{
    /**
     * Handle requests for home.
     *
     * @uses \view()
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function home()
    {
        return $this->createResponse(view('root.home'));
    }

    /**
     * Handle requests for '/robots.txt'.
     *
     * @uses \view()
     *
     * @param \Luminous\Bridge\WP $wp
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function robots(WP $wp)
    {
        $view = $wp->isPublic() ? 'root.robots' : 'root.robots-noindex';
        $content = view($view)->render();

        return response($content)->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * Handle requests for '/sitemap.xml'.
     *
     * @uses \abort()
     * @uses \view()
     *
     * @param \Luminous\Bridge\WP $wp
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sitemap(WP $wp)
    {
        if (! $wp->isPublic()) {
            abort(404);
        }

        $content = view('root.sitemap')->render();

        return response($content)->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
