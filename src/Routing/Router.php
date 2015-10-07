<?php

namespace Luminous\Routing;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Luminous\Application;

/**
 * @method void get(string $uri, mixed $action) Add a route to the collection via GET method.
 * @method void head(string $uri, mixed $action) Add a route to the collection via HEAD method.
 * @method void post(string $uri, mixed $action) Add a route to the collection via POST method.
 * @method void put(string $uri, mixed $action) Add a route to the collection via PUT method.
 * @method void patch(string $uri, mixed $action) Add a route to the collection via PATCH method.
 * @method void delete(string $uri, mixed $action) Add a route to the collection via DELETE method.
 */
class Router
{
    /**
     * The application instance.
     *
     * @var \Luminous\Application
     */
    protected $app;

    /**
     * The HTTP methods.
     *
     * @var array
     */
    protected $methods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * The route collection.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * The named route collection.
     *
     * @var array
     */
    protected $namedRoutes = [];

    /**
     * The shared attributes for the current route scope.
     *
     * @var array
     */
    protected $currentScope = [];

    /**
     * The current request context.
     *
     * @var array|null
     */
    protected $cachedRequestContext;

    /**
     * Create a new router instance.
     *
     * @param \Luminous\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get the route collection.
     *
     * @return array
     */
    public function routes()
    {
        return $this->routes;
    }

    /**
     * Add a route to the collection.
     *
     * @param array|string $methods
     * @param string $uri
     * @param mixed $action
     * @return void
     */
    public function add($methods, $uri, $action)
    {
        $methods = array_map('strtoupper', (array) $methods);

        if (isset($this->currentScope['prefix'])) {
            $uri = strpos($uri, '[') === 0 ? $uri : '/'.trim($uri, '/');
            $uri = $this->currentScope['prefix'].$uri;
        }

        $uri = '/'.trim($uri, '/');

        if (is_string($action)) {
            $action = ['uses' => $action];
        } elseif (! is_array($action)) {
            $action = [$action];
        }

        if (isset($this->currentScope['query'])) {
            $action['query'] = array_merge($this->currentScope['query'], Arr::get($action, 'query', []));
        }

        if (isset($action['uses'], $this->currentScope['namespace'])) {
            $action['uses'] = $this->currentScope['namespace'].'\\'.$action['uses'];
        }

        if (isset($action['as'])) {
            $this->namedRoutes[$action['as']] = $uri;
        }

        $this->routes[] = compact('methods', 'uri', 'action');
    }

    /**
     * Add a route to the collection via any methods.
     *
     * @param string $uri
     * @param mixed $action
     * @return void
     */
    public function any($uri, $action)
    {
        $this->add($this->methods, $uri, $action);
    }

    /**
     * Register a set of routes with a set of shared attributes.
     *
     * @param array $attributes
     * @param Closure $callback
     * @return void
     */
    public function scope(array $attributes, Closure $callback)
    {
        if (is_string($attributes)) {
            $attributes = ['prefix' => $attributes];
        }

        $current = $this->currentScope;
        $this->currentScope = $this->mergeScope($current, $attributes);

        call_user_func($callback, $this);

        $this->currentScope = $current;
    }

    /**
     * Get the merged scope attributes.
     *
     * @param array $current
     * @param array $attributes
     * @return array
     */
    protected function mergeScope(array $current, array $attributes)
    {
        if (isset($attributes['prefix'])) {
            $prefix = '/'.trim($attributes['prefix'], '/');
            $current['prefix'] = Arr::get($current, 'prefix', '').$prefix;
        }

        if (isset($attributes['namespace'])) {
            $current['namespace'] = $attributes['namespace'];
        }

        if (isset($attributes['query'])) {
            $current['query'] = array_merge(Arr::get($current, 'query', []), $attributes['query']);
        }

        return $current;
    }

    /**
     * Get the URL.
     *
     * @param string $path
     * @param bool|string $full
     * @param null|bool $secure
     * @return string
     */
    public function url($path = '', $full = false, $secure = null)
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $base = is_string($full) ? rtrim($full, '/') : $this->getBaseUrl($full, $secure);

        return $base.'/'.ltrim($path, '/');
    }

    /**
     * Get the URL to a named route.
     *
     * @param string $path
     * @param array $parameters
     * @param bool|string $full
     * @param null|bool $secure
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, array $parameters = [], $full = false, $secure = null)
    {
        if (! isset($this->namedRoutes[$name])) {
            throw new InvalidArgumentException("Route [{$name}] not defined.");
        }

        $uri = $this->buildRouteUrl($this->namedRoutes[$name], function ($key) use (&$parameters) {
            if (isset($parameters[$key])) {
                return array_pull($parameters, $key);
            } elseif (preg_match('/^(archive|post|term|user)_(.+)$/', $key, $m) && isset($parameters[$m[1]])) {
                return $parameters[$m[1]]->urlParameter($m[2]);
            }
        });

        unset($parameters['archive'], $parameters['post'], $parameters['term'], $parameters['user']);

        if (! empty($parameters)) {
            $uri .= '?'.http_build_query($parameters);
        }

        return $this->url($uri, $full, $secure);
    }

    /**
     * Build the URL.
     *
     * @param string $route
     * @param Closure $callback
     * @return string
     */
    public function buildRouteUrl($route, Closure $callback)
    {
        $pattern = '~(?:\[/?)?'.\FastRoute\RouteParser\Std::VARIABLE_REGEX.'~x';
        $uri = rtrim($route, ']');

        return preg_replace_callback($pattern, function ($m) use ($callback) {
            $segment = $callback($m[1]);

            if (strpos($m[0], '[') === 0) {
                return $segment ? (strpos($m[0], '[/') === 0 ? '/' : '').$segment : '';
            } else {
                return $segment ? $segment : $m[0];
            }
        }, $uri);
    }

    /**
     * Determine if the given path is a valid URL.
     *
     * @todo Fix options for 'filter_var()'.
     *
     * @param string $path
     * @return bool
     */
    protected function isValidUrl($path)
    {
        if (Str::startsWith($path, ['//', '#', '?', 'javascript:', 'mailto:', 'tel:', 'sms:'])) {
            return true;
        }

        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get the base URL for the request.
     *
     * @param bool|string $full
     * @param null|bool $secure
     * @return string
     */
    protected function getBaseUrl($full = false, $secure = null)
    {
        $context = $this->getRequestContext();
        $base = $context['base'];

        if ($full) {
            $secure = is_null($secure) ? $context['secure'] : $secure;
            $scheme = $secure ? 'https' : 'http';
            $base = "{$scheme}://{$context['host']}{$base}";
        }

        return $base;
    }

    /**
     * Get the request context.
     *
     * @return array
     */
    protected function getRequestContext()
    {
        if (is_null($this->cachedRequestContext)) {
            $request = $this->app->make('request');
            $this->cachedRequestContext = [
                'host' => $request->getHttpHost(),
                'base' => $request->getBaseUrl(),
                'secure' => $request->isSecure(),
            ];
        }

        return $this->cachedRequestContext;
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        if (in_array(strtoupper($method), $this->methods)) {
            call_user_func_array([$this, 'add'], array_merge([$method], $arguments));
            return;
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}
