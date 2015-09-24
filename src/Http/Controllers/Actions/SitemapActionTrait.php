<?php

namespace Luminous\Http\Controllers\Actions;

use Carbon\Carbon;
use Luminous\Bridge\WP;

trait SitemapActionTrait
{
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
