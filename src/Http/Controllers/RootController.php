<?php

namespace Luminous\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Luminous\Bridge\Exceptions\EntityNotFoundException;
use Luminous\Routing\Controller as BaseController;

class RootController extends BaseController
{
    /**
     * Handle requests for home (and shortlinks).
     *
     * @uses \view()
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function home(Request $request)
    {
        if ($id = $request->query('p')) {
            return $this->shortlink($id);
        }

        return $this->createResponse($request, view('root.home'));
    }

    /**
     * Handle requests for shortlinks.
     *
     * @link https://developer.wordpress.org/reference/functions/wp_get_shortlink/
     *
     * @uses \app()
     * @uses \abort()
     * @uses \post_url()
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function shortlink($id)
    {
        try {
            $post = app('wp')->post($id);
        } catch (EntityNotFoundException $e) {
            abort(404);
        }

        return redirect(post_url($post));
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
