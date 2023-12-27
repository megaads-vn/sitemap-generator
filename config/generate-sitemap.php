<?php

return [
    'multiplesitemap' => true,
    'defaultlocale' => '',
    'sitemaptype' => [
        'categories' => 'category', 
        'stores' => 'store', 
        'blogs' => 'blog',
        'coupons' => 'coupon',
        'keywords' => 'store_n_keyword',
        'tags' => 'tag',
        'menu' => 'static_routes'
    ],
    'locales' => [
        'us' => 'United State',
        'uk' => 'United Kingdom',
        'ca' => 'Canada',
        'fr' => 'French',
        'vn' => 'Viet Nam',
    ],
    'routes' => [ // Add route name from routes.php for generate sitemap url automatically
        'store' => 'frontend::store::listByStore', // Show all stores
        'category' => 'frontend::category::listByCategory', // Show all categories
        'blog' => 'frontend::blog::detail', // Detail a blog
        'coupon' => 'frontend::coupon::detail', // Detail a coupon
        'store_n_keyword' =>  '/',
        'tag' => 'frontend::tag::list',
        'detail_deal' => 'deal::detail'
    ],
    'static_routes' => [
        'home' => 'frontend::home',
        'categories' => 'frontend::category::allCategory',
        'stores' => 'frontend::store::allStore',
        'blogs' => 'frontend::blog::list',
        'terms' => 'frontend::home::terms-of-use',
        'privacy' => 'frontend::home::privacy-policy'
    ],
    'is_save_storage' => false,
];
