<?php 
namespace Megaads\Generatesitemap\Controller;

use Megaads\Generatesitemap\Models\Store;

class SitemapGeneratorController
{
    /**
     * Generate sitemap.xml from database tables
     * 
     * @return \Illuminate\Http\JsonResponse\;
     */
    public function generate() {
        $stores = Store::limit(2)->get();
        return response()->json($stores);
    }
}