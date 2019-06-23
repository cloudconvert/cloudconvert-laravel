<?php


namespace CloudConvert\Laravel\Providers;

use CloudConvert\CloudConvert;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;


class CloudConvertServiceProvider extends ServiceProvider implements DeferrableProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cloudconvert.php' => config_path('cloudconvert.php'),
            ], 'config');
        }
        $this->mergeConfigFrom(__DIR__ . '/../config/cloudconvert.php', 'cloudconvert');

    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton(CloudConvert::class, function ($app) {
            return new CloudConvert(
                Arr::only($app['config']['cloudconvert'],
                    ['api_key', 'sandbox']
                )
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [CloudConvert::class];
    }

}
