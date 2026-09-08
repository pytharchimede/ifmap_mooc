<?php
use App\Controllers\WorkspaceController;

$router->get('/academie/tickets',[WorkspaceController::class,'tickets']);
$router->post('/academie/tickets',[WorkspaceController::class,'createTicket']);
$router->get('/academie/ticket',[WorkspaceController::class,'ticket']);
$router->post('/academie/ticket/repondre',[WorkspaceController::class,'replyTicket']);

$router->get('/academie/discussions',[WorkspaceController::class,'chat']);
$router->get('/academie/discussion',[WorkspaceController::class,'conversation']);
$router->post('/academie/discussions/demarrer',[WorkspaceController::class,'startConversation']);
$router->post('/academie/discussion/envoyer',[WorkspaceController::class,'sendMessage']);

$router->get('/admin/tickets',[WorkspaceController::class,'adminTickets']);
$router->get('/admin/ticket',[WorkspaceController::class,'adminTicket']);
$router->post('/admin/ticket',[WorkspaceController::class,'adminTicketAction']);

$router->get('/admin/crm',[WorkspaceController::class,'crm']);
$router->post('/admin/crm/contact',[WorkspaceController::class,'saveContact']);
$router->post('/admin/crm/opportunite',[WorkspaceController::class,'saveOpportunity']);

$router->get('/admin/traductions',[WorkspaceController::class,'translations']);
$router->post('/admin/traductions',[WorkspaceController::class,'saveTranslation']);
$router->post('/admin/traductions/parametres',[WorkspaceController::class,'saveI18nSettings']);
$router->post('/langue',[WorkspaceController::class,'switchLocale']);
