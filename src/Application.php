<?php

namespace Luminous;

use Laravel\Lumen\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * Bootstrap the application container.
     *
     * @return void
     */
    protected function bootstrapContainer()
    {
        parent::bootstrapContainer();

        $this->loadComponent('wp', 'Luminous\Bridge\BridgeServiceProvider');
        $this->aliases['Luminous\Bridge\WP'] = 'wp';
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
     * Get the path to the given configuration file.
     *
     * @param string $name
     * @return string
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
     * Get the path to the application's language files.
     *
     * @return string
     */
    protected function getLanguagePath()
    {
        if (is_dir($appPath = $this->resourcePath('lang'))) {
            return $appPath;
        }

        return $this->frameworkBasePath('resources/lang');
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
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    /**
     * Handle a route found by the dispatcher.
     *
     * @param array $routeInfo
     * @return mixed
     */
    protected function handleFoundRoute($routeInfo)
    {
        if (isset($routeInfo[1]['query'])) {
            $routeInfo[2] = ['query' => array_merge($routeInfo[1]['query'], $routeInfo[2])];
        }

        return parent::handleFoundRoute($routeInfo);
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
}
