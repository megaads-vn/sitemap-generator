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
   Then run this command to publish package config to application config folder: 
   
   ```
   php artisan vendor:publish --provider="Megaads\Generatesitemap\GeneratesitemapServiceProvider"
   
```
   After run publish command open file ``generate-sitemap.php``. It see like this: 
   
   ```
  
return [
    'multiplesitemap' => false,

    'locales' => [
        'us' => 'United States',
        'uk' => 'United Kingdom',
        'ca' => 'Canada',
        'fr' => 'France',
        'vn' => 'Vietnam',
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
   ```
   //example.com/sitemap-generator
   ```
   Or ``multiplesitemap`` is `true`:
   ```
   //example.com/generator-all-sitemap
   ```
   And can see result file with url `//example.com/sitemap.xml`
   
