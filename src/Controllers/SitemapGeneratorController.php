<?php 
namespace Megaads\Generatesitemap\Controllers;

use Megaads\Generatesitemap\Models\Stores;
use Megaads\Generatesitemap\Models\Categories;
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
        $this->sitemapConfigurator->add(route('frontend::home'), '1');
        $stores = Stores::get(['slug']);
        foreach ($stores as $store) {
            $piority = '0.8';
            $lastMode = date('Y-m-d');
            $changefreq = 'daily';
            $this->sitemapConfigurator->add(route($this->storeRouteName, ['slug' => $store->slug]), $piority, $lastMode, $changefreq);
        }

        $categories = Categories::get(['slug']);
        foreach ($categories as $category) {
            $piority = '0.8';
            $lastMode = date('Y-m-d');
            $changefreq = 'daily';
            $this->sitemapConfigurator->add(route($this->categoryRouteName, ['slug' => $category->slug]), $piority, $lastMode, $changefreq);
        }

        $this->sitemapConfigurator->store('xml', 'sitemap');

    }
}