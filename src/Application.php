<?php

namespace Luminous;

use Exception;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Luminous\Asset\Asset;
use Luminous\Routing\Dispatcher;
use Luminous\Routing\Router;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Application Class
 *
 * This class is based on Laravel Lumen:
 *
 * - Copyright (c) Taylor Otwell
 * - Licensed under the MIT license
 * - {@link https://github.com/laravel/lumen-framework/blob/5.1/src/Application.php}
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
     * @param string|null $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        $this->basePath = $basePath;
        $this->bootstrapContainer();

        $this->make('Luminous\Exceptions\Bootstrap')->register($this);
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
     * @param array|string,... $patterns
     * @return string|bool
     */
    public function environment($patterns = null)
    {
        $env = env('APP_ENV', 'production');

        if (func_num_args() > 0) {
            $patterns = is_array($patterns) ? $patterns : func_get_args();
            foreach ($patterns as $pattern) {
                if (Str::is($pattern, $env)) {
                    return true;
                }
            }
            return false;
        }

        return $env;
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() === 'cli';
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
     * @param string $path
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
     * @param string $path
     * @return string
     */
    public function storagePath($path = null)
    {
        return $this->basePath().'/storage'.($path ? '/'.$path : $path);
    }

    /**
     * Get the resource path for the application.
     *
     * @param string $path
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
     * @param \Illuminate\Support\ServiceProvider|string  $provider
     * @param array $options
     * @param bool $force
     * @return \Illuminate\Support\ServiceProvider
     *
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
     * @param string $provider
     * @param string $service
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        return $this->register($provider);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
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
     * @param string $config
     * @param array|string $providers
     * @param string|null $return
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
     * @param string $name
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
    protected function registerAssetBindings()
    {
        $this->singleton('asset', function () {
            $this->configure('asset');
            $config = $this->make('config')->get('asset');
            return new Asset($config['manifest'], $config['prefix']);
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerDispatcherBindings()
    {
        $this->singleton('dispatcher', function () {
            return new Dispatcher($this, $this->make('router'));
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerRouterBindings()
    {
        $this->singleton('router', function () {
            $context = $this->make('wp')->option('url');
            return (new Router($this))->setContext($context);
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
            return $this->prepareRequest(Request::capture())->setRouteResolver(function () {
                return $this->make('dispatcher')->currentRoute();
            });
        });
    }

    /**
     * Remove magic quotes from `$_GET`, `$_POST`, `$_COOKIE`, and `$_SERVER`.
     *
     * @see \wp_magic_quotes() https://developer.wordpress.org/reference/functions/wp_magic_quotes/
     * @uses \stripslashes_deep()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function prepareRequest(SymfonyRequest $request)
    {
        if (function_exists('wp_magic_quotes')) {
            foreach (['request', 'query', 'cookies', 'server', 'headers'] as $param) {
                $striped = array_map('stripslashes_deep', $request->{$param}->all());
                $request->{$param}->replace($striped);
            }
        }

        return $request;
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
            unset($this['validation.presence']);

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

    // Request Handring ========================================================

    /**
     * Add new middleware to the application.
     *
     * @param array $middleware
     * @return $this
     */
    public function middleware(array $middleware)
    {
        $this->make('dispatcher')->middleware($middleware);

        return $this;
    }

    /**
     * Define the route middleware for the application.
     *
     * @param array $middleware
     * @return $this
     */
    public function routeMiddleware(array $middleware)
    {
        $this->make('dispatcher')->routeMiddleware($middleware);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        return $this->dispatch(Request::createFromBase($request));
    }

    /**
     * Run the application and send the response.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return void
     */
    public function run(Request $request = null)
    {
        if ($request) {
            $this->instance('Illuminate\Http\Request', $request);
            $this->ranServiceBinders['registerRequestBindings'] = true;
        } else {
            $request = $this->make('request');
        }

        $response = $this->dispatch($request);
        $response->send();

        $this->make('dispatcher')->terminate($request, $response);
    }

    /**
     * Dispatch the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch(Request $request)
    {
        return $this->make('dispatcher')->dispatch($request);
    }

    // Implementations for \Illuminate\Contracts\Foundation\Application ========

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
     *
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
     *
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

    // Bindings ================================================================

    /**
     * Register the core container aliases.
     *
     * @return void
     */
    protected function registerContainerAliases()
    {
        $this->aliases = [
            'Luminous\Application' => 'app',
            'Luminous\Bridge\WP' => 'wp',
            'Luminous\Asset\Asset' => 'asset',
            'Luminous\Routing\Dispatcher' => 'dispatcher',
            'Luminous\Routing\Router' => 'router',
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
        'wp' => 'registerBridgeBindings',
        'asset' => 'registerAssetBindings',
        'dispatcher' => 'registerDispatcherBindings',
        'router' => 'registerRouterBindings',
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
        'Illuminate\Http\Request' => 'registerRequestBindings',
        'session' => 'registerSessionBindings',
        'session.store' => 'registerSessionBindings',
        'Illuminate\Session\SessionManager' => 'registerSessionBindings',
        'translator' => 'registerTranslationBindings',
        'validator' => 'registerValidatorBindings',
        'view' => 'registerViewBindings',
        'Illuminate\Contracts\View\Factory' => 'registerViewBindings',
    ];
}
