<?php
use App\Controllers\AuthCommunicationController;

$router->get('/mot-de-passe-oublie',[AuthCommunicationController::class,'forgot']);
$router->post('/mot-de-passe-oublie',[AuthCommunicationController::class,'requestReset']);
$router->get('/reinitialiser-mot-de-passe',[AuthCommunicationController::class,'reset']);
$router->post('/reinitialiser-mot-de-passe',[AuthCommunicationController::class,'saveReset']);
$router->get('/admin/auth-notifications',[AuthCommunicationController::class,'settings']);
$router->post('/admin/auth-notifications',[AuthCommunicationController::class,'saveSettings']);
$router->post('/admin/auth-notifications/test',[AuthCommunicationController::class,'testChannel']);
