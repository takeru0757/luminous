<?php

namespace Luminous\Routing;

use Closure;
use Error;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Luminous\Application;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Dispatcher
{
    /**
     * The application instance.
     *
     * @var \Luminous\Application
     */
    protected $app;

    /**
     * The router instance.
     *
     * @var \Luminous\Routing\Router
     */
    protected $router;

    /**
     * The current route being dispatched.
     *
     * @var array
     */
    protected $currentRoute;

    /**
     * All of the global middleware for the application.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * All of the route specific middleware short-hands.
     *
     * @var array
     */
    protected $routeMiddleware = [];

    /**
     * Create a new dispatcher instance.
     *
     * @param \Luminous\Application $app
     * @param \Luminous\Routing\Router $router
     * @return void
     */
    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    /**
     * Get the current route.
     *
     * @return array
     */
    public function currentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * Add new middleware to the application.
     *
     * @param array $middleware
     * @return void
     */
    public function middleware(array $middleware)
    {
        $this->middleware = array_unique(array_merge($this->middleware, $middleware));
    }

    /**
     * Define the route middleware for the application.
     *
     * @param array $middleware
     * @return void
     */
    public function routeMiddleware(array $middleware)
    {
        $this->routeMiddleware = array_merge($this->routeMiddleware, $middleware);
    }

    /**
     * Dispatch the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        try {
            $response = $this->handleRequestWithMiddleware($request);
        } catch (Exception $e) {
            $response = $this->handleException($request, $e);
        } catch (Throwable $e) {
            $response = $this->handleException($request, $e);
        }

        // The method `Request::isNotModified()` contains `Request::setNotModified()`.
        // https://github.com/symfony/symfony/issues/13678
        $response->setEtag(md5($response->getContent()));
        $response->isNotModified($request);

        return $response->prepare($request);
    }


    /**
     * Call the terminable middleware.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @return void
     */
    public function terminate(Request $request, SymfonyResponse $response)
    {
        foreach ($this->middleware as $middleware) {
            if (method_exists($instance = $this->app->make($middleware), 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }

    /**
     * Handle the request with middleware.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleRequestWithMiddleware(Request $request)
    {
        try {
            return $this->sendThroughPipeline($this->middleware, $request, function () use ($request) {
                return $this->handleRequest($request);
            });
        } catch (HttpExceptionInterface $e) {
            return $this->handleHttpException($e);
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Handle the request.
     *
     * @uses \FastRoute\simpleDispatcher()
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function handleRequest(Request $request)
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
            foreach ($this->router->routes() as $route) {
                $r->addRoute($route->getMethods(), $route->getUri(), $route);
            }
        });

        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());

        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException($routeInfo[1]);
            case \FastRoute\Dispatcher::FOUND:
                $this->setCurrentRoute($routeInfo[1], $routeInfo[2]);
                return $this->handleRouteWithMiddleware($request, $routeInfo[1], $routeInfo[2]);
        }
    }

    /**
     * Set the current route.
     *
     * @param \Luminous\Routing\Route $route
     * @param array $parameters
     * @return void
     */
    protected function setCurrentRoute(Route $route, array $parameters = [])
    {
        $this->currentRoute = $route;
        $this->currentRoute->setParameters($parameters);
    }

    /**
     * Handle the route with middleware.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Luminous\Routing\Route $route
     * @param array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleRouteWithMiddleware(Request $request, Route $route, array $parameters)
    {
        $middleware = $this->gatherMiddlewareClassNames($route->getMiddleware());

        return $this->sendThroughPipeline($middleware, $request, function () use ($request, $route, $parameters) {
            return $this->handleRoute($request, $route, $parameters);
        });
    }

    /**
     * Handle the route.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Luminous\Routing\Route $route
     * @param array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function handleRoute(Request $request, Route $route, array $parameters)
    {
        $controller = $route->getController();
        $method = $route->getControllerMethod();

        if (is_string($controller) && ! ($controller = $this->app->make($controller))) {
            throw new NotFoundHttpException;
        }

        if (! method_exists($controller, $method)) {
            throw new NotFoundHttpException;
        }

        $callback = [$controller, $method];

        if ($controller instanceof Controller) {
            $middleware = $this->gatherMiddlewareClassNames($controller->getMiddlewareForMethod($request, $method));
            $maxAge = $controller->maxAge($request, $method);

            return $this->sendThroughPipeline($middleware, $request, function () use ($callback, $parameters, $maxAge) {
                return $this->call($callback, $parameters, $maxAge);
            });
        }

        return $this->call($callback, $parameters);
    }

    /**
     * Call the callback with parameters.
     *
     * @param callable $callback
     * @param array $parameters
     * @param int $maxAge
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function call(callable $callback, array $parameters, $maxAge = 0)
    {
        $response = $this->app->call($callback, $parameters);

        if (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        }

        if ($maxAge) {
            $response->setCache(['private' => true, 'max_age' => $maxAge]);
        }

        return $response;
    }

    /**
     * Gather the full class names for the middleware short-cut string.
     *
     * @param string $middleware
     * @return array
     */
    protected function gatherMiddlewareClassNames($middleware)
    {
        $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;

        return array_map(function ($name) {
            list($name, $parameters) = array_pad(explode(':', $name, 2), 2, null);
            return Arr::get($this->routeMiddleware, $name, $name).($parameters ? ':'.$parameters : '');
        }, $middleware);
    }

    /**
     * Send the request through the pipeline with the given callback.
     *
     * @param array $middleware
     * @param \Illuminate\Http\Request $request
     * @param \Closure $then
     * @return mixed
     */
    protected function sendThroughPipeline(array $middleware, Request $request, Closure $then)
    {
        $shouldSkipMiddleware = $this->app->bound('middleware.disable') &&
                                $this->app->make('middleware.disable') === true;

        if (count($middleware) > 0 && ! $shouldSkipMiddleware) {
            return (new Pipeline($this->app))->send($request)->through($middleware)->then($then);
        }

        return $then();
    }

    /**
     * Handle the HTTP exception.
     *
     * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception
     * @return \Illuminate\Http\Response
     *
     * @throws \Exception
     */
    protected function handleHttpException(HttpExceptionInterface $exception)
    {
        if (! $view = $this->getErrorView($exception)) {
            throw $exception;
        }

        $statusCode = $exception->getStatusCode();
        $headers = $exception->getHeaders();

        return new Response($view, $statusCode, $headers);
    }

    /**
     * Get the error view.
     *
     * @param \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception
     * @return \Illuminate\View\View|null
     */
    protected function getErrorView(HttpExceptionInterface $exception)
    {
        $view = $this->app['view'];
        $statuses = [$exception->getStatusCode()];

        if (! env('APP_DEBUG', false)) {
            $statuses[] = 500;
        }

        foreach (array_unique($statuses) as $status) {
            if ($view->exists($file = "error.{$status}")) {
                return $view->make($file, compact('status', 'exception'));
            }
        }

        return null;
    }

    /**
     * Handle the exception.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Illuminate\Http\Response
     */
    protected function handleException(Request $request, $exception)
    {
        $handler = $this->app->make('Illuminate\Contracts\Debug\ExceptionHandler');

        if ($exception instanceof Error) {
            $exception = new FatalThrowableError($exception);
        }

        $handler->report($exception);

        return $handler->render($request, $exception);
    }
}
