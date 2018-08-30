<?php 
namespace Megaads\Generatesitemap\Controllers;

use Megaads\Generatesitemap\Models\Store;

class GeneratorController
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