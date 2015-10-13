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
    use ValidatesRequests;

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
     *
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
     * Get the `max-age`.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $method
     * @return int Returns `600` (`0` when debug).
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function maxAge(Request $request, $method)
    {
        return env('APP_DEBUG', false) ? 0 : 600;
    }
}
