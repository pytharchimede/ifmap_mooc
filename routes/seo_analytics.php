<?php
use App\Controllers\SeoAnalyticsController;

$router->get('/admin/visiteurs', [SeoAnalyticsController::class, 'visitors']);
$router->get('/admin/referencement', [SeoAnalyticsController::class, 'seo']);
$router->post('/admin/referencement', [SeoAnalyticsController::class, 'saveSeo']);
$router->post('/admin/referencement/supprimer', [SeoAnalyticsController::class, 'deleteSeo']);
$router->get('/robots.txt', [SeoAnalyticsController::class, 'robots']);
$router->get('/sitemap.xml', [SeoAnalyticsController::class, 'sitemap']);
