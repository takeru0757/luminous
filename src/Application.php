<?php

namespace Luminous;

use Closure;
use Exception;
use ErrorException;
use RuntimeException;
use Throwable;
use FastRoute\Dispatcher;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * Application class
 *
 * This class is based on Laravel Lumen Application class.
 * (c) Taylor Otwell, licensed under the MIT license.
 * @link https://github.com/laravel/lumen-framework/blob/5.1/composer.json
 *
 */
class Application extends Container implements ApplicationContract, HttpKernelInterface
{
    /**
     * The base path of the application installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * The service binding methods that have been executed.
     *
     * @var array
     */
    protected $ranServiceBinders = [];

    /**
     * All of the loaded configuration files.
     *
     * @var array
     */
    protected $loadedConfigurations = [];

    /**
     * Create a new Lumen application instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        $this->basePath = $basePath;
        $this->bootstrapContainer();
        $this->registerErrorHandling();
    }

    /**
     * Bootstrap the application container.
     *
     * @return void
     */
    protected function bootstrapContainer()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance('path', $this->path());
        $this->instance('modified', @filemtime($this->basePath('style.css')) ?: 0);

        $this->registerContainerAliases();
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        static $version = null;

        if (is_null($version)) {
            $style = file_get_contents(__DIR__.'/../style.css');
            preg_match('/^Version:\s*(.+)$/im', $style, $matches);
            $version = isset($matches[1]) ? $matches[1] : '-';
        }

        return "Luminous ({$version})";
    }

    /**
     * Get or check the current application environment.
     *
     * @param  mixed
     * @return string
     */
    public function environment()
    {
        return env('APP_ENV', 'production');
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Get the application namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return 'App\\';
    }

    // Path ====================================================================

    /**
     * Get the path to the application "app" directory.
     *
     * @return string
     */
    public function path()
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'app';
    }

    /**
     * Get the base path for the application.
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = null)
    {
        if (isset($this->basePath)) {
            return $this->basePath.($path ? '/'.$path : $path);
        }

        if ($this->runningInConsole()) {
            $this->basePath = getcwd();
        } else {
            $this->basePath = realpath(getcwd().'/../');
        }

        return $this->basePath($path);
    }

    /**
     * Get the path to the Luminous framework directory.
     *
     * @param string $path
     * @return string
     */
    public function frameworkBasePath($path = null)
    {
        return dirname(__DIR__).($path ? '/'.$path : $path);
    }

    /**
     * Get the storage path for the application.
     *
     * @param  string  $path
     * @return string
     */
    public function storagePath($path = null)
    {
        return $this->basePath().'/storage'.($path ? '/'.$path : $path);
    }

    /**
     * Get the resource path for the application.
     *
     * @param  string  $path
     * @return string
     */
    public function resourcePath($path = null)
    {
        return $this->basePath().'/resources'.($path ? '/'.$path : $path);
    }

    // IoC Container ===========================================================

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @param  bool   $force
     * @return \Illuminate\Support\ServiceProvider
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function register($provider, $options = [], $force = false)
    {
        if (! $provider instanceof ServiceProvider) {
            $provider = new $provider($this);
        }

        if (array_key_exists($providerName = get_class($provider), $this->loadedProviders)) {
            return;
        }

        $this->loadedProviders[$providerName] = true;

        $provider->register();
        $provider->boot();
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string  $provider
     * @param  string  $service
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        return $this->register($provider);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array   $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        if (array_key_exists($abstract, $this->availableBindings) &&
            ! array_key_exists($this->availableBindings[$abstract], $this->ranServiceBinders)) {
            $this->{$method = $this->availableBindings[$abstract]}();

            $this->ranServiceBinders[$method] = true;
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Configure and load the given component and provider.
     *
     * @param  string  $config
     * @param  array|string  $providers
     * @param  string|null  $return
     * @return mixed
     */
    protected function loadComponent($config, $providers, $return = null)
    {
        $this->configure($config);

        foreach ((array) $providers as $provider) {
            $this->register($provider);
        }

        return $this->make($return ?: $config);
    }

    /**
     * Load a configuration file into the application.
     *
     * @return void
     */
    public function configure($name)
    {
        if (isset($this->loadedConfigurations[$name])) {
            return;
        }

        $this->loadedConfigurations[$name] = true;

        $path = $this->getConfigurationPath($name);

        if ($path) {
            $this->make('config')->set($name, require $path);
        }
    }

    /**
     * Get the path to the given configuration file.
     *
     * @param  string  $name
     * @return string
     */
    protected function getConfigurationPath($name)
    {
        if (file_exists($path = $this->basePath("config/{$name}.php"))) {
            return $path;
        } elseif (file_exists($path = $this->frameworkBasePath("config/{$name}.php"))) {
            return $path;
        }
    }

    // Bindings ================================================================

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerBridgeBindings()
    {
        $this->singleton('wp', function () {
            return $this->loadComponent('wp', 'Luminous\Bridge\BridgeServiceProvider');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerBusBindings()
    {
        $this->singleton('Illuminate\Contracts\Bus\Dispatcher', function () {
            $this->register('Illuminate\Bus\BusServiceProvider');

            return $this->make('Illuminate\Contracts\Bus\Dispatcher');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerCacheBindings()
    {
        $this->singleton('cache', function () {
            return $this->loadComponent('cache', 'Illuminate\Cache\CacheServiceProvider');
        });

        $this->singleton('cache.store', function () {
            return $this->loadComponent('cache', 'Illuminate\Cache\CacheServiceProvider', 'cache.store');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerConfigBindings()
    {
        $this->singleton('config', function () {
            return new ConfigRepository;
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerCookieBindings()
    {
        $this->singleton('cookie', function () {
            return $this->loadComponent('session', 'Illuminate\Cookie\CookieServiceProvider', 'cookie');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerEncrypterBindings()
    {
        $this->singleton('encrypter', function () {
            return $this->loadComponent('app', 'Illuminate\Encryption\EncryptionServiceProvider', 'encrypter');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerEventBindings()
    {
        $this->singleton('events', function () {
            $this->register('Illuminate\Events\EventServiceProvider');
            return $this->make('events');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerExceptionHandlerBindings()
    {
        $this->singleton('Illuminate\Contracts\Debug\ExceptionHandler', 'Luminous\Exceptions\Handler');
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerFilesBindings()
    {
        $this->singleton('files', function () {
            return new Filesystem;
        });

        $this->singleton('filesystem', function () {
            return $this->loadComponent('filesystems', 'Illuminate\Filesystem\FilesystemServiceProvider', 'filesystem');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerLogBindings()
    {
        $this->singleton('Psr\Log\LoggerInterface', function () {
            return new Logger('luminous', [$this->getMonologHandler()]);
        });
    }

    /**
     * Get the Monolog handler for the application.
     *
     * @return \Monolog\Handler\AbstractHandler
     */
    protected function getMonologHandler()
    {
        $handler = new StreamHandler($this->storagePath('logs/luminous.log'), Logger::DEBUG);
        return $handler->setFormatter(new LineFormatter(null, null, true, true));
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerMailBindings()
    {
        $this->singleton('mailer', function () {
            $this->configure('services');

            return $this->loadComponent('mail', 'Illuminate\Mail\MailServiceProvider', 'mailer');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerRequestBindings()
    {
        $this->singleton('Illuminate\Http\Request', function () {
            Request::enableHttpMethodParameterOverride();

            return $this->getRequestInstance()->setRouteResolver(function () {
                return $this->currentRoute;
            });
        });
    }

    /**
     * Remove magic quotes from `$_GET`, `$_POST`, `$_COOKIE`, and `$_SERVER`.
     *
     * @see \wp_magic_quotes() https://developer.wordpress.org/reference/functions/wp_magic_quotes/
     * @uses \stripslashes_deep()
     *
     * @return \Illuminate\Http\Request
     */
    protected function getRequestInstance()
    {
        if (! function_exists('wp_magic_quotes')) {
            return Request::capture();
        }

        $base = SymfonyRequest::createFromGlobals();

        foreach (['request', 'query', 'cookies', 'server', 'headers'] as $param) {
            $striped = array_map('stripslashes_deep', $base->{$param}->all());
            $base->{$param}->replace($striped);
        }

        return Request::createFromBase($base);
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerRouterBindings()
    {
        $this->singleton('router', 'Luminous\Routing\Router');
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerSessionBindings()
    {
        $this->singleton('session', function () {
            return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider');
        });

        $this->singleton('session.store', function () {
            return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider', 'session.store');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerTranslationBindings()
    {
        $this->singleton('translator', function () {
            $this->configure('app');

            $this->instance('path.lang', $this->getLanguagePath());

            $this->register('Illuminate\Translation\TranslationServiceProvider');

            return $this->make('translator');
        });
    }

    /**
     * Get the path to the application's language files.
     *
     * @return string
     */
    protected function getLanguagePath()
    {
        if (is_dir($path = $this->resourcePath('lang'))) {
            return $path;
        }

        return $this->frameworkBasePath('resources/lang');
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerValidatorBindings()
    {
        $this->singleton('validator', function () {
            $this->register('Illuminate\Validation\ValidationServiceProvider');

            return $this->make('validator');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerViewBindings()
    {
        $this->singleton('view', function () {
            return $this->loadComponent('view', 'Illuminate\View\ViewServiceProvider');
        });
    }

    // Middleware ==============================================================

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
     * Add new middleware to the application.
     *
     * @param  array  $middleware
     * @return $this
     */
    public function middleware(array $middleware)
    {
        $this->middleware = array_unique(array_merge($this->middleware, $middleware));

        return $this;
    }

    /**
     * Define the route middleware for the application.
     *
     * @param  array  $middleware
     * @return $this
     */
    public function routeMiddleware(array $middleware)
    {
        $this->routeMiddleware = array_merge($this->routeMiddleware, $middleware);

        return $this;
    }

    // Error Handring ==========================================================

    /**
     * Set the error handling for the application.
     *
     * @return void
     */
    protected function registerErrorHandling()
    {
        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function ($e) {
            $this->handleUncaughtException($e);
        });

        register_shutdown_function(function () {
            if (! is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
                $e = new FatalErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
                $this->handleUncaughtException($e);
            }
        });
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param  int  $type
     * @return bool
     */
    protected function isFatalError($type)
    {
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }

        return in_array($type, $errorCodes);
    }

    /**
     * Send the exception to the handler and return the response.
     *
     * @param  \Throwable  $e
     * @return Response
     */
    protected function sendExceptionToHandler($e)
    {
        $handler = $this->make('Illuminate\Contracts\Debug\ExceptionHandler');

        if ($e instanceof Error) {
            $e = new FatalThrowableError($e);
        }

        $handler->report($e);

        return $handler->render($this->make('request'), $e);
    }

    /**
     * Handle an uncaught exception instance.
     *
     * @param  \Throwable  $e
     * @return void
     */
    protected function handleUncaughtException($e)
    {
        $handler = $this->make('Illuminate\Contracts\Debug\ExceptionHandler');

        if ($e instanceof Error) {
            $e = new FatalThrowableError($e);
        }

        $handler->report($e);

        if ($this->runningInConsole()) {
            $handler->renderForConsole(new ConsoleOutput, $e);
        } else {
            $handler->render($this->make('request'), $e)->send();
        }
    }

    // Request Handring ========================================================

    /**
     * The current route being dispatched.
     *
     * @var array
     */
    protected $currentRoute;

    /**
     * The FastRoute dispatcher.
     *
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;

    /**
     * {@inheritdoc}
     */
    public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        return $this->dispatch($request);
    }

    /**
     * Run the application and send the response.
     *
     * @param  SymfonyRequest|null  $request
     * @return void
     */
    public function run($request = null)
    {
        $response = $this->dispatch($request);

        if ($response instanceof SymfonyResponse) {
            $response->send();
        } else {
            echo (string) $response;
        }

        if (count($this->middleware) > 0) {
            $this->callTerminableMiddleware($response);
        }
    }

    /**
     * Call the terminable middleware.
     *
     * @param  mixed  $response
     * @return void
     */
    protected function callTerminableMiddleware($response)
    {
        $response = $this->prepareResponse($response);

        foreach ($this->middleware as $middleware) {
            $instance = $this->make($middleware);

            if (method_exists($instance, 'terminate')) {
                $instance->terminate($this->make('request'), $response);
            }
        }
    }

    /**
     * Dispatch the incoming request.
     *
     * @param  SymfonyRequest|null  $request
     * @return Response
     */
    public function dispatch($request = null)
    {
        $this->configure('assets');
        $this->make('wp');

        if ($request) {
            $this->instance('Illuminate\Http\Request', $request);
            $this->ranServiceBinders['registerRequestBindings'] = true;
        } else {
            $request = $this->make('request');
        }

        $method = $request->getMethod();
        $pathInfo = $request->getPathInfo();

        try {
            return $this->sendThroughPipeline($this->middleware, function () use ($method, $pathInfo) {
                return $this->handleDispatcherResponse(
                    $this->createDispatcher()->dispatch($method, $pathInfo)
                );
            });
        } catch (Exception $e) {
            return $this->sendExceptionToHandler($e);
        } catch (Throwable $e) {
            return $this->sendExceptionToHandler($e);
        }
    }

    /**
     * Create a FastRoute dispatcher instance for the application.
     *
     * @uses \FastRoute\simpleDispatcher()
     *
     * @return Dispatcher
     */
    protected function createDispatcher()
    {
        return \FastRoute\simpleDispatcher(function ($r) {
            $router = $this->make('router');
            foreach ($router->routes() as $route) {
                $r->addRoute($route['methods'], $route['uri'], $route['action']);
            }
        });
    }

    /**
     * Handle the response from the FastRoute dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleDispatcherResponse($routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException;

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException($routeInfo[1]);

            case Dispatcher::FOUND:
                return $this->handleFoundRoute($routeInfo);
        }
    }

    /**
     * Handle a route found by the dispatcher.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        if (isset($routeInfo[1]['query'])) {
            $routeInfo[2] = ['query' => array_merge($routeInfo[1]['query'], $routeInfo[2])];
        }

        $this->currentRoute = $routeInfo;

        $action = $routeInfo[1];

        // Pipe through route middleware...
        if (isset($action['middleware'])) {
            $middleware = $this->gatherMiddlewareClassNames($action['middleware']);

            return $this->prepareResponse($this->sendThroughPipeline($middleware, function () use ($routeInfo) {
                return $this->callActionOnArrayBasedRoute($routeInfo);
            }));
        }

        return $this->prepareResponse(
            $this->callActionOnArrayBasedRoute($routeInfo)
        );
    }

    /**
     * Call the Closure on the array based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callActionOnArrayBasedRoute($routeInfo)
    {
        $action = $routeInfo[1];

        if (isset($action['uses'])) {
            return $this->prepareResponse($this->callControllerAction($routeInfo));
        }

        foreach ($action as $value) {
            if ($value instanceof Closure) {
                $closure = $value->bindTo(new Routing\Closure);
                break;
            }
        }

        try {
            return $this->prepareResponse($this->call($closure, $routeInfo[2]));
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Call a controller based route.
     *
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callControllerAction($routeInfo)
    {
        list($controller, $method) = explode('@', $routeInfo[1]['uses']);

        if (! method_exists($instance = $this->make($controller), $method)) {
            throw new NotFoundHttpException;
        }

        if ($instance instanceof Routing\Controller) {
            return $this->callLumenController($instance, $method, $routeInfo);
        } else {
            return $this->callControllerCallable([$instance, $method], $routeInfo[2]);
        }
    }

    /**
     * Send the request through a Lumen controller.
     *
     * @param  mixed  $instance
     * @param  string  $method
     * @param  array  $routeInfo
     * @return mixed
     */
    protected function callLumenController($instance, $method, $routeInfo)
    {
        $middleware = $instance->getMiddlewareForMethod($this->make('request'), $method);

        if (count($middleware) > 0) {
            return $this->callLumenControllerWithMiddleware($instance, $method, $routeInfo, $middleware);
        } else {
            return $this->callControllerCallable([$instance, $method], $routeInfo[2]);
        }
    }

    /**
     * Send the request through a set of controller middleware.
     *
     * @param  mixed  $instance
     * @param  string  $method
     * @param  array  $routeInfo
     * @param  array  $middleware
     * @return mixed
     */
    protected function callLumenControllerWithMiddleware($instance, $method, $routeInfo, $middleware)
    {
        $middleware = $this->gatherMiddlewareClassNames($middleware);

        return $this->sendThroughPipeline($middleware, function () use ($instance, $method, $routeInfo) {
            return $this->callControllerCallable([$instance, $method], $routeInfo[2]);
        });
    }

    /**
     * Call the callable for a controller action with the given parameters.
     *
     * @param  array  $callable
     * @param  array $parameters
     * @return mixed
     */
    protected function callControllerCallable(array $callable, array $parameters)
    {
        try {
            return $this->prepareResponse(
                $this->call($callable, $parameters)
            );
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * Gather the full class names for the middleware short-cut string.
     *
     * @param  string  $middleware
     * @return array
     */
    protected function gatherMiddlewareClassNames($middleware)
    {
        $middleware = is_string($middleware) ? explode('|', $middleware) : (array) $middleware;

        return array_map(function ($name) {
            list($name, $parameters) = array_pad(explode(':', $name, 2), 2, null);

            return array_get($this->routeMiddleware, $name, $name).($parameters ? ':'.$parameters : '');
        }, $middleware);
    }

    /**
     * Send the request through the pipeline with the given callback.
     *
     * @param  array  $middleware
     * @param  Closure  $then
     * @return mixed
     */
    protected function sendThroughPipeline(array $middleware, Closure $then)
    {
        $shouldSkipMiddleware = $this->bound('middleware.disable') &&
                                        $this->make('middleware.disable') === true;

        if (count($middleware) > 0 && ! $shouldSkipMiddleware) {
            return (new Pipeline($this))
                ->send($this->make('request'))
                ->through($middleware)
                ->then($then);
        }

        return $then();
    }

    /**
     * Prepare the response for sending.
     *
     * @param  mixed  $response
     * @return Response
     */
    public function prepareResponse($response)
    {
        if (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        }

        return $response->prepare($this->make('request'));
    }

    // Others ==================================================================

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return false;
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        //
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register a new boot listener.
     *
     * @param mixed $callback
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function booting($callback)
    {
        //
    }

    /**
     * Register a new "booted" listener.
     *
     * @param mixed $callback
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function booted($callback)
    {
        //
    }

    /**
     * Get the path to the cached "compiled.php" file.
     *
     * @return string
     */
    public function getCachedCompilePath()
    {
        throw new Exception(__FUNCTION__.' is not implemented by Luminous.');
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        throw new Exception(__FUNCTION__.' is not implemented by Luminous.');
    }

    /**
     * Register the core container aliases.
     *
     * @return void
     */
    protected function registerContainerAliases()
    {
        $this->aliases = [
            'Luminous\Application' => 'app',
            'Illuminate\Contracts\Foundation\Application' => 'app',
            'Illuminate\Contracts\Cache\Factory' => 'cache',
            'Illuminate\Contracts\Cache\Repository' => 'cache.store',
            'Illuminate\Contracts\Config\Repository' => 'config',
            'Illuminate\Container\Container' => 'app',
            'Illuminate\Contracts\Container\Container' => 'app',
            'Illuminate\Contracts\Cookie\Factory' => 'cookie',
            'Illuminate\Contracts\Cookie\QueueingFactory' => 'cookie',
            'Illuminate\Contracts\Encryption\Encrypter' => 'encrypter',
            'Illuminate\Contracts\Events\Dispatcher' => 'events',
            'Illuminate\Contracts\Filesystem\Factory' => 'filesystem',
            'log' => 'Psr\Log\LoggerInterface',
            'Illuminate\Contracts\Mail\Mailer' => 'mailer',
            'request' => 'Illuminate\Http\Request',
            'Illuminate\Session\SessionManager' => 'session',
            'Illuminate\Contracts\View\Factory' => 'view',
        ];
    }

    /**
     * The available container bindings and their respective load methods.
     *
     * @var array
     */
    protected $availableBindings = [
        'Illuminate\Contracts\Bus\Dispatcher' => 'registerBusBindings',
        'cache' => 'registerCacheBindings',
        'Illuminate\Contracts\Cache\Factory' => 'registerCacheBindings',
        'Illuminate\Contracts\Cache\Repository' => 'registerCacheBindings',
        'config' => 'registerConfigBindings',
        'cookie' => 'registerCookieBindings',
        'Illuminate\Contracts\Cookie\Factory' => 'registerCookieBindings',
        'Illuminate\Contracts\Cookie\QueueingFactory' => 'registerCookieBindings',
        'encrypter' => 'registerEncrypterBindings',
        'events' => 'registerEventBindings',
        'Illuminate\Contracts\Events\Dispatcher' => 'registerEventBindings',
        'Illuminate\Contracts\Encryption\Encrypter' => 'registerEncrypterBindings',
        'Illuminate\Contracts\Debug\ExceptionHandler' => 'registerExceptionHandlerBindings',
        'files' => 'registerFilesBindings',
        'filesystem' => 'registerFilesBindings',
        'Illuminate\Contracts\Filesystem\Factory' => 'registerFilesBindings',
        'log' => 'registerLogBindings',
        'Psr\Log\LoggerInterface' => 'registerLogBindings',
        'mailer' => 'registerMailBindings',
        'Illuminate\Contracts\Mail\Mailer' => 'registerMailBindings',
        'request' => 'registerRequestBindings',
        'router' => 'registerRouterBindings',
        'Illuminate\Http\Request' => 'registerRequestBindings',
        'session' => 'registerSessionBindings',
        'session.store' => 'registerSessionBindings',
        'Illuminate\Session\SessionManager' => 'registerSessionBindings',
        'translator' => 'registerTranslationBindings',
        'validator' => 'registerValidatorBindings',
        'view' => 'registerViewBindings',
        'Illuminate\Contracts\View\Factory' => 'registerViewBindings',
        'wp' => 'registerBridgeBindings',
    ];
}
