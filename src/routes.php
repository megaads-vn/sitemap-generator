<?php 

Route::group(['namespace' => '\Megaads\Generatesitemap\Controllers'], function() {
    Route::get('/sitemap-generator', 'SitemapGeneratorController@generate');
});

