<?php
namespace Megaads\Generatesitemap\Controllers;

use Dotenv\Dotenv;
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

        return response()->json(['status' => 'successful', 'message' => 'Sitemap created']);
    }

    private function multipleGenerate($localesKey, $index = 0) {
        if ($index == count($localesKey)) {
            return "success";
        }

        try {
            $this->loadDotEnv($localesKey[$index]);
            //Override database connection config
            $this->changeConfigurationDatabase();


            $this->addSitemapData('category', $this->categoryRouteName);
            $this->addSitemapData('store', $this->storeRouteName);

            $this->sitemapConfigurator->store('xml', $localesKey[$index].'-sitemap', true, $localesKey[$index]);

        } catch (\Exception $ex) {
            \Log::error("At locales " . $localesKey[$index] . ' ' . $ex->getMessage());
        }

        $this->multipleGenerate($localesKey, $index + 1);
    }

    private function changeConfigurationDatabase() {
        config(['database.connections.mysql.host' => getenv('DB_HOST')]);
        config(['database.connections.mysql.port' => getenv('DB_PORT')]);
        config(['database.connections.mysql.database' => getenv('DB_DATABASE')]);
        config(['database.connecitons.mysql.username' => getenv('DB_USERNAME')]);
        config(['database.connections.mysql.password' => getenv('DB_PASSWORD')]);
    }

    private function addSitemapData($table, $routeName,$columns=['slug']) {
        try {
            $tableItems = \DB::connection('mysql')
                ->table($table)
                ->get($columns);

            if ( !empty($tableItems) ) {
                foreach($tableItems as $item) {
                    if ($item->slug == 'root')
                        continue;

                    $piority = "0.8";
                    $lastMode = date('Y-m-d');
                    $changeFreq = 'daily';
                    $this->sitemapConfigurator->add(route($routeName, ['slug' => $item->slug]), $piority, $lastMode, $changeFreq);
                }
            }
            \DB::disconnect('mysql');
        } catch (\Exception $exception) {
            \DB::disconnect('mysql');
            throw new \Exception("Error database connection. Please check again");
            \Log::error($exception->getMessage());
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