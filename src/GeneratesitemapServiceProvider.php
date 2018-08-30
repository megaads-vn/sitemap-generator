<?php 
namespace Megaads\Generatesitemap;

use Illuminate\Support\ServiceProvider;

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
        
    }
}