<?php
if (config('generate-sitemap.multiplesitemap')) {
    $avaiablelocales = config('app.locales', []);
    $locale = Request::segment(1);
    if (empty($avaiablelocales) || !array_key_exists($locale, $avaiablelocales)) {
        $locale = '';
    }
} else {
    $locale = '';
}
Route::group(['prefix' => $locale, 'namespace' => '\Megaads\Generatesitemap\Controllers'], function() {
    Route::get('/sitemap-generator', 'SitemapGeneratorController@generate');
    Route::get('/sitemap/generate-by-alphabet', 'SitemapGeneratorController@sitemapByAlphabet');
});
Route::group(['namespace' => '\Megaads\Generatesitemap\Controllers'], function() {
    Route::get('/generator-all-sitemap', 'SitemapGeneratorController@generateAll');
    Route::get('/sitemap/generator-by-locale', 'SitemapGeneratorController@sitemapByLocales');
    Route::get('/sitemap/generate-by-alphabet', 'SitemapGeneratorController@sitemapByAlphabet');
    Route::get('/generate-sitemap-by-alphabet', 'SitemapGeneratorController@sitemapByAlphabet');
    Route::get('/sitemap/tools', function() {
        return 'Welcome to generate sitemap tool.';
    });
});