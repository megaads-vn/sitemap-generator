<?php
namespace Megaads\Generatesitemap;

use Illuminate\Support\ServiceProvider;
use Megaads\Generatesitemap\Services\SitemapConfigurator;

class GeneratesitemapServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $framework = $this->checkFrameWork();
        if ($framework && $framework['key'] == 'laravel/framework' && $framework['version'] >= 52 ) {
            include __DIR__ . '/routes.php';
        } else {  
            if ( method_exists($this, 'routesAreCached') ) {
                if (!$this->app->routesAreCached()) {
                    include __DIR__ . '/routes.php';
                }
            }
        }
        $this->registerHelpers();
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

     /**
     * Register helpers file
     */
    public function registerHelpers()
    {
        $file = __DIR__ . '/helpers.php';
        if (file_exists($file))
        {
            require $file;
        }
    }

    private function getConfigPath()
    {
        return __DIR__.'/../config/generate-sitemap.php';
    }

    private function checkFrameWork() {
        $findFrameWork = ['laravel/framework','laravel/lumen-framework'];
        $frameworkDeclare = file_get_contents(__DIR__ . '../../../../../composer.json');
        $frameworkDeclare = json_decode($frameworkDeclare, true);
        $required =  array_key_exists('require', $frameworkDeclare) ? $frameworkDeclare['require'] : [];
        $requiredKeys = [];
        if ( !empty($required) ) {
            $requiredKeys = array_keys($required);
            foreach($requiredKeys as $key) {
                if ( in_array($key, $findFrameWork) ) {
                    $version = $required[$key];
                    $version = str_replace('*', '',$version);
                    $version = preg_replace('/\./', '', $version);
                    return ['key' => $key, 'version' => (int) $version];
                }
            }
        }
        return NULL;
    }

}