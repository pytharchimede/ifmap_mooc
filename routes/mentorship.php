<?php
use App\Controllers\MentorshipController;

$router->get('/mentorat', [MentorshipController::class,'catalogue']);
$router->get('/devenir-mentor', [MentorshipController::class,'apply']);
$router->post('/devenir-mentor', [MentorshipController::class,'submitApplication']);
$router->get('/academie/mentorat', [MentorshipController::class,'dashboard']);
$router->post('/academie/mentorat/demander', [MentorshipController::class,'request']);
$router->get('/academie/mentorat/payer', [MentorshipController::class,'pay']);
$router->post('/academie/mentorat/avis', [MentorshipController::class,'review']);
$router->get('/academie/coaching/{id}', [MentorshipController::class,'room']);
$router->get('/mentor', [MentorshipController::class,'mentorDashboard']);
$router->get('/mentorat/paiement/retour', [MentorshipController::class,'paymentReturn']);
$router->post('/mentorat/paiement/notification', [MentorshipController::class,'paymentNotify']);
$router->get('/mentorat/paiement/notification', [MentorshipController::class,'paymentNotify']);
$router->get('/admin/mentorat', [MentorshipController::class,'adminIndex']);
$router->post('/admin/mentorat/mentor', [MentorshipController::class,'adminMentorAction']);
$router->post('/admin/mentorat/session', [MentorshipController::class,'adminSessionAction']);
$router->get('/admin/integrations', [MentorshipController::class,'integrations']);
$router->post('/admin/integrations', [MentorshipController::class,'saveIntegrations']);
$router->post('/admin/integrations/test-mail', [MentorshipController::class,'testMail']);
