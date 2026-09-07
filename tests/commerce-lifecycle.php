<?php
// Uses connection-local temporary tables only: no real orders or stock are modified.
spl_autoload_register(function($class){if(str_starts_with($class,'App\\'))require __DIR__.'/../app/'.str_replace('\\','/',substr($class,4)).'.php';});
App\Core\Env::load(__DIR__.'/../.env');
$db=App\Core\Database::connection();
foreach (['orders','payments','order_items','enrollments','products','courses','users','stock_movements','order_status_history','coupons','digital_downloads','modules','lessons','lesson_progress','certificates'] as $table) {
    $schema=$db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM)[1];
    $schema=str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $schema);
    $schema=preg_replace('/^\s*CONSTRAINT[^\n]*\n/m', '', $schema);
    $schema=preg_replace('/,\n\)/', "\n)", $schema);
    $db->exec($schema);
}
$migration=file_get_contents(__DIR__.'/../database/migrations/2026_09_07_000014_payment_delivery_refunds.php');
$migration=str_replace('CREATE TABLE IF NOT EXISTS order_refunds','CREATE TEMPORARY TABLE IF NOT EXISTS order_refunds',$migration);
$migration=str_replace(', FOREIGN KEY(order_id) REFERENCES orders(id)','',$migration);
$migrate=eval(substr($migration,5));$migrate($db);$migrate($db);
function check($condition,$message){if(!$condition)throw new RuntimeException($message);}
function value($sql){global $db;return $db->query($sql)->fetchColumn();}
function rejects(callable $action){try{$action();}catch(RuntimeException $e){return;}throw new LogicException('Operation should be rejected');}
$db->exec("INSERT INTO users(id,name,email) VALUES(1,'Test','test@example.invalid')");
$db->exec("INSERT INTO courses(id,title,price,status) VALUES(1,'Cours test',1000,'published')");
$db->exec("INSERT INTO products(id,name,price,stock_quantity,product_type,download_limit) VALUES(1,'Physique',1000,10,'physical',5),(2,'Numérique',1000,0,'digital',5)");
function order(int $id,string $payment='pending',bool $mixed=true): void {
    global $db;
    $db->prepare("INSERT INTO orders(id,user_id,reference,status,payment_status,total,subtotal,payment_method,customer_data) VALUES(?,1,?,'pending',?,3000,3000,?,?)")->execute([$id,'TEST-'.$id,$payment,$payment==='cod'?'delivery':'online',json_encode(['name'=>'Test','email'=>'test@example.invalid','phone'=>'0700000000'])]);
    $db->prepare("INSERT INTO order_items(order_id,item_type,item_id,label,quantity,unit_price,total) VALUES(?,'product',1,'Physique',1,1000,1000)")->execute([$id]);
    if($mixed){$db->prepare("INSERT INTO order_items(order_id,item_type,item_id,label,quantity,unit_price,total) VALUES(?,'course',1,'Cours test',1,1000,1000),(?,'product',2,'Numérique',1,1000,1000)")->execute([$id,$id]);}
    if($payment!=='cod')$db->prepare("INSERT INTO payments(order_id,provider,amount,payload) VALUES(?,'paiementpro',3000,?)")->execute([$id,json_encode(['notification_token_hash'=>str_repeat('a',64)])]);
}
$controller=new App\Controllers\SiteController();$service=new App\Services\OrderLifecycle($db);
order(1);
$verification=['notification'=>['transactionReference'=>'OP-123','customerPhoneNumber'=>'0711111111','channel'=>'MOMO','amount'=>'3000']];
$controller->finalizePaidOrder(1,$verification,'paiementpro');
$controller->finalizePaidOrder(1,$verification,'paiementpro');
check(value('SELECT payment_status FROM orders WHERE id=1')==='paid','Order paid');
check(value('SELECT payment_status FROM enrollments WHERE user_id=1 AND course_id=1')==='paid','Course paid');
check((int)value('SELECT COUNT(*) FROM digital_downloads')===1,'Digital access once');
check((int)value('SELECT stock_quantity FROM products WHERE id=1')===10,'Payment does not debit physical stock');
check(value('SELECT transaction_reference FROM payments WHERE order_id=1')==='OP-123','Operator reference retained');
check(value('SELECT payer_phone FROM payments WHERE order_id=1')==='0711111111','Payer number retained separately');
check((int)value('SELECT COUNT(*) FROM payments WHERE order_id=1')===1,'No duplicate payment');
$service->updateStatus(1,'completed','Livraison test');$service->updateStatus(1,'completed','Repeat');
check((int)value('SELECT stock_quantity FROM products WHERE id=1')===9,'Delivery debits once');
$service->requestRefund(1,'Défaut signalé');$service->requestRefund(1,'Repeat request');
check(value('SELECT payment_status FROM orders WHERE id=1')==='paid','Request does not claim refunded');
rejects(fn()=>$service->completeRefund(1,'','',''));
$service->receiveReturn(1,'Article contrôlé conforme');$service->receiveReturn(1,'Repeat');
check((int)value('SELECT stock_quantity FROM products WHERE id=1')===10,'Return restocks once');
$service->completeRefund(1,'REFUND-123','Mobile Money','Exécuté');$service->completeRefund(1,'REFUND-123','Mobile Money','Repeat');
check(value('SELECT payment_status FROM orders WHERE id=1')==='refunded','Order refunded');
check(value('SELECT status FROM enrollments WHERE user_id=1 AND course_id=1')==='cancelled','Refund revokes course');
check(value('SELECT status FROM payments WHERE order_id=1')==='refunded','Payment refunded');
$controller->finalizePaidOrder(1,$verification,'paiementpro');
check(value('SELECT payment_status FROM orders WHERE id=1')==='refunded','Late notification cannot undo refund');
order(2,'cod',false);rejects(fn()=>$service->updateStatus(2,'completed','Unpaid'));
$controller->finalizePaidOrder(2,['data'=>['operator_id'=>'RECEIPT-2','payment_method'=>'Espèces']],'cash_on_delivery');
$service->updateStatus(2,'completed','Remis');
check((int)value('SELECT stock_quantity FROM products WHERE id=1')===9,'COD delivery debits once');
order(3,'pending',false);$service->updateStatus(3,'cancelled','Annulation');
check(value('SELECT payment_status FROM orders WHERE id=3')==='pending','Cancellation never claims refund');
rejects(fn()=>$service->receiveReturn(3,'Pas de sortie'));
$controller->finalizePaidOrder(3,$verification,'paiementpro');
check(value('SELECT status FROM orders WHERE id=3')==='cancelled','Late payment keeps cancelled logistics');
check(value('SELECT payment_status FROM orders WHERE id=3')==='paid','Late payment is recorded for refund');
$service->requestRefund(3,'Paiement tardif');
order(4,'pending',false);$controller->finalizePaidOrder(4,$verification,'paiementpro');
$db->exec("UPDATE products SET stock_quantity=8 WHERE id=1; INSERT INTO stock_movements(product_id,order_id,movement_type,quantity,stock_before,stock_after) VALUES(1,4,'sale',-1,9,8)");
$service->updateStatus(4,'completed','Ancienne commande');
check((int)value('SELECT stock_quantity FROM products WHERE id=1')===8,'Legacy payment stock is not debited twice');
order(5,'pending',false);$controller->finalizePaidOrder(5,$verification,'paiementpro');$db->exec('UPDATE products SET stock_quantity=0 WHERE id=1');
rejects(fn()=>$service->updateStatus(5,'completed','Insufficient stock'));
check(value('SELECT status FROM orders WHERE id=5')==='paid','Failed delivery rolls back');
order(6);order(7);$controller->finalizePaidOrder(6,$verification,'paiementpro');$controller->finalizePaidOrder(7,$verification,'paiementpro');
$service->requestRefund(7,'Achat en double');$service->completeRefund(7,'REFUND-7','Mobile Money','Exécuté');
check((int)value('SELECT order_id FROM enrollments WHERE user_id=1 AND course_id=1')===6,'Other paid purchase preserves access');
check(value('SELECT status FROM enrollments WHERE user_id=1 AND course_id=1')==='active','Other purchase remains active');
order(8,'pending',false);$db->exec('UPDATE products SET stock_quantity=5 WHERE id=1');$controller->finalizePaidOrder(8,$verification,'paiementpro');$service->updateStatus(8,'completed','Remis');$service->receiveReturn(8,'Article endommagé',false);$service->receiveReturn(8,'Repeat',true);
check((int)value('SELECT stock_quantity FROM products WHERE id=1')===4,'Damaged return never restocks');
rejects(fn()=>$service->updateStatus(8,'shipping','Cannot redeliver returned goods'));
// Cancellation is allowed at each stage, records its reason and never fabricates a refund/return.
foreach (['pending','paid','processing','shipping','completed'] as $index=>$stage) {
    $id=20+$index;order($id,'pending',false);
    if ($stage!=='pending') $controller->finalizePaidOrder($id,$verification,'paiementpro');
    $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$stage,$id]);
    $stockBefore=(int)value('SELECT stock_quantity FROM products WHERE id=1');
    rejects(fn()=>$service->updateStatus($id,'cancelled',''));
    $service->updateStatus($id,'cancelled','Annulation demandée par le client');
    $historyBefore=(int)value('SELECT COUNT(*) FROM order_status_history WHERE order_id='.$id);
    $service->updateStatus($id,'cancelled','Deuxième clic');
    check(value('SELECT status FROM orders WHERE id='.$id)==='cancelled','Cancellation allowed at '.$stage);
    check(value('SELECT payment_status FROM orders WHERE id='.$id)===($stage==='pending'?'pending':'paid'),'Cancellation preserves payment at '.$stage);
    check((int)value('SELECT stock_quantity FROM products WHERE id=1')===$stockBefore,'Cancellation preserves stock at '.$stage);
    check((int)value('SELECT COUNT(*) FROM order_status_history WHERE order_id='.$id)===$historyBefore,'Repeat cancellation is idempotent');
    rejects(fn()=>$service->updateStatus($id,'processing','Cannot reopen cancelled order'));
}
order(30);$controller->finalizePaidOrder(30,$verification,'paiementpro');
$service->requestRefund(30,'Demande à examiner');$historyBefore=(int)value('SELECT COUNT(*) FROM order_status_history WHERE order_id=30');$service->requestRefund(30,'Deuxième clic');
check((int)value('SELECT COUNT(*) FROM order_status_history WHERE order_id=30')===$historyBefore,'Refund request is idempotent');
rejects(fn()=>$service->rejectRefund(30,''));$service->rejectRefund(30,'Demande non justifiée');
check(value('SELECT status FROM order_refunds WHERE order_id=30')==='rejected','Refund rejection recorded');
check(value('SELECT payment_status FROM orders WHERE id=30')==='paid','Refund rejection preserves payment');
rejects(fn()=>$service->completeRefund(30,'REFUSED','Mobile Money','Cannot complete rejected refund'));
$service->requestRefund(30,'Nouvel examen avec justificatif');
check(value('SELECT status FROM order_refunds WHERE order_id=30')==='requested','Rejected refund can be reconsidered');
order(31,'cod',false);$service->updateStatus(31,'processing','Préparation');$service->updateStatus(31,'shipping','Expédiée');
rejects(fn()=>$service->updateStatus(31,'completed','Unpaid COD cannot complete'));
// Cancelled paid course loses access but retains valid alternate purchase.
$service->updateStatus(30,'cancelled','Annulation de cet achat');
check((int)value('SELECT order_id FROM enrollments WHERE user_id=1 AND course_id=1')===6,'Cancellation preserves alternate paid course access');
$db->exec("INSERT INTO courses(id,title,price,status) VALUES(2,'Cours annulé',1000,'published')");order(32,'pending',false);
$db->exec("INSERT INTO order_items(order_id,item_type,item_id,label,quantity,unit_price,total) VALUES(32,'course',2,'Cours annulé',1,1000,1000)");
$controller->finalizePaidOrder(32,$verification,'paiementpro');$service->updateStatus(32,'cancelled','Cours annulé par le client');
check(value('SELECT status FROM enrollments WHERE course_id=2')==='cancelled','Cancellation revokes sole course access');
check(value('SELECT payment_status FROM enrollments WHERE course_id=2')==='paid','Cancellation does not pretend course was refunded');
order(33,'pending',false);$controller->finalizePaidOrder(33,$verification,'paiementpro');$service->updateStatus(33,'processing','Préparation');
$_SESSION=['admin_authenticated'=>true,'user'=>['id'=>1,'name'=>'Test','role'=>'learner']];
ob_start();(new App\Controllers\AdminController())->orders();$html=ob_get_clean();
if($fixture=getenv('IFMAP_TEST_HTML_PATH'))file_put_contents($fixture,$html);
check(str_contains($html,'OP-123')&&str_contains($html,'0711111111')&&str_contains($html,'REFUND-123'),'Admin renders payment and refund evidence');
$db->exec("UPDATE courses SET category='Test',duration_label='1 h',tone='green' WHERE id=1");
ob_start();(new App\Controllers\DashboardController())->course();$html=ob_get_clean();
check(str_contains($html,'Déjà payé')&&str_contains($html,'TEST-6'),'Course receipt visible');
foreach (['terms','refundPolicy'] as $action) { ob_start();$controller->$action();$html=ob_get_clean();check(str_contains($html,'infos@ifmap.ci'),'Legal page renders'); }
echo "Commerce: migrations, paiements, cours, reçus, livraison, retour, remboursement, répétitions et rollback OK\n";
