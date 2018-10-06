<?php
namespace Megaads\Generatesitemap\Controllers;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\DB;
use Megaads\Generatesitemap\Models\Stores;
use Megaads\Generatesitemap\Models\Categories;
use Illuminate\Routing\Controller as BaseController;

class SitemapGeneratorController extends BaseController
{
    protected $storeRouteName = 'frontend::store::listByStore';
    protected $categoryRouteName = 'frontend::category::listByCategory';
    protected $storePath = 'store/';
    protected $categoryPath = 'coupon-category/';
    protected $publicPath = null;
    private $sitemapConfigurator;
    protected $defaultLocale = '';

    /***
     * SitemapGeneratorController constructor.
     */
    public function __construct()
    {
        $this->publicPath = base_path() . '/public';
        $this->sitemapConfigurator = app()->make('sitemapConfigurator');
        $this->defaultLocale = config('generate-sitemap.defaultlocale');
    }

    /***
     * Generate sitemap from table
     * @return null
     */
    public function generate() {
        $isMultiple = config('generate-sitemap.multiplesitemap');
        if (!$isMultiple) {
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
        } else {
            $this->multipleGenerateSitemap();
        }

        return response()->json(['status' => 'successful', 'message' => 'Sitemap created']);
    }

    public function multipleGenerateSitemap() {
        $localesConfig = config('generate-sitemap.locales');
        $localesKey = array_keys($localesConfig);

        $this->multipleGenerate($localesKey);
        $this->sitemapConfigurator->mergeSitemap($localesKey);
    }

    private function multipleGenerate($localesKey, $index = 0) {
        if ($index == count($localesKey)) {
            return "success";
        }
        $this->loadDotEnv($localesKey[$index]);
        $this->changeConfigurationDatabase();
        try {
            $this->sitemapConfigurator->add(route('frontend::home') . '/' . $localesKey[$index], '1');
            $this->addSitemapData('category', route('frontend::home') . '/'. $localesKey[$index]  . '/' . $this->categoryPath . '#slug');
            $this->addSitemapData('store', route('frontend::home') . '/'. $localesKey[$index]  . '/' . $this->storePath . '#slug');

            if ($this->defaultLocale !== '' && $this->defaultLocale == $localesKey[$index]) {
                $this->addSitemapData('category', route($this->categoryRouteName, ['slug' => '#slug']));
                $this->addSitemapData('store', route($this->storeRouteName, ['slug' => '#slug']));
            }

            $this->sitemapConfigurator->store('xml', $localesKey[$index].'-sitemap', true, $localesKey[$index]);

        } catch (\Exception $ex) {
            \Log::error("At locales " . $localesKey[$index] . ' ' . $ex->getMessage());
        }

        $this->sitemapConfigurator->resetUrlSet();
        $this->sitemapConfigurator->resetXmlString();
        $this->multipleGenerate($localesKey, $index + 1);
    }

    private function changeConfigurationDatabase() {
        $config = config('database.connections.mysql');
        $config['database'] = getenv('DB_DATABASE');
        $config['username'] = getenv('DB_USERNAME');
        $config['password'] = getenv('DB_PASSWORD');
        config()->set('database.connections.mysql', $config);
    }

    private function addSitemapData($table, $routeName, $columns=['slug']) {
        try {
            $tableItems = DB::reconnect()
                ->table($table)
                ->get($columns);

            if ( !empty($tableItems) ) {
                foreach($tableItems as $item) {
                    if ($item->slug == 'root') continue;
                    $route = "";
                    $piority = "0.8";
                    $lastMode = date('Y-m-d');
                    $changeFreq = 'daily';
                    $route = str_replace('#slug', $item->slug, $routeName);
                    $this->sitemapConfigurator->add($route, $piority, $lastMode, $changeFreq);
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception("Error database connection. Please check again");
        }
    }

    private function loadDotEnv($localEnv) {
        try {
            $dotenv = new Dotenv(__DIR__.'/../../../../../', '.'.$localEnv.'.env');
            $dotenv->overload();
        } catch (\Exception $exception) {
            echo "Load env error " . $exception->getMessage();
        }
    }
}