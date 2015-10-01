<?php

namespace Luminous;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Laravel\Lumen\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * {@inheritdoc}
     */
    protected function bootstrapContainer()
    {
        parent::bootstrapContainer();

        $this->availableBindings['wp'] = 'registerBridgeBidings';
        $this->availableBindings['modified'] = 'registerModifiedBidings';
    }

    /**
     * {@inheritdoc}
     */
    public function version()
    {
        static $version = null;

        if (is_null($version)) {
            foreach (file(__DIR__.'/../style.css') as $line) {
                if (preg_match('/^Version:\s*(.+)$/', $line, $m)) {
                    $version = $m[1];
                    break;
                }
            }
        }

        return "Luminous ({$version}) / ".parent::version();
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerBridgeBidings()
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
    protected function registerModifiedBidings()
    {
        $this->bind('modified', function () {
            static $timestamp = null;

            if (is_null($timestamp)) {
                $timestamp = @filemtime($this->basePath('style.css')) ?: 0;
            }

            return Carbon::createFromTimeStamp($timestamp);
        });
    }

    /**
     * {@inheritdoc}
     *
     * Remove magic quotes from `$_GET`, `$_POST`, `$_COOKIE`, and `$_SERVER`.
     *
     * @see \wp_magic_quotes() https://developer.wordpress.org/reference/functions/wp_magic_quotes/
     * @uses \stripslashes_deep()
     */
    protected function registerRequestBindings()
    {
        $this->singleton('Illuminate\Http\Request', function () {
            Request::enableHttpMethodParameterOverride();

            $base = SymfonyRequest::createFromGlobals();

            foreach (['request', 'query', 'cookies', 'server', 'headers'] as $param) {
                $striped = array_map('stripslashes_deep', $base->{$param}->all());
                $base->{$param}->replace($striped);
            }

            return Request::createFromBase($base)->setRouteResolver(function () {
                return $this->currentRoute;
            });
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigurationPath($name)
    {
        $appConfigPath = ($this->configPath ?: $this->basePath('config')).'/'.$name.'.php';
        if (file_exists($appConfigPath)) {
            return $appConfigPath;
        } elseif (file_exists($path = $this->frameworkBasePath('config/'.$name.'.php'))) {
            return $path;
        }

        return parent::getConfigurationPath($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLanguagePath()
    {
        if (is_dir($appPath = $this->resourcePath('lang'))) {
            return $appPath;
        }

        return $this->frameworkBasePath('resources/lang');
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
     * Register a route with the application.
     *
     * @param string $uri
     * @param mixed $action
     * @return $this
     */
    public function any($uri, $action)
    {
        foreach (['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $action);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($request = null)
    {
        $this->configure('assets');
        $this->make('wp');

        return parent::dispatch($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleFoundRoute($routeInfo)
    {
        if (isset($routeInfo[1]['query'])) {
            $routeInfo[2] = ['query' => array_merge($routeInfo[1]['query'], $routeInfo[2])];
        }

        return parent::handleFoundRoute($routeInfo);
    }
}
