<?php
use App\Controllers\AdminController;
use App\Controllers\DashboardController;
use App\Controllers\SiteController;

$router->get('/', [SiteController::class, 'home']);
$router->get('/formations', [SiteController::class, 'trainings']);
$router->get('/formations/sous-gerant-station-service', [SiteController::class, 'training']);
$router->get('/boutique', [SiteController::class, 'shop']);
$router->get('/boutique/sabre-de-jauge', [SiteController::class, 'product']);
$router->get('/panier', [SiteController::class, 'cart']);
$router->post('/panier/ajouter', [SiteController::class, 'addCart']);
$router->get('/mon-compte', [SiteController::class, 'account']);
$router->get('/academie', [DashboardController::class, 'index']);
$router->get('/catalogue', [DashboardController::class, 'catalog']);
$router->get('/cours', [DashboardController::class, 'course']);
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/connexion', [AdminController::class, 'login']);
$router->post('/admin/connexion', [AdminController::class, 'authenticate']);
$router->post('/admin/deconnexion', [AdminController::class, 'logout']);
$router->get('/admin/cours', [AdminController::class, 'courses']);
$router->get('/admin/branding', [AdminController::class, 'branding']);
$router->post('/admin/branding', [AdminController::class, 'saveBranding']);
