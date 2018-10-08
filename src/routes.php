<?php
if (config('generate-sitemap.multiplesitemap')) {
    $avaiablelocales = config('app.locales');
    $locale = Request::segment(1);
    if (!array_key_exists($locale, $avaiablelocales)) {
        $locale = '';
    }
} else {
    $locale = '';
}
Route::group(['prefix' => $locale, 'namespace' => '\Megaads\Generatesitemap\Controllers'], function() {
    Route::get('/sitemap-generator', 'SitemapGeneratorController@generate');
});
Route::group(['namespace' => '\Megaads\Generatesitemap\Controllers'], function() {
    Route::get('/generator-all-sitemap', 'SitemapGeneratorController@generateAll');
});