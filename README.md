# Laravel 5 generate sitemap
   This package generate sitemap.xml file automatically base on database table and route
## Install and Configuration
   Using composer command
   ```
    composer require megaads/sitemap-generator
```
   After composer install package complete, open file app.php and add below line to `providers`: 
   ```
    Megaads\Generatesitemap\GeneratesitemapServiceProvider::class
   ```
   Then run this command to publish package config to application config folder (NOTE: Add option `--force` for overwrite config file. Be sure backup your config file before run with `--force` option): 
   
   ```
   php artisan vendor:publish --provider="Megaads\Generatesitemap\GeneratesitemapServiceProvider"
   
  ```
   After run publish command open file ``generate-sitemap.php``. IF NOT, CAN USING COMMAND TO COPY 
   ```
   cp vendor/megaads/generate-sitemap/config/generate-sitemap.php config/generate-sitemap.php
   ```
   
   It see like this: 
   
   ```
  
return [
    'multiplesitemap' => false,
    'defaultlocale' => '',
    'sitemaptype' => [
        'categories' => 'category', 
        'stores' => 'store', 
        'blogs' => 'blog'
    ],
    'locales' => [
        'us' => 'United States',
        'uk' => 'United Kingdom',
        'ca' => 'Canada',
        'fr' => 'France',
        'vn' => 'Vietnam',
    ],
    'routes' => [ // Add route name from routes.php for generate sitemap url automatically
        'store' => 'frontend::store::listByStore', // Show all stores
        'category' => 'frontend::category::listByCategory', // Show all categories
        'blog' => 'frontend::blog::detail', // Detail a blog
        'coupon' => 'frontend::coupon::detail', // Detail a coupon
        'store_n_keyword' =>  '/'
    ]
];
  
```
   
   Default param ``multiplesitemap`` will be set to `false`, mean has single sitemap.xml was generator. If set it to `true`
   sitemap file will be generated to folder with name as key at ``locales`` param config. Create folder name `sitemap` in folder `public` and 
   set `chmod 775` for this folder for create multiple locales folder.
   
   Add this line to bottom of `app\config\app`: 
   ```
   'domain' => 'http://example.com'
   ```
   
   Finally, go the below url to generate sitemap. File sitemap.xml will be generate automatically and save to public path.
   ``multiplesitemap`` is `false`: 
   If separate sitemap to multiple file using param ``is_multiple=true`` on url when call it. (attension: config ``sitemaptype`` on file config).
   ```
   //example.com/sitemap-generator
   //example.com/sitemap-generator?is_multiple=true
   ```
   Or ``multiplesitemap`` is `true`. This option allow to generate sitemap with multiple language.
   ```
   //example.com/generator-all-sitemap
   ```
   Or call to url below for generate all sitemap type (blogs, categories...) group by locales:
   ```
   //example.com/sitemap/generator-by-locale
   ```
   And can see result file with url `//example.com/sitemap.xml`
   
