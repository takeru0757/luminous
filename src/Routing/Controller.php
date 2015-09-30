<?php

namespace Luminous\Routing;

use Exception;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Create the response with headers.
     *
     * @uses \app()
     * @uses \response()
     *
     * @param \Illuminate\Contracts\View\View $view
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function createResponse(View $view)
    {
        $modified = app('wp')->lastModified();
        $expires = $this->expires();

        if ($expires > 0 && $this->isNotModified($modified)) {
            $response = response('', 304);
        } else {
            $response = response($view->render(), 200);
        }

        $response->header('Cache-Control', "private,max-age={$expires}");
        $response->header('Last-Modified', $modified->toRfc1123String());

        return $response;
    }

    /**
     * Determine if the content was modified.
     *
     * @uses \app()
     *
     * @param \Carbon\Carbon $modified
     * @return bool
     */
    protected function isNotModified(Carbon $modified)
    {
        if (! $header = app('request')->header('If-Modified-Since')) {
            return false;
        }

        try {
            return Carbon::parse($header)->gte($modified);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get expires in seconds.
     *
     * @uses \env()
     *
     * @return int Returns `600` (`0` when debug).
     */
    protected function expires()
    {
        return env('APP_DEBUG', false) ? 0 : 600;
    }
}
