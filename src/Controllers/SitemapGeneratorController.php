<?php

namespace Megaads\Generatesitemap\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Store;
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

    /**
     * 
     */
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

    /**
     * 
     */
    public function sitemapByAlphabet()
    {
        $response = [
            'status' => 'successful'
        ];
        $mergePath = [];
        $this->generateStores($mergePath);
        $this->generateBlog($mergePath);
        $this->generateCategories($mergePath);
        $this->generateKeypages($mergePath);
        
        foreach ($mergePath as $item) {
            $this->sitemapConfigurator->mergeSingleSitemap($item, 'sitemap');
        }
        return response()->json($response);
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
            preg_match('/\/([A-Za-z]+)(\/*)/', $uri, $matches);
            $locale = $matches[1];
            if (!isset($locales[$locale])){
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

    protected function generateStores(&$mergePath)
    {
        $total = Store::where('status', Store::STATUS_ENABLE)->count();
        $limit = 200;
        $page = 0;
        $alphabetItems = [];
        foreach (range('a', 'z') as $char) {
            $alphabetItems[$char] = [];
        }
        if ($total > 0) {
            $page = ceil($total / $limit);
            $this->getStore(0, $limit, $page, $alphabetItems);
            $this->addToAlphabetSiteMap($alphabetItems, $mergePath);
        }
    }

    /**
     * Add store to xml file separate by alphabet
     * 
     * @param array items
     * 
     * @return null
     */
    protected function addToAlphabetSiteMap($items, &$mergePath)
    {
        foreach ($items as $char => $childs) {
            foreach ($childs as $child) {
                    $piority = '0.8';
                    $lastMode = date('Y-m-d');
                    $changefreq = 'daily';
                    $url = route($this->routeConfig['store'], ['slug' => htmlspecialchars($child)]);
                    $this->sitemapConfigurator->add($url, $piority, $lastMode, $changefreq);            
            }
            $this->sitemapConfigurator->store('xml', 'stores-' . $char, true, '', '');
            $this->sitemapConfigurator->resetUrlSet();
            $this->sitemapConfigurator->resetXmlString();
            $mergePath[] = '/stores-' . $char . '.xml';
        }
    }

    /**
     * Recursive function select store slug and add to array separate by alphabet
     * 
     * @param integer page
     * @param integer limit
     * @param integer total
     * 
     * @return boolean true
     */
    protected function getStore($page, $limit, $total, &$alphabetItems)
    {
        if ($total < ($page + 1)) {
            return true;
        }
        $stores = Store::where('status', Store::STATUS_ENABLE)
                    ->offset($page * $limit)
                    ->limit($limit)
                    ->get(['slug']);
        if (count($stores) > 0) {
            $count = 0;
            foreach ($stores as $item) {
                $count++;
                $firstChar = strtolower($item->slug[0]);
                if (is_numeric($firstChar)) {
                    $alphabetItems['0-9'][] = $item->slug;
                } else {
                    $alphabetItems[$firstChar][] = $item->slug;
                }
            }
        }
        $page = $page + 1;
        return $this->getStore($page, $limit, $total, $alphabetItems);
    }

    /**
     * 
     */
    protected function generateBlog(&$mergePath)
    {
        $limit = 200;
        $total = Blog::where('status', Blog::STATUS_ACTIVE)->count();
        $path = [];
        if ($total > 0) {
            $page = ceil($total / $limit);
            $this->getBlog(0, $limit, $page, $path);
            if (count($path) > 0) {
                foreach ($path as $item) {
                    $piority = '0.8';
                    $lastMode = date('Y-m-d');
                    $changefreq = 'daily';
                    $url = $item;
                    $this->sitemapConfigurator->add($url, $piority, $lastMode, $changefreq);   
                }
                $this->sitemapConfigurator->store('xml', 'blog', true, '', '');
                $this->sitemapConfigurator->resetUrlSet();
                $this->sitemapConfigurator->resetXmlString();
                $mergePath[] = '/blog.xml';
            }
        }
    }

    /**
     * 
     * 
     */
    protected function getBlog ($page, $limit, $total, &$path)
    {
        if ($total < ($page + 1)) {
            return true;
        }
        $blogs = Blog::where('status', Blog::STATUS_ACTIVE)
                    ->limit($limit)
                    ->offset($limit * $page)
                    ->get(['slug']);
        if (count($blogs) > 0) {
            foreach ($blogs as $item) {
                $path[] = route($this->routeConfig['blog'], ['slug' => htmlspecialchars($item->slug)]);
            }
        }
        $page = $page + 1;
        $this->getBlog($page, $limit, $total, $path);
    }

    /**
     * 
     */
    protected function generateCategories(&$mergePath)
    {
        $limit = 200;
        $total = Category::where('status', Category::STATUS_ENABLE)->count();
        $path = [];
        if ($total > 0) {
            $page = ceil($total / $limit);
            $this->getCategory(0, $limit, $page, $path);
            if (count($path) > 0) {
                foreach ($path as $item) {
                    $piority = '0.8';
                    $lastMode = date('Y-m-d');
                    $changefreq = 'daily';
                    $url = $item;
                    $this->sitemapConfigurator->add($url, $piority, $lastMode, $changefreq);   
                }
                $this->sitemapConfigurator->store('xml', 'categories', true, '', '');
                $this->sitemapConfigurator->resetUrlSet();
                $this->sitemapConfigurator->resetXmlString();
                $mergePath[] = '/categories.xml';
            }
        }
    }

    /**
     * 
     * 
     */
    protected function getCategory ($page, $limit, $total, &$path)
    {
        if ($total < ($page + 1)) {
            return true;
        }
        $categories = Category::where('status', Category::STATUS_ENABLE)
                    ->limit($limit)
                    ->offset($limit * $page)
                    ->get(['slug']);
        if (count($categories) > 0) {
            foreach ($categories as $item) {
                $path[] = route($this->routeConfig['category'], ['slug' => htmlspecialchars($item->slug)]);
            }
        }
        $page = $page + 1;
        $this->getCategory($page, $limit, $total, $path);
    }

    /**
     * 
     */
    protected function generateKeypages(&$mergePath)
    {
        $limit = 200;
        $total = StoreKeyword::count();
        $path = [];
        if ($total > 0) {
            $page = ceil($total / $limit);
            $this->getKeypage(0, $limit, $page, $path);
            if (count($path) > 0) {
                foreach ($path as $item) {
                    $piority = '0.8';
                    $lastMode = date('Y-m-d');
                    $changefreq = 'daily';
                    $url = $item;
                    $this->sitemapConfigurator->add($url, $piority, $lastMode, $changefreq);   
                }
                $this->sitemapConfigurator->store('xml', 'keypages', true, '', '');
                $this->sitemapConfigurator->resetUrlSet();
                $this->sitemapConfigurator->resetXmlString();
                $mergePath[] = '/keypages.xml';
            }
        }
    }

    /**
     * 
     * 
     */
    protected function getKeypage ($page, $limit, $total, &$path)
    {
        if ($total < ($page + 1)) {
            return true;
        }
        $keywords = StoreKeyword::limit($limit)
                    ->offset($limit * $page)
                    ->get(['slug']);
        if (count($keywords) > 0) {
            foreach ($keywords as $item) {
                if (Route::has($this->routeConfig['store_n_keyword'])) {
                    $path[] = route($this->routeConfig['store_n_keyword'], ['slug' => htmlspecialchars($item->slug)]);
                } else {
                    $path[] = url($this->routeConfig['store_n_keyword']) . '/' . htmlspecialchars($item->slug);
                }
            }
        }
        $page = $page + 1;
        return $this->getKeypage($page, $limit, $total, $path);
    }
}
