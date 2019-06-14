<?php
namespace Megaads\Generatesitemap;

use Illuminate\Support\ServiceProvider;
use Megaads\Generatesitemap\Services\SitemapConfigurator;

class GeneratesitemapServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // if ( method_exists($this, 'routesAreCached') ) {
            // if (!$this->app->routesAreCached()) {
                include __DIR__ . '/routes.php';
            // }
        // }
        $this->publishConfig();
    }

    public function register()
    {
        $this->app->singleton('sitemapConfigurator', function() {
            return new SitemapConfigurator();
        });
    }

    private function publishConfig()
    {
        if ( method_exists($this, 'config_path') ) {
            $path = $this->getConfigPath();
            $this->publishes([$path => config_path('generate-sitemap.php')], 'config');
        }
    }

    private function getConfigPath()
    {
        return __DIR__.'/../config/generate-sitemap.php';
    }


}