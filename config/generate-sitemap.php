<?php

return [
    'multiplesitemap' => false,
    'defaultlocale' => '',
    'sitemaptype' => [
        'categories' => 'category', 
        'stores' => 'store', 
        'blogs' => 'blog',
        'coupons' => 'coupon',
        'keywords' => 'store_n_keyword'
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