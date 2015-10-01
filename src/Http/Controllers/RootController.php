<?php

namespace Luminous\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Luminous\Routing\Controller as BaseController;

class RootController extends BaseController
{
    /**
     * Handle requests for home.
     *
     * @uses \view()
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function home(Request $request)
    {
        return $this->createResponse($request, view('root.home'));
    }

    /**
     * Handle requests for '/robots.txt'.
     *
     * @uses \app()
     * @uses \view()
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function robots(Request $request)
    {
        $view = view(app('wp')->isPublic() ? 'root.robots' : 'root.robots-noindex');

        return $this->createResponse($request, $view, ['Content-Type' => 'text/plain']);
    }

    /**
     * Handle requests for '/sitemap.xml'.
     *
     * @uses \app()
     * @uses \abort()
     * @uses \view()
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sitemap(Request $request)
    {
        $wp = app('wp');

        if (! $wp->isPublic()) {
            abort(404);
        }

        $view = view('root.sitemap', [
            'appModified' => app('modified')->setTimezone($wp->timezone())
        ]);

        return $this->createResponse($request, $view, ['Content-Type' => 'text/xml']);
    }
}
