<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Core\Env;
use App\Services\CinetPay;
use App\Services\PaiementPro;

final class SiteController
{
    private function trainingData(): array
    {
        try {
            return array_map(fn($course) => [
                'id' => $course['id'],
                'slug' => $course['slug'],
                'title' => $course['title'],
                'sector' => $course['category'],
                'mode' => $course['mode'],
                'duration' => $course['duration_label'],
                'price' => (int) $course['price'],
                'tone' => $course['tone'],
                'thumbnail' => $course['thumbnail'],
                'status' => $course['status'],
            ], Database::connection()->query("SELECT * FROM courses WHERE status='published' ORDER BY id DESC")->fetchAll());
        } catch (\Throwable) {
        }

        return [
            ['slug'=>'sous-gerant-station-service','title'=>'Le Métier de Sous-Gérant en Station-Service','sector'=>'Aval pétrolier','mode'=>'Présentiel','duration'=>'4 jours','price'=>75000,'tone'=>'navy','thumbnail'=>null],
            ['slug'=>'depotage-carburant','title'=>'Le Dépotage du Carburant en Station-Service','sector'=>'Aval pétrolier','mode'=>'En ligne','duration'=>'8 heures','price'=>45000,'tone'=>'gold'],
            ['slug'=>'graissage-lubrifiants','title'=>'Graissage et Vente des Lubrifiants','sector'=>'Aval pétrolier','mode'=>'Mixte','duration'=>'3 jours','price'=>50000,'tone'=>'green'],
            ['slug'=>'installation-solaire','title'=>'Installation et Maintenance Solaire','sector'=>'Énergie solaire','mode'=>'Présentiel','duration'=>'5 jours','price'=>95000,'tone'=>'sun'],
            ['slug'=>'techniques-vente','title'=>'Techniques de Vente et Relation Client','sector'=>'Commerce & Marketing','mode'=>'En ligne','duration'=>'6 heures','price'=>35000,'tone'=>'coral'],
            ['slug'=>'excel-professionnel','title'=>'Excel Professionnel & Tableaux de Bord','sector'=>'Informatique','mode'=>'Mixte','duration'=>'3 jours','price'=>60000,'tone'=>'blue'],
        ];
    }

    private function products(): array
    {
        try { return array_map(fn($r)=>['id'=>$r['id'],'slug'=>$r['slug'],'title'=>$r['name'],'description'=>$r['description'],'category'=>$r['category']??'Équipements','product_type'=>$r['product_type']??'physical','image'=>$r['image'],'stock'=>($r['product_type']??'physical')==='digital'?'Téléchargement immédiat':($r['stock_status']==='in_stock'?'En stock':($r['stock_status']==='on_order'?'Sur commande':'Rupture')),'price'=>$r['price']===null?null:(int)$r['price'],'promotional_price'=>isset($r['promotional_price'])&&$r['promotional_price']!==null?(int)$r['promotional_price']:null,'tone'=>'navy'], Database::connection()->query("SELECT * FROM products WHERE status='published' ORDER BY id DESC")->fetchAll()); } catch(\Throwable) {} return [
            ['slug'=>'pompe-carburant','title'=>'Pompe à carburant','stock'=>'En stock','price'=>null,'tone'=>'navy'],
            ['slug'=>'pistolet-distribution','title'=>'Pistolet de distribution','stock'=>'En stock','price'=>85000,'tone'=>'gold'],
            ['slug'=>'sabre-de-jauge','title'=>'Sabre de jauge','stock'=>'Sur commande','price'=>45000,'tone'=>'steel'],
            ['slug'=>'pate-kolor-kut','title'=>'Pâte Kolor Kut','stock'=>'En stock','price'=>18000,'tone'=>'coral'],
        ];
    }

    public function home(): void { View::render('site/home', ['title'=>'Accueil','active'=>'home'], 'site'); }
    public function contact(): void { View::render('site/contact', ['title'=>'Contact','active'=>'contact'], 'site'); }
    public function company(): void { View::render('site/company', ['title'=>'Solutions entreprises','active'=>'company'], 'site'); }
    public function trainings(): void { View::render('site/trainings', ['title'=>'Nos formations','active'=>'trainings','trainings'=>$this->trainingData()], 'site'); }
    public function training(): void
    {
        $slug = trim((string) ($_GET['slug'] ?? 'sous-gerant-station-service'));
        $db = Database::connection();
        $stmt = $db->prepare("SELECT c.*, u.name instructor_name, u.email instructor_email, u.phone instructor_phone, u.avatar instructor_avatar, u.specialty instructor_specialty, u.bio instructor_bio, u.cv_path instructor_cv FROM courses c LEFT JOIN users u ON u.id=c.instructor_id WHERE c.slug=? AND c.status='published' LIMIT 1");
        $stmt->execute([$slug]);
        $course = $stmt->fetch();

        if (!$course) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Formation introuvable']);
            return;
        }

        $prerequisites = $db->query("SELECT cp.*,required.title required_title FROM course_prerequisites cp LEFT JOIN courses required ON required.id=cp.prerequisite_course_id WHERE cp.course_id=" . (int) $course['id'])->fetchAll();
        $modules = $db->query('SELECT * FROM modules WHERE course_id=' . (int) $course['id'] . ' ORDER BY position,id')->fetchAll();
        foreach ($modules as &$module) {
            $lessonStmt = $db->prepare('SELECT title,type,duration,content FROM lessons WHERE module_id=? ORDER BY position,id');
            $lessonStmt->execute([(int) $module['id']]);
            $module['lessons'] = $lessonStmt->fetchAll();
        }
        unset($module);

        View::render('site/training', [
            'title' => $course['title'],
            'active' => 'trainings',
            'courseData' => $course,
            'prerequisites' => $prerequisites,
            'modules' => $modules,
        ], 'site');
    }
    public function terms(): void { View::render('site/legal',['title'=>'Conditions générales d’utilisation et de vente','active'=>'legal','policy'=>'terms'],'site'); }
    public function refundPolicy(): void { View::render('site/legal',['title'=>'Politique de retour et de remboursement','active'=>'legal','policy'=>'refund'],'site'); }
    public function shop(): void { View::render('site/shop', ['title'=>'Boutique équipements','active'=>'shop','products'=>$this->products()], 'site'); }
    public function product(): void
    {
        $slug=trim((string)($_GET['slug']??''));$db=Database::connection();$stmt=$db->prepare("SELECT * FROM products WHERE slug=? AND status='published' LIMIT 1");$stmt->execute([$slug]);$product=$stmt->fetch();
        if(!$product){http_response_code(404);View::render('errors/404',['title'=>'Produit introuvable']);return;}
        $stmt=$db->prepare("SELECT * FROM products WHERE status='published' AND category=? AND id<>? ORDER BY id DESC LIMIT 4");$stmt->execute([$product['category']??'Équipements',(int)$product['id']]);
        View::render('site/product',['title'=>$product['name'],'active'=>'shop','product'=>$product,'related'=>$stmt->fetchAll()],'site');
    }
    public function cart(): void { View::render('site/cart', ['title'=>'Votre panier','active'=>'cart'], 'site'); }
    public function removeCart(): void { unset($_SESSION['cart'][$_POST['key'] ?? '']); $_SESSION['flash']='Article retiré du panier.'; $this->redirect('/panier'); }
    public function clearCart(): void { $_SESSION['cart']=[]; $_SESSION['flash']='Panier vidé.'; $this->redirect('/panier'); }
    public function applyCoupon(): void
    {
        $code=strtoupper(trim((string)($_POST['code']??'')));unset($_SESSION['coupon']);$subtotal=$this->cartSubtotal();$stmt=Database::connection()->prepare("SELECT * FROM coupons WHERE code=? AND status='active' AND (starts_at IS NULL OR starts_at<=NOW()) AND (expires_at IS NULL OR expires_at>=NOW()) AND (usage_limit IS NULL OR used_count<usage_limit) LIMIT 1");$stmt->execute([$code]);$coupon=$stmt->fetch();
        if(!$coupon||$subtotal<(float)$coupon['minimum_amount']){$_SESSION['flash']='Ce coupon est invalide, expiré ou son minimum d’achat n’est pas atteint.';$this->redirect('/panier');}
        $_SESSION['coupon']=$coupon;$_SESSION['flash']='Coupon '.$code.' appliqué avec succès.';$this->redirect('/panier');
    }
    public function checkout(): void
    {
        if (empty($_SESSION['cart'])) { $_SESSION['flash']='Votre panier est vide.'; $this->redirect('/panier'); }
        $hasTraining=(bool)array_filter($_SESSION['cart'],fn($item)=>($item['kind']??'')==='formation');if($hasTraining&&empty($_SESSION['user'])){$_SESSION['intended_url']='/commande';$_SESSION['auth_flash']='Connectez-vous ou créez un compte pour que la formation soit ajoutée automatiquement à votre espace après paiement.';$this->redirect('/connexion');}
        View::render('site/checkout', ['title'=>'Informations et paiement','active'=>'cart','cart'=>$_SESSION['cart']], 'site');
    }
    public function placeOrder(): void
    {
        if (empty($_SESSION['cart'])) $this->redirect('/panier');
        if (($_POST['accept_terms'] ?? '') !== '1') { $_SESSION['checkout_error']='Veuillez accepter les conditions de vente et la politique de retour et remboursement.'; $this->redirect('/commande'); }
        $hasTraining = (bool) array_filter($_SESSION['cart'], fn($item) => ($item['kind'] ?? '') === 'formation');
        if ($hasTraining && empty($_SESSION['user'])) { $_SESSION['intended_url']='/commande'; $this->redirect('/connexion'); }
        if (!in_array($_POST['payment'] ?? '', ['online','delivery'], true) || !in_array($_POST['delivery'] ?? '', ['pickup','delivery'], true)) { $_SESSION['checkout_error']='Mode de paiement ou de livraison invalide.'; $this->redirect('/commande'); }
        $hasProduct = (bool)array_filter($_SESSION['cart'],fn($item)=>($item['kind']??'')==='product');
        $hasPhysical = (bool)array_filter($_SESSION['cart'],fn($item)=>($item['kind']??'')==='product'&&($item['product_type']??'physical')==='physical');
        if ($hasTraining && ($_POST['payment'] ?? '') === 'delivery') $_POST['payment']='online';
        if (!$hasPhysical) { $_POST['delivery']='pickup'; if(($_POST['payment']??'')==='delivery')$_POST['payment']='online'; }
        $required=['name','email','phone','payment','delivery']; foreach($required as $field) if(trim($_POST[$field]??'')===''){$_SESSION['checkout_error']='Veuillez remplir tous les champs obligatoires.';$this->redirect('/commande');}
        if($hasPhysical&&($_POST['delivery']??'')==='delivery'&&trim((string)($_POST['address']??''))===''){$_SESSION['checkout_error']='Indiquez l’adresse complète de livraison.';$this->redirect('/commande');}
        if(!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL)){$_SESSION['checkout_error']='Adresse email invalide.';$this->redirect('/commande');}
        $subtotal=$this->cartSubtotal();$coupon=$_SESSION['coupon']??null;$discount=$this->couponDiscount($coupon,$subtotal);$total=max(0,$subtotal-$discount);if($total>0)$total=round($total);
        $db=Database::connection();$db->beginTransaction();try{
            $reference='IF-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(8)));$userId=$_SESSION['user']['id']??null;$status='pending';$paymentStatus=$_POST['payment']==='delivery'?'cod':'pending';$customer=['name'=>trim($_POST['name']),'email'=>trim($_POST['email']),'phone'=>trim($_POST['phone']),'company'=>trim((string)($_POST['company']??'')),'address'=>trim((string)($_POST['address']??''))];
            $stmt=$db->prepare('INSERT INTO orders(user_id,reference,status,payment_method,payment_status,delivery_method,subtotal,discount,total,coupon_code,customer_data) VALUES(?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute([$userId,$reference,$status,$_POST['payment'],$paymentStatus,$_POST['delivery'],$subtotal,$discount,$total,$coupon['code']??null,json_encode($customer,JSON_UNESCAPED_UNICODE)]);$orderId=(int)$db->lastInsertId();$db->prepare('UPDATE orders SET terms_version=?,terms_accepted_at=NOW() WHERE id=?')->execute(['2026-09-07',$orderId]);$itemStmt=$db->prepare('INSERT INTO order_items(order_id,item_type,item_id,label,quantity,unit_price,total) VALUES(?,?,?,?,?,?,?)');
            foreach($_SESSION['cart'] as $item){$itemId=($item['kind']??'')==='formation'?(int)($item['course_id']??0):(int)($item['product_id']??0);$itemType=($item['kind']??'')==='formation'?'course':'product';$itemStmt->execute([$orderId,$itemType,$itemId,$item['name'],$item['quantity'],$item['price'],$item['price']*$item['quantity']]);}
            $db->prepare('INSERT INTO order_status_history(order_id,status,note) VALUES(?,?,?)')->execute([$orderId,$status,'Commande créée']);if($coupon&&$paymentStatus==='cod')$db->prepare('UPDATE coupons SET used_count=used_count+1 WHERE id=?')->execute([(int)$coupon['id']]);
            $db->commit();$order=['id'=>$orderId,'reference'=>$reference,'customer'=>$customer['name'],'email'=>$customer['email'],'payment'=>$_POST['payment'],'items'=>$_SESSION['cart'],'downloads'=>[],'total'=>$total,'discount'=>$discount,'status'=>$status,'created_at'=>date('c')];
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['checkout_error']='La commande n’a pas pu être enregistrée.';$this->redirect('/commande');}
        if ($_POST['payment'] === 'online' && $total > 0) {
            try {
                $token = bin2hex(random_bytes(32));
                $metadata = ['notification_token_hash' => hash('sha256', $token)];
                $db->prepare("INSERT INTO payments(order_id,provider,amount,status,payload) VALUES(?,'paiementpro',?,'initiated',?)")
                    ->execute([$orderId, $total, json_encode($metadata)]);
                $dbOrder = $db->query('SELECT * FROM orders WHERE id='.$orderId)->fetch();
                $response = (new PaiementPro())->initialize($dbOrder, $this->publicUrl('/paiement/paiementpro/retour'), $this->publicUrl('/paiement/paiementpro/notification').'?token='.$token);
                $db->prepare("UPDATE payments SET provider_reference=? WHERE order_id=? AND provider='paiementpro'")
                    ->execute([$response['session_id'], $orderId]);
                $_SESSION['pending_order_id'] = $orderId;
                $_SESSION['cart'] = [];
                unset($_SESSION['coupon']);
                header('Location: '.$response['payment_url']);
                exit;
            } catch (\Throwable $e) {
                error_log('Paiement Pro init commande '.$orderId.' : '.$e->getMessage());
                $_SESSION['checkout_error'] = 'Impossible d’ouvrir Paiement Pro. Veuillez réessayer. Votre panier a été conservé.';
                $this->redirect('/commande');
            }
        }
        if($_POST['payment']==='online'&&$total<=0){$this->finalizePaidOrder($orderId,['data'=>['status'=>'ACCEPTED','amount'=>0,'currency'=>'XOF']]);$order=$this->orderForConfirmation($orderId);}
        $_SESSION['last_order']=$order;$_SESSION['cart']=[];unset($_SESSION['coupon']);$this->redirect('/commande/confirmation');
    }
    public function paiementProNotify(): void
    {
        $payload = $_POST ?: (json_decode((string) file_get_contents('php://input'), true) ?: $_GET);
        if (!is_array($payload) || !is_string($payload['referenceNumber'] ?? null)) { http_response_code(400); return; }
        $db = Database::connection();
        $stmt = $db->prepare("SELECT o.*,p.payload payment_payload FROM orders o JOIN payments p ON p.order_id=o.id AND p.provider='paiementpro' WHERE p.provider_reference=? LIMIT 1");
        $stmt->execute([(string)$payload['referenceNumber']]);
        $order = $stmt->fetch();
        if (!$order) { http_response_code(404); return; }
        $saved = json_decode((string)($order['payment_payload']??''),true)?:[];$provided=(string)($_GET['token']??'');$expected=(string)($saved['notification_token_hash']??'');if($provided===''||$expected===''||!hash_equals($expected,hash('sha256',$provided))){http_response_code(403);return;}
        try {
            $response=(new PaiementPro())->status((string)$payload['referenceNumber']);
            $this->finalizePaiementProOrder((int)$order['id'],$response);
            http_response_code(200);
        } catch (\Throwable $e) { error_log('Paiement Pro notification: '.$e->getMessage()); http_response_code(500); }
    }
    public function paiementProReturn(): void
    {
        $reference=trim((string)($_GET['referenceNumber']??$_POST['referenceNumber']??''));$orderId=(int)($_SESSION['pending_order_id']??0);if($reference!==''&&$orderId){try{$this->finalizePaiementProOrder($orderId,(new PaiementPro())->status($reference));}catch(\Throwable $e){error_log('Paiement Pro retour: '.$e->getMessage());}}
        unset($_SESSION['pending_order_id']);$_SESSION['last_order']=$orderId?$this->orderForConfirmation($orderId):($_SESSION['last_order']??null);$this->redirect('/commande/confirmation');
    }
    private function finalizePaiementProOrder(int $orderId,array $response): void
    {
        $code=(int)($response['code']??-1);$state=$code===0?'ACCEPTED':($code===1002||$code===1006?'REFUSED':'PENDING');$mapped=['data'=>['status'=>$state,'amount'=>$response['amount']??null,'currency'=>'XOF','operator_id'=>$response['referenceNumber']??null,'payment_method'=>$response['paymentType']??'Paiement Pro','payer_phone'=>$response['mobile']??null]];$this->finalizePaidOrder($orderId,$mapped,'paiementpro');
        if($state!=='ACCEPTED'){Database::connection()->prepare("UPDATE payments SET status=?,payload=? WHERE order_id=? AND provider='paiementpro'")->execute([$state==='REFUSED'?'failed':'pending',json_encode($response,JSON_UNESCAPED_UNICODE),$orderId]);}
    }
    public function cinetPayNotify(): void { http_response_code(410); }
    public function cinetPayReturn(): void { $_SESSION['checkout_error']='Le connecteur CinetPay a été désactivé au profit de Paiement Pro.';$this->redirect('/commande'); }
    public function addCart(): void
    {
        $kind = ($_POST['kind'] ?? '') === 'product' ? 'product' : 'formation';
        if ($kind === 'formation') {
            $id=(int)($_POST['course_id']??0);$stmt=Database::connection()->prepare("SELECT id,title,price FROM courses WHERE id=? AND status='published'");$stmt->execute([$id]);$course=$stmt->fetch();if(!$course){$_SESSION['flash']='Formation introuvable.';$this->redirect('/formations');}
            $item=['kind'=>'formation','course_id'=>(int)$course['id'],'name'=>$course['title'],'price'=>(int)$course['price'],'quantity'=>1];$key='formation-'.$course['id'];
        } else {
            $id=(int)($_POST['product_id']??0);$stmt=Database::connection()->prepare("SELECT id,name,price,promotional_price,product_type,stock_status,stock_quantity FROM products WHERE id=? AND status='published'");$stmt->execute([$id]);$product=$stmt->fetch();if(!$product){$_SESSION['flash']='Produit introuvable.';$this->redirect('/boutique');}if(($product['product_type']??'physical')==='physical'&&(($product['stock_status']??'out_of_stock')==='out_of_stock'||(int)$product['stock_quantity']<1)){$_SESSION['flash']='Ce produit est en rupture de stock.';$this->redirect('/boutique');}$price=$product['promotional_price']!==null?(float)$product['promotional_price']:(float)$product['price'];$quantity=max(1,min(99,(int)($_POST['quantity']??1)));if(($product['product_type']??'physical')==='physical')$quantity=min($quantity,(int)$product['stock_quantity']);$item=['kind'=>'product','product_id'=>(int)$product['id'],'product_type'=>$product['product_type']??'physical','name'=>$product['name'],'price'=>$price,'quantity'=>$quantity];$key='product-'.$product['id'];
        }
        $_SESSION['cart'] ??= [];$_SESSION['cart'][$key]=$item;$_SESSION['flash']='Article ajouté au panier.';$this->redirect('/panier');
    }
    public function confirmation(): void { $order=$_SESSION['last_order']??null;View::render('site/confirmation',['title'=>'Confirmation de commande','active'=>'cart','order'=>$order],'site'); }
    public function downloadProduct(): void
    {
        $token=(string)($_GET['token']??'');if($token===''){http_response_code(404);return;}$db=Database::connection();$stmt=$db->prepare("SELECT od.*,p.digital_file,p.name FROM order_downloads od JOIN products p ON p.id=od.product_id JOIN orders o ON o.id=od.order_id WHERE od.token_hash=? AND od.expires_at>NOW() AND od.download_count<od.max_downloads AND o.payment_status='paid' LIMIT 1");$stmt->execute([hash('sha256',$token)]);$row=$stmt->fetch();if(!$row){http_response_code(403);echo 'Lien invalide, expiré ou limite de téléchargement atteinte.';return;}$file=dirname(__DIR__,2).'/'.ltrim((string)$row['digital_file'],'/');if(!is_file($file)){http_response_code(404);return;}$db->prepare('UPDATE order_downloads SET download_count=download_count+1,last_downloaded_at=NOW() WHERE id=?')->execute([(int)$row['id']]);$filename=preg_replace('/[^A-Za-z0-9._-]+/','-',basename($file));header('Content-Type: application/octet-stream');header('Content-Length: '.filesize($file));header('Content-Disposition: attachment; filename="'.$filename.'"');header('X-Content-Type-Options: nosniff');readfile($file);exit;
    }
    public function news(): void
    {
        $db=Database::connection();$posts=$db->query("SELECT p.*,u.name author_name,(SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id AND pc.status='approved') comment_count FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE p.status='published' ORDER BY p.published_at DESC,p.id DESC")->fetchAll();View::render('site/news',['title'=>'Actualités','active'=>'news','posts'=>$posts],'site');
    }
    public function article(): void
    {
        $slug=trim((string)($_GET['slug']??''));$db=Database::connection();$stmt=$db->prepare("SELECT p.*,u.name author_name FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE p.slug=? AND p.status='published' LIMIT 1");$stmt->execute([$slug]);$post=$stmt->fetch();if(!$post){http_response_code(404);View::render('errors/404',['title'=>'Article introuvable']);return;}$db->prepare('UPDATE posts SET views_count=views_count+1 WHERE id=?')->execute([(int)$post['id']]);$stmt=$db->prepare("SELECT name,content,created_at FROM post_comments WHERE post_id=? AND status='approved' ORDER BY created_at ASC");$stmt->execute([(int)$post['id']]);View::render('site/article',['title'=>$post['title'],'active'=>'news','post'=>$post,'comments'=>$stmt->fetchAll()],'site');
    }
    public function commentArticle(): void
    {
        $postId=(int)($_POST['post_id']??0);$name=mb_substr(trim((string)($_POST['name']??'')),0,120);$email=strtolower(trim((string)($_POST['email']??'')));$content=mb_substr(trim((string)($_POST['content']??'')),0,3000);if(!$postId||$name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$content===''){$_SESSION['flash']='Complétez correctement le formulaire de commentaire.';$this->redirect('/actualites');}$db=Database::connection();$stmt=$db->prepare('SELECT slug FROM posts WHERE id=? AND status="published"');$stmt->execute([$postId]);$slug=$stmt->fetchColumn();if(!$slug){$this->redirect('/actualites');}$stmt=$db->prepare("INSERT INTO post_comments(post_id,user_id,name,email,content,status) VALUES(?,?,?,?,?,'approved')");$stmt->execute([$postId,$_SESSION['user']['id']??null,$name,$email,$content]);$_SESSION['flash']='Votre commentaire a été publié.';$this->redirect('/actualites/'.$slug.'#commentaires');
    }
    public function savePassword(): void { $this->requireUser();$password=(string)($_POST['password']??'');$confirmation=(string)($_POST['password_confirmation']??'');if(strlen($password)<8||$password!==$confirmation){$_SESSION['flash']='Mot de passe invalide ou confirmation différente.';$this->redirect('/profil');}Database::connection()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),(int)$_SESSION['user']['id']]);$_SESSION['flash']='Mot de passe mis à jour.';$this->redirect('/profil'); }
    private function cartSubtotal(): float { return array_reduce($_SESSION['cart']??[],fn($sum,$item)=>$sum+($item['price']*$item['quantity']),0); }
    private function couponDiscount(?array $coupon,float $subtotal): float { if(!$coupon)return 0;$discount=$coupon['type']==='percent'?$subtotal*((float)$coupon['value']/100):(float)$coupon['value'];return min($subtotal,max(0,$discount)); }
    public function finalizePaidOrder(int $orderId,array $paymentData,string $provider='cinetpay'): void
    {
        $db=Database::connection();$db->beginTransaction();try{$stmt=$db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$stmt->execute([$orderId]);$order=$stmt->fetch();if(!$order){$db->rollBack();return;}$data=$paymentData['data']??[];$status=strtoupper((string)($data['status']??''));if($status!=='ACCEPTED'){$db->prepare("UPDATE payments SET status='failed',payload=? WHERE order_id=? AND provider=?")->execute([json_encode($paymentData),$orderId,$provider]);$db->commit();return;}$expected=round((float)$order['total']);$received=round((float)($data['amount']??$expected));$currency=strtoupper((string)($data['currency']??'XOF'));if($received!==$expected||$currency!=='XOF')throw new \RuntimeException('Montant ou devise de paiement incohérent.');if($order['payment_status']!=='paid'){$db->prepare("UPDATE orders SET payment_status='paid',status=CASE WHEN status='pending' THEN 'confirmed' ELSE status END,paid_at=NOW() WHERE id=?")->execute([$orderId]);$db->prepare("UPDATE payments SET status='paid',provider_reference=COALESCE(NULLIF(?,''),provider_reference),transaction_reference=?,payer_phone=?,payment_channel=?,paid_at=COALESCE(paid_at,NOW()),payload=? WHERE order_id=? AND provider=?")->execute([(string)($data['payment_method']??''),(string)($data['operator_id']??''),(string)($data['payer_phone']??''),(string)($data['payment_method']??''),json_encode($paymentData),$orderId,$provider]);$db->prepare('INSERT INTO order_status_history(order_id,status,note) VALUES(?,?,?)')->execute([$orderId,'confirmed','Paiement confirmé par '.$provider]);$this->fulfillOrder($db,$orderId,$order);} $db->commit();}catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }
    private function fulfillOrder(\PDO $db,int $orderId,array $order): void
    {
        $items=$db->prepare('SELECT * FROM order_items WHERE order_id=?');$items->execute([$orderId]);foreach($items->fetchAll() as $item){if($item['item_type']==='course'&&!empty($order['user_id'])){$stmt=$db->prepare("INSERT INTO enrollments(user_id,course_id,status,progress,payment_status,order_id) VALUES(?,?,'active',0,'paid',?) ON DUPLICATE KEY UPDATE status='active',payment_status='paid',order_id=VALUES(order_id)");$stmt->execute([(int)$order['user_id'],(int)$item['item_id'],$orderId]);}elseif($item['item_type']==='product'){$stmt=$db->prepare('SELECT product_type,digital_file,stock_quantity FROM products WHERE id=? FOR UPDATE');$stmt->execute([(int)$item['item_id']]);$product=$stmt->fetch();if(!$product)continue;if(($product['product_type']??'physical')==='digital'&&!empty($product['digital_file'])){$token=bin2hex(random_bytes(32));$db->prepare('INSERT INTO order_downloads(order_id,product_id,token_hash,max_downloads,expires_at) SELECT ?,id,?,download_limit,DATE_ADD(NOW(),INTERVAL 30 DAY) FROM products WHERE id=?')->execute([$orderId,hash('sha256',$token),(int)$item['item_id']]);$_SESSION['digital_download_tokens'][(int)$item['item_id']]=$token;}}}if(!empty($order['coupon_code']))$db->prepare('UPDATE coupons SET used_count=used_count+1 WHERE code=?')->execute([$order['coupon_code']]);
    }
    private function orderForConfirmation(int $id): ?array { $stmt=Database::connection()->prepare('SELECT * FROM orders WHERE id=?');$stmt->execute([$id]);$order=$stmt->fetch();if(!$order)return null;$order['downloads']=$this->orderDownloads($id);return $order; }
    private function orderDownloads(int $id): array
    {
        $tokens=$_SESSION['digital_download_tokens']??[];$stmt=Database::connection()->prepare("SELECT od.product_id,p.name FROM order_downloads od JOIN products p ON p.id=od.product_id WHERE od.order_id=? AND od.expires_at>NOW()");$stmt->execute([$id]);$rows=[];foreach($stmt->fetchAll() as $row)if(isset($tokens[(int)$row['product_id']]))$rows[]=['name'=>$row['name'],'url'=>'/commande/telecharger?token='.urlencode($tokens[(int)$row['product_id']])];return $rows;
    }
    private function publicUrl(string $path): string { $base=rtrim((string)Env::get('APP_URL',''),' /');if($base===''){$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$base=$scheme.'://'.($_SERVER['HTTP_HOST']??'localhost');}return $base.$path; }
    private function requireUser(): void { if(empty($_SESSION['user'])){$this->redirect('/connexion');} }
    private function redirect(string $path): never { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');header('Location: '.$base.$path);exit; }
}
