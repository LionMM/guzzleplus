<?php namespace LionMM\GuzzlePlus;

use Illuminate\Support\ServiceProvider;

class GuzzlePlusServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $configPath = __DIR__ . '/config/guzzleplus.php';
        $this->publishes([$configPath => $this->getConfigPath()], 'config');
    }

    public function register()
    {
        $configPath = __DIR__ . '/config/guzzleplus.php';
        $this->mergeConfigFrom($configPath, 'guzzleplus');

        $this->app->singleton('guzzleplus', function ($app) {
            return new GuzzlePlus($app);
        });
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('guzzleplus.php');
    }

    /**
     * Publish the config file
     *
     * @param  string $configPath
     */
    protected function publishConfig($configPath)
    {
        $this->publishes([$configPath => config_path('guzzleplus.php')], 'config');
    }
}