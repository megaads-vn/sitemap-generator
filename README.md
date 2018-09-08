# Laravel 5 generate sitemap
   This package generate sitemap.xml file automatically base on database table and route
## Install and Configuration
   Using composer command
   ```
    composer require megaads/generate-sitemap
```
   Or open composer.json and add:
   ```
    "require": {
                "megaads/generate-sitemap": "1.1.2"
            },
   ```
   Then run command 
   ```
    composer update
   ```
   After composer install package complete, open file app.php and add below line to `providers`: 
   ```
    Megaads\Generatesitemap\GeneratesitemapServiceProvider::class
   ```
   
   Finally, go the below url to generate sitemap. File sitemap.xml will be generate automatically and save to public path.
   
   ```
   //example.com/sitemap-generator
   ```
   
   And can see result file with url `//example.com/sitemap.xml`
   