<?php

namespace Luminous\Http;

use Illuminate\Http\RedirectResponse;
use Luminous\Application;

/**
 * Redirector Class
 *
 * This class is based on Laravel Lumen:
 *
 * - Copyright (c) Taylor Otwell
 * - Licensed under the MIT license
 * - {@link https://github.com/laravel/lumen-framework/blob/5.1/src/Http/Redirector.php}
 */
class Redirector
{
    /**
     * The application instance.
     *
     * @var \Luminous\Application
     */
    protected $app;

    /**
     * Create a new redirector instance.
     *
     * @param \Luminous\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Create a new redirect response to the given path.
     *
     * @param array|string|mixed $parameters
     * @param int $status
     * @param array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function to($parameters, $status = 302, $headers = [])
    {
        $path = $this->app->make('router')->url($parameters, true);

        return $this->createRedirect($path, $status, $headers);
    }

    /**
     * Create a new redirect response to the previous location.
     *
     * @param int $status
     * @param array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    public function back($status = 302, $headers = [])
    {
        $referrer = $this->app->make('request')
                                ->headers->get('referer');

        $url = $referrer ? $this->app->make('url')->to($referrer)
                                : $this->app->make('session')->previousUrl();

        return $this->createRedirect($url, $status, $headers);
    }

    /**
     * Create a new redirect response.
     *
     * @param string $path
     * @param int $status
     * @param array $headers
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function createRedirect($path, $status, $headers)
    {
        $redirect = new RedirectResponse($path, $status, $headers);

        $redirect->setRequest($this->app->make('request'));

        $redirect->setSession($this->app->make('session.store'));

        return $redirect;
    }
}
