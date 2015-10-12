<?php

namespace Luminous\Routing;

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
     * @return \Illuminate\Http\Response
     */
    public function dispatch(Request $request)
    {
        try {
            $response = $this->dispatchRequest($request);
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
     * @param \Illuminate\Http\Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        foreach ($this->middleware as $middleware) {
            if (method_exists($instance = $this->app->make($middleware), 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
    }

    /**
     * Dispatch the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    protected function dispatchRequest(Request $request)
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
     * @return mixed
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
                return $this->callAction($request, $routeInfo[1], $routeInfo[2]);
        }
    }

    /**
     * Call the action.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Luminous\Routing\Route $route
     * @param array $parameters
     * @return mixed
     */
    protected function callAction(Request $request, Route $route, array $parameters)
    {
        $route->setParameters($parameters);
        $this->currentRoute = $route;

        $middleware = $this->gatherMiddlewareClassNames($route->getMiddleware());

        return $this->sendThroughPipeline($middleware, $request, function () use ($request, $route, $parameters) {
            if (is_array($action = $route->getAction())) {
                return $this->callControllerMethod($request, $action[0], $action[1], $parameters);
            }

            return $this->call($action, $parameters);
        });
    }

    /**
     * Call the controller method.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $controller
     * @param string $method
     * @param array $parameters
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function callControllerMethod(Request $request, $controller, $method, array $parameters)
    {
        if (! method_exists($instance = $this->app->make($controller), $method)) {
            throw new NotFoundHttpException;
        }

        $callback = [$instance, $method];

        if ($instance instanceof Controller) {
            $middleware = $this->gatherMiddlewareClassNames($instance->getMiddlewareForMethod($request, $method));
            $maxAge = $instance->maxAge($request, $method);

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
     * @return \Illuminate\Http\Response
     */
    protected function call(callable $callback, array $parameters, $maxAge = 0)
    {
        $response = $this->app->call($callback, $parameters);

        if (! $response instanceof Response) {
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
    protected function sendThroughPipeline(array $middleware, Request $request, \Closure $then)
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

        return new Response($view->render(), $statusCode, $headers);
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
