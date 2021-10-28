<?php

namespace Megaads\Generatesitemap\Controllers;

use Config;
use Dotenv\Dotenv;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Megaads\Generatesitemap\Models\Categories;
use Megaads\Generatesitemap\Models\Stores;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Megaads\Generatesitemap\Models\StoreKeyword;
use Schema;
use URL;

class SitemapGeneratorController extends BaseController
{

    protected $baseUrl = '';
    protected $storeRouteName = '/store/';
    protected $categoryRouteName = '/coupon-category/';
    protected $store_n_keywordRouteName = '/';
    protected $blogRouteName = '/blog-detail/';
    protected $couponRouteName = '/coupons/';

    protected $publicPath = null;
    private $sitemapConfigurator;
    protected $routeConfig = NULL;

    /***
     * SitemapGeneratorController constructor.
     */
    public function __construct()
    {
        $this->baseUrl = URL::to('/');
        $this->publicPath = base_path() . '/public';
        $this->sitemapConfigurator = app()->make('sitemapConfigurator');
        $this->routeConfig = config('generate-sitemap.routes');
    }

    /***
     * Generate sitemap from table
     * @return null
     */
    public function generate()
    {
        $isMultiple = Input::get('is_multiple', false);
        $isByLocales = Input::get('multiple_locales', false);
        if (!$isMultiple) {
            $this->sitemapConfigurator->add(route('frontend::home'), '1');
            $stores = Stores::get(['slug']);
            foreach ($stores as $store) {
                $piority = '0.8';
                $lastMode = date('Y-m-d');
                $changefreq = 'daily';
                $url = route($this->routeConfig['store'], ['slug' => htmlspecialchars($store->slug)]);
                $this->sitemapConfigurator->add($url, $piority, $lastMode, $changefreq);
            }

            if(Schema::hasTable('store_n_keyword')) {
                $keywords = StoreKeyword::get(['slug']);
                foreach ($keywords as $keyword) {
                    $piority = '0.8';
                    $lastMode = date('Y-m-d');
                    $changefreq = 'daily';
                    $this->sitemapConfigurator->add($this->baseUrl . $this->store_n_keywordRouteName . htmlspecialchars($keyword->slug), $lastMode, $changefreq);
                }
                $this->sitemapConfigurator->store('xml', 'sitemap');
            }

            $categories = Categories::get(['slug']);
            foreach ($categories as $category) {
                $piority = '0.8';
                $lastMode = date('Y-m-d');
                $changefreq = 'daily';
                $url = route($this->routeConfig['category'], ['slug' => htmlspecialchars($category->slug)]);
                $this->sitemapConfigurator->add($url, $piority, $lastMode, $changefreq);
            }
            $this->sitemapConfigurator->store('xml', 'sitemap');
        } else if ($isByLocales && $isMultiple) {
            $this->multipleByLocales();
        } else {
            $this->multipleGenerateSitemap();
        }

        return response()->json(['status' => 'successful', 'message' => 'Sitemap created']);
    }

    public function multipleGenerateSitemap()
    {
        $sitemapType = config('generate-sitemap.sitemaptype');
        try {
            $mergePath = [];
            foreach( $sitemapType as $key =>  $type ) {
                if(Schema::hasTable($type)) {
                    $routeName = $this->routeConfig[$type];
                    $this->addSitemapData($type, $routeName);
                    $this->sitemapConfigurator->store('xml', $type . '-sitemap', true, $key);
                    $this->sitemapConfigurator->resetUrlSet();
                    $this->sitemapConfigurator->resetXmlString();
                    $mergePath[] = $key . '/' . $type .'-sitemap.xml';
                }
            }
            $this->sitemapConfigurator->mergeSitemap($mergePath);
        } catch (\Exception $ex) {
            throw new \Exception("Error generate. " .  $ex->getMessage());
        }
    }

    public function sitemapByLocales() {
        $listLocaleSuccess = [];
        $listLocaleFail = [];
        $locales = config('generate-sitemap.locales');
        $mergePath = [];
        foreach ($locales as $key => $name) {
            $this->sitemapConfigurator->store('xml', 'sitemap', true, $key);
            $this->sitemapConfigurator->resetUrlSet();
            $this->sitemapConfigurator->resetXmlString();
            $mergePath[] = $key . '/sitemap.xml';
            if ($key == 'us'){
                $url = config('app.domain') . '/sitemap-generator?is_multiple=true&multiple_locales=true';
            }else{
                $url = config('app.domain') . '/' . $key . '/sitemap-generator?is_multiple=true&multiple_locales=true';
            }
            $request = $this->curlRequest($url);
            if (isset($request->status) && $request->status == 'successful') {
                $listLocaleSuccess[] = $name;
            } else {
                $listLocaleFail[$name] = $request;
            }
        }
        $this->sitemapConfigurator->mergeSitemap($mergePath,'sitemap_index');
        return response()->json(['status' => 'successful', 'message' => 'List sitemap created: ' . implode(', ', $listLocaleSuccess), 'fail' => $listLocaleFail]);
    }

    private function multipleByLocales() {
        $locales = config('generate-sitemap.locales');
        $types = config('generate-sitemap.sitemaptype');
        try {
            $uri = array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : '';
            preg_match('/\/([A-Za-z]+)(\/*)/', $uri, $locales);
            $locale = $locales[1];
            if (!in_array($locale,$locales)){
                $locale = 'us';
            }
            foreach ($types as $key => $type) {
                if (Schema::hasTable($type)) {
                    $routeName = $this->routeConfig[$type];
                    $this->addSitemapData($type, $routeName, ['slug']);

                } else if ($key == 'menu') {
                    $staticRoutes = config('generate-sitemap.' . $type);
                    foreach ($staticRoutes as $routeName) {
                        $piority = "0.8";
                        $lastMode = date('Y-m-d');
                        $changeFreq = 'daily';
                        $route =  route($routeName);
                        $this->sitemapConfigurator->add($route, $piority, $lastMode, $changeFreq);
                    }
                }
            }
            $this->sitemapConfigurator->store('xml', 'sitemap', true, $locale . '/');
            $this->sitemapConfigurator->resetUrlSet();
            $this->sitemapConfigurator->resetXmlString();
        } catch (\Exception $ex) {
            throw new \Exception("Error generate. " . $ex->getMessage());
        }
    }

    private function addSitemapData($table, $routeName, $columns = ['slug'])
    {
        try {
            $buildQuery = DB::reconnect()
                ->table($table);
            if ($table == 'coupon') {
                $buildQuery->where('status', 'active');
            }
            if ($table == 'store_n_keyword') {
                $buildQuery->where('visibility', 'visible');
            }
            if ($table == 'category') {
                $this->sitemapConfigurator->add(route('frontend::home'), 1, date('Y-m-d'), 'daily');
            }
            $tableItems =  $buildQuery->get($columns);
            if (!empty($tableItems)) {
                foreach ($tableItems as $item) {
                    if ($item->slug == 'root') continue;
                    if (Route::has($routeName)) {
                        $route = "";
                        $piority = "0.8";
                        $lastMode = date('Y-m-d');
                        $changeFreq = 'daily';
                        $route =  route($routeName, ['slug' => htmlspecialchars($item->slug)]);
                        $route = $this->formatRoute($route);
                        $this->sitemapConfigurator->add(urldecode($route), $piority, $lastMode, $changeFreq);
                    }
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception("Error database connection. Please check again.");
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

    private function formatRoute($route) {
        $parseUrl = parse_url($route);
        $urlPaths = explode('/', $parseUrl['path']);
        //Get last path to encode special characters
        $lastUrlPath = end($urlPaths);
        //Remove last path.
        array_pop($urlPaths);

        return $parseUrl['scheme']  . '://' . $parseUrl['host'] . join('/', $urlPaths) . '/' . urlencode($lastUrlPath);
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
