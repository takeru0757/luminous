<?php

namespace Luminous\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Luminous\Bridge\Exceptions\EntityNotFoundException;

class RootController extends Controller
{
    /**
     * Handle requests for home (and shortlinks).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function home(Request $request)
    {
        if ($id = $request->query('p')) {
            return $this->shortlink($id);
        }

        return view('root.home');
    }

    /**
     * Handle requests for shortlinks.
     *
     * @link https://developer.wordpress.org/reference/functions/wp_get_shortlink/
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function shortlink($id)
    {
        try {
            $post = $this->wp->post((int) $id);
        } catch (EntityNotFoundException $e) {
            return redirect('/');
        }

        return redirect($post);
    }

    /**
     * Handle requests for '/robots.txt'.
     *
     * @return \Illuminate\Http\Response
     */
    public function robots()
    {
        $view = view($this->wp->isPublic() ? 'root.robots' : 'root.robots-noindex');

        return response($view, 200, ['content-type' => 'text/plain']);
    }

    /**
     * Handle requests for '/sitemap.xml'.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sitemap()
    {
        if (! $this->wp->isPublic()) {
            abort(404);
        }

        $view = view('root.sitemap', [
            'appModified' => Carbon::createFromTimeStamp(app('modified'), $this->wp->timezone()),
        ]);

        return response($view, 200, ['content-type' => 'text/xml']);
    }
}
