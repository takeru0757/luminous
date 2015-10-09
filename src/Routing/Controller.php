<?php

namespace Luminous\Routing;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Abstract Controller Class
 *
 * This class is based on Laravel Lumen:
 *
 * - Copyright (c) Taylor Otwell
 * - Licensed under the MIT license
 * - {@link https://github.com/laravel/lumen-framework/blob/5.1/src/Routing/Controller.php}
 */
abstract class Controller
{
    use DispatchesJobs, ValidatesRequests;

    /**
     * The middleware defined on the controller.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Define a middleware on the controller.
     *
     * @param string $middleware
     * @param array $options
     * @return void
     */
    public function middleware($middleware, array $options = [])
    {
        $this->middleware[$middleware] = $options;
    }

    /**
     * Get the middleware for a given method.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $method
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMiddlewareForMethod(Request $request, $method)
    {
        $middleware = [];

        foreach ($this->middleware as $name => $options) {
            if (isset($options['only']) && ! in_array($method, (array) $options['only'])) {
                continue;
            }

            if (isset($options['except']) && in_array($method, (array) $options['except'])) {
                continue;
            }

            $middleware[] = $name;
        }

        return $middleware;
    }

    /**
     * Create a new response.
     *
     * - Set `Cache-Control` header.
     * - Set `Etag` header and set 304 status code if not modified.
     * - Fix `Content-Type` header (add the charset) and protocol version.
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

        return $response;
    }

    /**
     * Get the `max-age`.
     *
     * @return int Returns `600` (`0` when debug).
     */
    protected function maxAge()
    {
        return env('APP_DEBUG', false) ? 0 : 600;
    }
}
