<?php 
namespace Megaads\Generatesitemap\Controllers;

use Megaads\Generatesitemap\Models\Stores;
use Illuminate\Routing\Controller as BaseController;

class SitemapGeneratorController extends BaseController
{
    protected $storeRouteName = 'frontend::store::listByStore';
    protected $categoryRouteName = 'frontend::category::listByCategory';
    protected $publicPath = null;
    private $sitemapConfigurator;

    /***
     * SitemapGeneratorController constructor.
     */
    public function __construct()
    {
        $this->publicPath = base_path() . '/public';
        $this->sitemapConfigurator = app()->make('sitemapConfigurator');
    }

    /***
     * Generate sitemap from table
     * @return null
     */
    public function generate() {

        $stores = Stores::get(['slug']);
        foreach ($stores as $store) {
            $piority = '0.8';
            $lastMode = date('Y-m-d');
            $changefreq = 'daily';
            $this->sitemapConfigurator->add(route('frontend::store::listByStore', ['slug' => $store->slug]), $piority, $lastMode, $changefreq);
        }
        $this->sitemapConfigurator->store('xml', 'sitemap');
    }
}