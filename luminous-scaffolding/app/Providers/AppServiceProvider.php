<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerViewData();
    }

    /**
     * Register view data.
     *
     * @return void
     */
    protected function registerViewData()
    {
        view()->share('wp', $wp = app('wp'));

        view()->share('site', (object) [
            'name'          => $wp->option('blogname'),
            'description'   => $wp->option('blogdescription'),
            'url'           => $wp->option('home'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
