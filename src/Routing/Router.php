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
     * @return array
     */
    public function routes()
    {
        return $this->routes;
    }

    /**
     * Add a route to the collection.
     *
     * @param array|string $via HTTP verbs
     * @param string $uri
     * @param mixed $action
     * @return void
     */
    public function add($via, $uri, $action)
    {
        $via = array_map('strtoupper', (array) $via);
        $uri = '/'.Url::join(Arr::get($this->currentScope, 'prefix', ''), $uri);

        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        if (is_array($action)) {
            $action['query'] = array_merge(Arr::get($this->currentScope, 'query', []), Arr::get($action, 'query', []));

            foreach ($action['query'] as $key => $value) {
                if (strpos($value, '{') === false) {
                    continue;
                }
                $replacement = preg_replace('/\{([a-z][a-z\d_]*)/i', "{{$key}__$1", $value);
                $uri = preg_replace("/\{{$key}\}/", $replacement, $uri);
            }

            if ($name = $this->buildName($action['query'])) {
                $this->namedRoutes[$name] = $uri;
            }

            if (isset($action['uses'], $this->currentScope['namespace'])) {
                $action['uses'] = $this->currentScope['namespace'].'\\'.$action['uses'];
            }

            if (isset($action['as'])) {
                $this->namedRoutes[$action['as']] = $uri;
            }
        } else {
            $action = [$action];
        }

        $this->routes[] = compact('via', 'uri', 'action');
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
     * - prefix: (string) The value is combined with current value.
     * - namespace: (string) The value REPLACEs with current value.
     * - query: (array) The value is merged with current value.
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
        if (isset($attributes['prefix'])) {
            $current['prefix'] = Url::join(Arr::get($current, 'prefix', ''), $attributes['prefix']);
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
     * The options array accepts:
     *
     * - scheme: (string)
     * - host: (string)
     * - port: (int)
     * - path: (string)
     * - ?: (array)
     * - #: (string)
     *
     * @todo Support userinfo.
     *
     * @param array|string|mixed $options
     * @param bool $full
     * @return string
     */
    public function url($options = '', $full = false)
    {
        if (Url::valid($options)) {
            return $options;
        }

        $options = $this->normalizeUrlOptions($options);
        $context = $this->getContext();

        $defaults = ['port' => null, 'path' => '', '?' => [], '#' => null, '_base' => null];
        $defaults += Arr::only($context, ['scheme', 'host']);
        $options = array_merge($defaults, $options);

        if ($name = $this->buildName($options)) {
            $options['path'] = $this->buildPath($name, $options);
        }

        $base = $options['_base'] ?: $context['base'];
        $path = '/'.Url::join($base, $options['path']);

        if ($options['?']) {
            $path .= '?'.http_build_query($options['?']);
        }

        if ($options['#']) {
            $path .= '#'.$options['#'];
        }

        if (! $full) {
            return $path;
        }

        $host = "{$options['scheme']}://{$options['host']}";

        // Use `!=` (Inequality)
        if ($options['port'] && $options['port'] != $context['port']) {
            $host .= ":{$options['port']}";
        }

        return $host.$path;
    }

    /**
     * Normalize url options.
     *
     * @param mixed $options
     * @return array
     */
    protected function normalizeUrlOptions($options)
    {
        if (is_string($options)) {
            return ['path' => $options];
        } elseif (! is_array($options)) {
            $options = [$options];
        }

        $normalized = [];

        foreach ($options as $key => $value) {
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

        if (isset($normalized['host']) && ($parsed = parse_url($normalized['host']))) {
            $normalized['host'] = $parsed['host'];
            $normalized['_base'] = Arr::get($parsed, 'path');
            $normalized += Arr::only($parsed, ['scheme', 'port']);
        }

        return $normalized;
    }

    /**
     * Build the URL path of the named route.
     *
     * @param string $name
     * @param array $options
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function buildPath($name, array $options = [])
    {
        if (! isset($this->namedRoutes[$name])) {
            throw new InvalidArgumentException("Route [{$name}] not defined.");
        }

        $pattern = '~'.\FastRoute\RouteParser\Std::VARIABLE_REGEX.'~x';
        $parts = explode('[', rtrim($this->namedRoutes[$name], ']'));
        $path = '';

        foreach ($parts as $part) {
            $replaced = preg_replace_callback($pattern, function ($m) use ($options) {
                if ($value = Arr::get($options, $key = $m[1])) {
                    return $value;
                } elseif (preg_match('/^([^_]+)__([^_].*)$/', $key, $s) &&
                    ($object = Arr::get($options, $s[1])) && ($object instanceof UrlResource)
                ) {
                    return $object->urlPath($s[2]);
                } else {
                    return '__FAIL__';
                }
            }, $part);

            if (strpos($replaced, '__FAIL__') !== false) {
                break;
            }

            $path .= $replaced;
        }

        return $path;
    }

    /**
     * Build the route name from options.
     *
     * @todo Treat optional path.
     *
     * @param array $options
     * @return string|null
     */
    protected function buildName(array $options)
    {
        ksort($options);
        $names = [];

        foreach ($options as $key => $value) {
            if (in_array($key, ['post_type', 'term_type'])) {
                $names[] = "{$key}:{$value}";
            } elseif (in_array($key, ['archive', 'post', 'term', 'user'])) {
                $names[] = $key;
            }
        }

        return $names ? '['.implode('|', $names).']' : null;
    }

    /**
     * Get the context from the request.
     *
     * @return array
     */
    protected function getContext()
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
