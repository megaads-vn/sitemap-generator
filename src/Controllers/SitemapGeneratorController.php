<?php

namespace Megaads\Generatesitemap\Controllers;

use Dotenv\Dotenv;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Megaads\Generatesitemap\Models\Categories;
use Megaads\Generatesitemap\Models\Stores;
use Illuminate\Support\Facades\Input;
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

    /***
     * SitemapGeneratorController constructor.
     */
    public function __construct()
    {
        $this->baseUrl = URL::to('/');
        $this->publicPath = base_path() . '/public';
        $this->sitemapConfigurator = app()->make('sitemapConfigurator');
    }

    /***
     * Generate sitemap from table
     * @return null
     */
    public function generate()
    {
        $isMultiple = Input::get('is_multiple', false);
        if (!$isMultiple) {
            $this->sitemapConfigurator->add(route('frontend::home'), '1');
            $stores = Stores::get(['slug']);
            foreach ($stores as $store) {
                $piority = '0.8';
                $lastMode = date('Y-m-d');
                $changefreq = 'daily';
                $this->sitemapConfigurator->add($this->baseUrl . $this->storeRouteName . $store->slug, $piority, $lastMode, $changefreq);
            }

            if(Schema::hasTable('store_n_keyword')) {
                $keywords = StoreKeyword::get(['slug']);
                foreach ($keywords as $keyword) {
                    $piority = '0.8';
                    $lastMode = date('Y-m-d');
                    $changefreq = 'daily';
                    $this->sitemapConfigurator->add($this->baseUrl . $this->store_n_keywordRouteName . $keyword->slug, $lastMode, $changefreq);
                }
                $this->sitemapConfigurator->store('xml', 'sitemap');
            }

            $categories = Categories::get(['slug']);
            foreach ($categories as $category) {
                $piority = '0.8';
                $lastMode = date('Y-m-d');
                $changefreq = 'daily';
                $this->sitemapConfigurator->add($this->baseUrl . $this->categoryRouteName . $category->slug, $piority, $lastMode, $changefreq);
            }
            $this->sitemapConfigurator->store('xml', 'sitemap');
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
                    $routeName = $type . 'RouteName';
                    $this->addSitemapData($type, $this->$routeName);
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

    private function addSitemapData($table, $routeName, $columns = ['slug'])
    {

        try {
            $buildQuery = DB::reconnect()
                            ->table($table);
            if ($table == 'coupon') {
                $buildQuery->where('status', 'active');
            }
            if ($table == 'category') {
                $this->sitemapConfigurator->add(route('frontend::home'), 1, date('Y-m-d'), 'daily');
            }
            $tableItems =  $buildQuery->get($columns);
            if (!empty($tableItems)) {
                foreach ($tableItems as $item) {
                    if ($item->slug == 'root') continue;
                    $route = "";
                    $piority = "0.8";
                    $lastMode = date('Y-m-d');
                    $changeFreq = 'daily';
                    $route =  $this->baseUrl . $routeName . $item->slug;
                    $route = $this->formatRoute($route);
                    $this->sitemapConfigurator->add(urldecode($route), $piority, $lastMode, $changeFreq);
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception("Error database connection. Please check again");
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