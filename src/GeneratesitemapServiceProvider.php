<?php 
namespace Megaads\Generatesitemap;

use Illuminate\Support\ServiceProvider;
use Megaads\Generatesitemap\Services\SitemapConfigurator;

class GeneratesitemapServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/routes.php';
        }  
    }

    public function register() 
    {
        $this->app->singleton('sitemapConfigurator', function() {
            return new SitemapConfigurator();
        });
    }

    
}