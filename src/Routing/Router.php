<?php

namespace Luminous\Routing;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Luminous\Application;
use Luminous\Bridge\Entity;
use Luminous\Bridge\Type;
use Luminous\Bridge\UrlResource;
use Luminous\Support\Url;

/**
 * Router Class
 *
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
     * @var \Luminous\Routing\Route[]
     */
    protected $routes = [];

    /**
     * The named route collection.
     *
     * @var \Luminous\Routing\Route[]
     */
    protected $namedRoutes = [];

    /**
     * The shared attributes for the current route scope.
     *
     * @var array
     */
    protected $currentScope = [];

    /**
     * The current context.
     *
     * @var array|null
     */
    protected $cachedContext;

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
     * @return \Luminous\Routing\Route[]
     */
    public function routes()
    {
        return $this->routes;
    }

    /**
     * Add a route to the collection.
     *
     * @param array|string $methods HTTP verbs
     * @param string $uri
     * @param mixed $options
     * @return void
     */
    public function add($methods, $uri, $options)
    {
        $names = [];
        $uri = '/'.Url::join(Arr::get($this->currentScope, 'prefix', ''), $uri);

        if (is_string($options)) {
            $options = ['uses' => $options];
        }

        if (is_array($options)) {
            $options['parameters'] = array_merge(
                Arr::get($this->currentScope, 'parameters', []),
                Arr::get($options, 'parameters', [])
            );

            if ($name = $this->buildName($options['parameters'])) {
                $names[] = $name;
            }

            if (isset($options['uses'], $this->currentScope['namespace'])) {
                $options['uses'] = $this->currentScope['namespace'].'\\'.$options['uses'];
            }

            if (isset($options['as'])) {
                $names[] = $options['as'];
            }
        }

        $route = new Route($methods, $uri, $options);

        foreach ($names as $name) {
            $this->namedRoutes[$name] = $route;
        }

        $this->routes[] = $route;
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
     * The attributes array accepts:
     *
     * - namespace: (string) The value is combined with current value.
     * - prefix: (string) The value is combined with current value.
     * - parameters: (array) The value is merged with current value.
     *
     * @todo Merge middleware.
     *
     * @param array|string $attributes If string, used as `prefix`;
     * @param Closure $callback The collback receives $this.
     * @return void
     */
    public function scope($attributes, Closure $callback)
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
        if (isset($attributes['namespace'])) {
            $current['namespace'] = isset($current['namespace'])
                ? $current['namespace'].'\\'.$attributes['namespace']
                : $attributes['namespace'];
        }

        if (isset($attributes['prefix'])) {
            $current['prefix'] = Url::join(Arr::get($current, 'prefix', ''), $attributes['prefix']);
        }

        if (isset($attributes['parameters'])) {
            $current['parameters'] = array_merge(Arr::get($current, 'parameters', []), $attributes['parameters']);
        }

        return $current;
    }

    /**
     * Get the URL.
     *
     * The parameters array accepts:
     *
     * - scheme: (string)
     * - host: (string)
     * - port: (int)
     * - base: (string)
     * - path: (string)
     * - ?: (array)
     * - #: (string)
     *
     * @todo Support userinfo.
     *
     * @param array|string|mixed $parameters
     * @param bool $full
     * @return string
     */
    public function url($parameters = '', $full = false)
    {
        if (Url::valid($parameters)) {
            return $parameters;
        }

        $context = $this->getContext();
        $parameters = $this->normalizeParameters($parameters) + $context;

        if ($name = $this->buildName($parameters)) {
            $parameters['path'] = $this->buildPath($name, $parameters);
        }

        $path = '/'.Url::join($parameters['base'], $parameters['path']);

        if ($parameters['?']) {
            $path .= '?'.http_build_query($parameters['?']);
        }

        if ($parameters['#']) {
            $path .= '#'.$parameters['#'];
        }

        if (! $full) {
            return $path;
        }

        $host = "{$parameters['scheme']}://{$parameters['host']}";

        if ($parameters['port'] && $parameters['port'] !== $context['port']) {
            $host .= ":{$parameters['port']}";
        }

        return $host.$path;
    }

    /**
     * Normalize url parameters.
     *
     * @param mixed $parameters
     * @return array
     */
    protected function normalizeParameters($parameters)
    {
        if (is_string($parameters)) {
            $parameters = ['path' => $parameters];
        } elseif (! is_array($parameters)) {
            $parameters = [$parameters];
        }

        $normalized = [];

        foreach ($parameters as $key => $value) {
            if (is_int($key) && ($value instanceof UrlResource)) {
                $normalized = array_merge($normalized, $value->forUrl());
            } elseif ($value instanceof Type) {
                $normalized[$key] = $value->name;
            } else {
                if ($value instanceof Entity) {
                    $normalized["{$key}_type"] = $value->type->name;
                }
                $normalized[$key] = $value;
            }
        }

        if (isset($normalized['host']) && strpos($normalized['host'], '//') !== false) {
            $context = Arr::pull($normalized, 'host');
            $normalized += $this->parseContext($context);
        }

        return $normalized + ['path' => '', '?' => [], '#' => null];
    }

    /**
     * Build the URL path of the named route.
     *
     * @param string $name
     * @param array $parameters
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function buildPath($name, array $parameters = [])
    {
        if (! isset($this->namedRoutes[$name])) {
            throw new InvalidArgumentException("Route [{$name}] not defined.");
        }

        return $this->namedRoutes[$name]->getUri($parameters);
    }

    /**
     * Build the route name from options.
     *
     * @todo Treat optional path.
     *
     * @param array $parameters
     * @return string|null
     */
    protected function buildName(array $parameters)
    {
        ksort($parameters);
        $names = [];

        foreach ($parameters as $key => $value) {
            if (in_array($key, ['post_type', 'term_type'])) {
                $names[] = "{$key}:{$value}";
            } elseif (in_array($key, ['date', 'post', 'term', 'user'])) {
                $names[] = $key;
            }
        }

        return $names ? '['.implode('|', $names).']' : null;
    }

    /**
     * Set the context.
     *
     * @param array|string $context
     * @return $this
     */
    public function setContext($context)
    {
        $this->cachedContext = $this->parseContext($context);

        return $this;
    }

    /**
     * Get the context from the request.
     *
     * @return array
     */
    public function getContext()
    {
        if (is_null($this->cachedContext)) {
            $request = $this->app->make('request');

            $this->cachedContext = [
                'scheme' => $request->getScheme(),
                'host' => $request->getHost(),
                'port' => $request->getPort(),
                'base' => $request->getBaseUrl(),
            ];
        }

        return $this->cachedContext;
    }

    /**
     * Parse a context.
     *
     * @param string $context
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseContext($context)
    {
        if (is_string($context) && ! ($context = parse_url($context))) {
            throw new InvalidArgumentException;
        }

        if (empty($context['host'])) {
            throw new InvalidArgumentException;
        }

        $values = [
            'host' => $context['host'],
            'scheme' => Arr::get($context, 'scheme', 'http'),
        ];

        $values['port'] = Arr::get($context, 'port', $context['scheme'] === 'https' ? 443 : 80);
        $values['base'] = Arr::get($context, 'base', '/');

        return $values;
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
