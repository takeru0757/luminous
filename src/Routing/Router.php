<?php

namespace Luminous\Routing;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Luminous\Application;
use Luminous\Bridge\Type;
use Luminous\Bridge\Entity;
use Luminous\Bridge\Post\Type as PostType;
use Luminous\Bridge\Post\Entity as PostEntity;

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
        $via = array_map('strtoupper', (array) $methods);

        if (isset($this->currentScope['prefix'])) {
            $uri = strpos($uri, '[') === 0 ? $uri : '/'.trim($uri, '/');
            $uri = $this->currentScope['prefix'].$uri;
        }

        $uri = '/'.trim($uri, '/');

        if (is_string($action)) {
            $action = ['uses' => $action];
        }

        if (is_array($action)) {
            $query = array_merge(Arr::get($this->currentScope, 'query', []), Arr::get($action, 'query', []));
            $params = array_intersect_key($query, array_flip(['post_type', 'term_type']));

            $uri = preg_replace_callback('/\{(archive|post|term|user)\}/', function ($m) use (&$query, &$params) {
                $params[$key = $m[1]] = null;
                if ($value = Arr::pull($query, $key)) {
                    return preg_replace('/\{([a-z][a-z\d_]*)/i', "{{$key}__$1", $value);
                } else {
                    $regex = $key === 'archive' ? '\d{4}(?:/\d{2}(?:/\d{2})?)?' : '.+';
                    return "{{$key}__path:{$regex}}";
                }
            }, $uri);

            $action['query'] = $query;

            if ($name = $this->buildName($params)) {
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
     * @param string|mixed $path
     * @param bool|string $full
     * @param null|bool $secure
     * @return string
     */
    public function url($path = '', $full = false, $secure = null)
    {
        if (! is_string($path)) {
            $parameters = $this->normalizeParameters($path);
            $name = $this->buildName($parameters);
            return $this->route($name, $parameters, $full, $secure);
        } elseif ($this->isValidUrl($path)) {
            return $path;
        }

        $base = is_string($full) ? rtrim($full, '/') : $this->getBaseUrl($full, $secure);

        return $base.'/'.ltrim($path, '/');
    }

    /**
     * Normalize parameters.
     *
     * @param mixed $parameters
     * @return array
     */
    protected function normalizeParameters($parameters)
    {
        if ($parameters instanceof PostType) {
            $parameters = ['post_type' => $parameters];
        } elseif ($parameters instanceof PostEntity) {
            $parameters = ['post' => $parameters];
        }

        foreach (['post', 'term'] as $key) {
            if (isset($parameters["{$key}_type"]) && $parameters["{$key}_type"] instanceof Type) {
                $parameters["{$key}_type"] = $parameters["{$key}_type"]->name;
            } elseif (isset($parameters[$key]) && $parameters[$key] instanceof Entity) {
                $parameters["{$key}_type"] = $parameters[$key]->type->name;
            }
        }

        return $parameters;
    }

    /**
     * Get the URL to a named route.
     *
     * @param string $name
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

        $pattern = '~'.\FastRoute\RouteParser\Std::VARIABLE_REGEX.'~x';
        $parts = explode('[', rtrim($this->namedRoutes[$name], ']'));
        $uri = '';

        foreach ($parts as $part) {
            $replaced = preg_replace_callback($pattern, function ($m) use (&$parameters) {
                if ($value = Arr::pull($parameters, $key = $m[1])) {
                    return $value;
                } elseif (preg_match('/^(archive|post|term|user)__(.+)$/', $key, $s) && isset($parameters[$s[1]])) {
                    return $parameters[$s[1]]->urlParameter($s[2]);
                } else {
                    return $m[0];
                }
            }, $part);

            if (strpos($replaced, '{') !== false) {
                break;
            }

            $uri .= $replaced;
        }

        $rejectKeys = ['archive', 'post_type', 'post', 'term_type', 'term', 'user'];

        foreach ($parameters as $key => $value) {
            if (in_array($key, $rejectKeys) || preg_match('/^(?:archive|post|term|user)__.+$/', $key)) {
                unset($parameters[$key]);
            }
        }

        if (! empty($parameters)) {
            $uri .= '?'.http_build_query($parameters);
        }

        return $this->url($uri, $full, $secure);
    }

    /**
     * Build the route name from parameters.
     *
     * @param array $parameters
     * @return string|null
     */
    protected function buildName(array $parameters)
    {
        ksort($parameters);

        $type = array_key_exists('post', $parameters) ? 'show' : 'index';
        $names = [];

        foreach ($parameters as $key => $value) {
            if (in_array($key, ['post_type', 'term_type'])) {
                $names[] = "{$key}:{$value}";
            } elseif (in_array($key, ['archive', 'user'])) {
                $names[] = $key;
            }
        }

        return $names ? "{$type}[".implode('|', $names).']' : null;
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
