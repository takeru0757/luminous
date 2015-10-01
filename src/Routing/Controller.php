<?php

namespace Luminous\Routing;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Create a new response.
     *
     * - Set `Cache-Control` header.
     * - Set `Etag` header and set 304 status code if not modified.
     * - Fix `Content-Type` header (add the charset) and protocol version.
     *
     * @uses \response()
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Contracts\View\View $view
     * @param array $headers
     * @return \Illuminate\Http\Response
     */
    protected function createResponse(Request $request, View $view, array $headers = [])
    {
        $response = response($view->render(), 200, $headers);

        if ($maxAge = $this->maxAge()) {
            $response->setCache(['private' => true, 'max_age' => $maxAge]);
        }

        // The method `Request::isNotModified()` contains `Request::setNotModified()`.
        // https://github.com/symfony/symfony/issues/13678
        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response->prepare($request);
    }

    /**
     * Get the `max-age`.
     *
     * @uses \env()
     *
     * @return int Returns `600` (`0` when debug).
     */
    protected function maxAge()
    {
        return env('APP_DEBUG', false) ? 0 : 600;
    }
}
