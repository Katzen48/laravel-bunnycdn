<?php
/**
 * User: Katzen48
 * Date: 26.09.2020
 * Time: 13:25
 */

namespace Katzen48\LaravelBunnyCdn\Providers;

use Illuminate\Support\ServiceProvider;
use Storage;
use Katzen48\LaravelBunnyCdn\Filesystem\BunnyCDNStorage;
use Katzen48\LaravelBunnyCdn\Filesystem\BunnyCDNAdapter;
use League\Flysystem\Filesystem;

class BunnyCdnServiceProvider extends ServiceProvider
{
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
            $client = new BunnyCDNAdapter(new BunnyCDNStorage(config['zone'], config['apikey'], config['region']));

            return new Filesystem($client);
        });
    }
}
