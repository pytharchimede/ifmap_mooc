<?php
// Controller-level tests with temporary data only; each scenario runs in its own process.
if (PHP_SAPI === 'cli' && empty($argv[1])) {
    foreach (['cancel','cancel_reason','prepare','shipping','complete','collect','collect_stale','request','refund','refund_reference','reject','return','return_damaged','csrf','unauthorized','unknown','order_document','delivery_document','export'] as $scenario) {
        $process=proc_open([PHP_BINARY,__FILE__,$scenario],[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes);
        if (!is_resource($process) || proc_close($process)!==0) exit(1);
    }
    exit(0);
}
ob_start();
require __DIR__.'/commerce-lifecycle.php';
ob_clean();
$scenario=$argv[1]??'';
$_SERVER['SCRIPT_NAME']='/index.php';
$_SERVER['REQUEST_METHOD']='POST';
$_POST=['csrf'=>$_SESSION['commerce_csrf'],'id'=>33,'note'=>'Opération de test justifiée','reference'=>'RECEIPT-CONTROLLER','method'=>'Mobile Money','payer_phone'=>'0700000000'];
$path='/admin/commandes/action';
$assert=null;
switch($scenario){
    case 'cancel': $_POST['status']='cancelled';$path='/admin/commandes/statut';$assert=fn()=>value('SELECT status FROM orders WHERE id=33')==='cancelled'&&value('SELECT payment_status FROM orders WHERE id=33')==='paid';break;
    case 'cancel_reason': $_POST['status']='cancelled';$_POST['note']='';$path='/admin/commandes/statut';$assert=fn()=>value('SELECT status FROM orders WHERE id=33')==='processing';break;
    case 'prepare': order(34,'cod',false);$_POST['id']=34;$_POST['status']='processing';$path='/admin/commandes/statut';$assert=fn()=>value('SELECT status FROM orders WHERE id=34')==='processing';break;
    case 'shipping': $_POST['status']='shipping';$path='/admin/commandes/statut';$assert=fn()=>value('SELECT status FROM orders WHERE id=33')==='shipping';break;
    case 'complete': $_POST['status']='completed';$path='/admin/commandes/statut';$assert=fn()=>value('SELECT status FROM orders WHERE id=33')==='completed'&&(int)value('SELECT stock_quantity FROM products WHERE id=1')===3;break;
    case 'collect': $_POST['id']=31;$_POST['action']='collect_cod';$assert=fn()=>value('SELECT payment_status FROM orders WHERE id=31')==='paid'&&value("SELECT transaction_reference FROM payments WHERE order_id=31 AND provider='cash_on_delivery'")==='RECEIPT-CONTROLLER';break;
    case 'collect_stale': $_POST['id']=33;$_POST['action']='collect_cod';$assert=fn()=>(int)value("SELECT COUNT(*) FROM payments WHERE order_id=33 AND provider='cash_on_delivery'")===0;break;
    case 'request': $_POST['action']='request_refund';$assert=fn()=>value('SELECT status FROM order_refunds WHERE order_id=33')==='requested'&&value('SELECT payment_status FROM orders WHERE id=33')==='paid';break;
    case 'refund': $_POST['id']=30;$_POST['action']='complete_refund';$assert=fn()=>value('SELECT status FROM order_refunds WHERE order_id=30')==='completed'&&value('SELECT reference FROM order_refunds WHERE order_id=30')==='RECEIPT-CONTROLLER'&&value('SELECT payment_status FROM orders WHERE id=30')==='refunded';break;
    case 'refund_reference': $_POST['id']=30;$_POST['action']='complete_refund';$_POST['reference']='';$assert=fn()=>value('SELECT status FROM order_refunds WHERE order_id=30')==='requested';break;
    case 'reject': $_POST['id']=30;$_POST['action']='reject_refund';$assert=fn()=>value('SELECT status FROM order_refunds WHERE order_id=30')==='rejected'&&value('SELECT payment_status FROM orders WHERE id=30')==='paid';break;
    case 'return': $_POST['id']=2;$_POST['action']='receive_return';$assert=fn()=>value('SELECT returned_at FROM orders WHERE id=2')!==null&&(int)value('SELECT stock_quantity FROM products WHERE id=1')===5;break;
    case 'return_damaged': $_POST['id']=2;$_POST['action']='receive_return_damaged';$assert=fn()=>value('SELECT returned_at FROM orders WHERE id=2')!==null&&(int)value('SELECT stock_quantity FROM products WHERE id=1')===4;break;
    case 'csrf': $_POST['csrf']='invalid';$_POST['action']='request_refund';$assert=fn()=>http_response_code()===403&&(int)value('SELECT COUNT(*) FROM order_refunds WHERE order_id=33')===0;break;
    case 'unauthorized': $_SESSION['admin_authenticated']=false;$_POST['action']='request_refund';$assert=fn()=>(int)value('SELECT COUNT(*) FROM order_refunds WHERE order_id=33')===0;break;
    case 'unknown': $_POST['action']='fake_refund';$assert=fn()=>value('SELECT payment_status FROM orders WHERE id=33')==='paid'&&(int)value('SELECT COUNT(*) FROM order_refunds WHERE order_id=33')===0;break;
    case 'order_document': $_GET['id']=2;$path='/admin/documents/commande';$_SERVER['REQUEST_METHOD']='GET';$assert=fn($body)=>str_contains($body,'BON DE COMMANDE')&&str_contains($body,'RECEIPT-2');break;
    case 'delivery_document': $_GET['id']=2;$path='/admin/documents/livraison';$_SERVER['REQUEST_METHOD']='GET';$assert=fn($body)=>str_contains($body,'BON DE LIVRAISON')&&str_contains($body,'Physique');break;
    case 'export': $path='/admin/documents/export-commandes';$_SERVER['REQUEST_METHOD']='GET';$assert=fn($body)=>str_contains($body,'OP-123')&&str_contains($body,'REFUND-123')&&str_contains($body,'0711111111');break;
    default: throw new RuntimeException('Unknown test scenario');
}
register_shutdown_function(function()use($assert,$scenario){
    $body=ob_get_contents();
    while(ob_get_level())ob_end_clean();
    try {check($assert($body),'Controller action failed: '.$scenario);echo $scenario." OK\n";}
    catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
});
$router=new App\Core\Router();require __DIR__.'/../routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'],$path);
