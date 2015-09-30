<?php

namespace Luminous\Routing;

use DateTime;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Create the response with headers.
     *
     * @uses \response()
     *
     * @param \Illuminate\Contracts\View\View $view
     * @param \DateTime $modified
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function createResponse(View $view, DateTime $modified = null)
    {
        $response = response($view->render(), 200);
        $expires = $this->expires();

        $response->header('Cache-Control', "private,max-age={$expires}");
        $response->header('Expires', Carbon::now()->addSeconds($expires)->format(DateTime::RFC1123));

        if ($modified) {
            $response->header('Last-Modified', $modified->format(DateTime::RFC1123));
        }

        return $response;
    }

    /**
     * Get expires in seconds.
     *
     * @uses \env()
     *
     * @return int Returns `0` when debug, otherwise `0`.
     */
    protected function expires()
    {
        return env('APP_DEBUG', false) ? 0 : 600;
    }
}
