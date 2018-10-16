<?php

namespace Megaads\Generatesitemap\Controllers;

use Dotenv\Dotenv;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Megaads\Generatesitemap\Models\Categories;
use Megaads\Generatesitemap\Models\Stores;

class SitemapGeneratorController extends BaseController
{
    protected $storeRouteName = 'frontend::store::listByStore';
    protected $categoryRouteName = 'frontend::category::listByCategory';
    protected $blogRouteName = 'frontend::blog::detail';

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
    public function generate()
    {
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

    public function multipleGenerateSitemap()
    {
        $localesConfig = config('app.locales', []);
        $localesKey = Request::segment(1);
        if (array_key_exists($localesKey, $localesConfig)) {
            $this->multipleGenerate($localesKey);
        }
    }

    private function multipleGenerate($localesKey, $index = 0)
    {
        $this->loadDotEnv($localesKey);
        $this->changeConfigurationDatabase();
        try {
            $this->sitemapConfigurator->add(route('frontend::home'), '1');
            $this->addSitemapData('category', route($this->categoryRouteName, ['slug' => '#slug']));
            $this->addSitemapData('store', route($this->storeRouteName, ['slug' => '#slug']));
            $this->addSitemapData('blog', route($this->blogRouteName, ['slug' => '#slug']));
            $this->sitemapConfigurator->store('xml', $localesKey . '-sitemap', true, $localesKey);

        } catch (\Exception $ex) {
            \Log::error("At locales " . $localesKey . ' ' . $ex->getMessage());
        }

        $this->sitemapConfigurator->resetUrlSet();
        $this->sitemapConfigurator->resetXmlString();
    }

    private function changeConfigurationDatabase()
    {
        $config = config('database.connections.mysql');
        $config['database'] = getenv('DB_DATABASE');
        $config['username'] = getenv('DB_USERNAME');
        $config['password'] = getenv('DB_PASSWORD');
        config()->set('database.connections.mysql', $config);
    }

    private function addSitemapData($table, $routeName, $columns = ['slug'])
    {
        try {
            $tableItems = DB::reconnect()
                ->table($table)
                ->get($columns);

            if (!empty($tableItems)) {
                foreach ($tableItems as $item) {
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

    private function loadDotEnv($localEnv)
    {
        try {
            $dotenv = new Dotenv(__DIR__ . '/../../../../../', '.' . $localEnv . '.env');
            $dotenv->overload();
        } catch (\Exception $exception) {
            echo "Load env error " . $exception->getMessage();
        }
    }

    public function generateAll()
    {
        $configLocales = config('app.locales', []);
        $listLocaleSuccess = [];
        foreach ($configLocales as $keyLocale => $nameLocale) {
            $url = config('app.domain') . '/' . $keyLocale . '/sitemap-generator';
            $request = $this->curlRequest($url);
            if (isset($request->status) && $request->status == 'successful') {
                $listLocaleSuccess[] = $nameLocale;
            }
        }
        $this->sitemapConfigurator->mergeSitemap(array_keys($configLocales));
        return response()->json(['status' => 'successful', 'message' => 'List sitemap created: ' . implode(', ', $listLocaleSuccess)]);
    }

    private function curlRequest($url, $data = [], $method = "GET", $isAsync = false)
    {
        $channel = curl_init();
        curl_setopt($channel, CURLOPT_URL, $url);
        curl_setopt($channel, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($channel, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($channel, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($channel, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($channel, CURLOPT_MAXREDIRS, 3);
        curl_setopt($channel, CURLOPT_POSTREDIR, 1);
        curl_setopt($channel, CURLOPT_TIMEOUT, 10);
        curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 10);
        if ($isAsync) {
            curl_setopt($channel, CURLOPT_NOSIGNAL, 1);
            curl_setopt($channel, CURLOPT_TIMEOUT_MS, 400);
        }
        $response = curl_exec($channel);
        return json_decode($response);
    }
}