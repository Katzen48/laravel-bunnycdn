<?php
/**
 * User: Katzen48
 * Date: 26.09.2020
 * Time: 13:25
 */

namespace Katzen48\LaravelBunnyCdn\Providers;

use Illuminate\Support\ServiceProvider;
use Storage;
use BunnyCDN\Storage\BunnyCDNStorage;
use PlatformCommunity\Flysystem\BunnyCDN\BunnyCDNAdapter;
use League\Flysystem\Filesystem;

class BunnyCdnServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/bunnycdn.php', 'bunnycdn'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/bunnycdn.php' => config_path('bunnycdn.php')
        ], 'config');

        Storage::extend('bunnycdn', function($app, $config)
        {
            $client = new BunnyCDNAdapter(new BunnyCDNStorage(config('bunnycdn.storage.zone'), config('bunnycdn.storage.apikey'), config('bunnycdn.storage.region')));

            return new Filesystem($client);
        });
    }
}
