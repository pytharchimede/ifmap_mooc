<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Store;
use App\Core\Database;

final class SiteController
{
    private function trainingData(): array
    {
        try { return array_map(fn($r)=>['id'=>$r['id'],'slug'=>$r['slug'],'title'=>$r['title'],'sector'=>$r['category'],'mode'=>$r['mode'],'duration'=>$r['duration_label'],'price'=>(int)$r['price'],'tone'=>$r['tone'],'status'=>$r['status']], Database::connection()->query("SELECT * FROM courses WHERE status='published' ORDER BY id DESC")->fetchAll()); } catch(\Throwable) {} return [
            ['slug'=>'sous-gerant-station-service','title'=>'Le Métier de Sous-Gérant en Station-Service','sector'=>'Aval pétrolier','mode'=>'Présentiel','duration'=>'4 jours','price'=>75000,'tone'=>'navy'],
            ['slug'=>'depotage-carburant','title'=>'Le Dépotage du Carburant en Station-Service','sector'=>'Aval pétrolier','mode'=>'En ligne','duration'=>'8 heures','price'=>45000,'tone'=>'gold'],
            ['slug'=>'graissage-lubrifiants','title'=>'Graissage et Vente des Lubrifiants','sector'=>'Aval pétrolier','mode'=>'Mixte','duration'=>'3 jours','price'=>50000,'tone'=>'green'],
            ['slug'=>'installation-solaire','title'=>'Installation et Maintenance Solaire','sector'=>'Énergie solaire','mode'=>'Présentiel','duration'=>'5 jours','price'=>95000,'tone'=>'sun'],
            ['slug'=>'techniques-vente','title'=>'Techniques de Vente et Relation Client','sector'=>'Commerce & Marketing','mode'=>'En ligne','duration'=>'6 heures','price'=>35000,'tone'=>'coral'],
            ['slug'=>'excel-professionnel','title'=>'Excel Professionnel & Tableaux de Bord','sector'=>'Informatique','mode'=>'Mixte','duration'=>'3 jours','price'=>60000,'tone'=>'blue'],
        ];
    }

    private function products(): array
    {
        try { return array_map(fn($r)=>['id'=>$r['id'],'slug'=>$r['slug'],'title'=>$r['name'],'stock'=>$r['stock_status']==='in_stock'?'En stock':($r['stock_status']==='on_order'?'Sur commande':'Rupture'),'price'=>$r['price']===null?null:(int)$r['price'],'tone'=>'navy'], Database::connection()->query("SELECT * FROM products WHERE status='published' ORDER BY id DESC")->fetchAll()); } catch(\Throwable) {} return [
            ['slug'=>'pompe-carburant','title'=>'Pompe à carburant','stock'=>'En stock','price'=>null,'tone'=>'navy'],
            ['slug'=>'pistolet-distribution','title'=>'Pistolet de distribution','stock'=>'En stock','price'=>85000,'tone'=>'gold'],
            ['slug'=>'sabre-de-jauge','title'=>'Sabre de jauge','stock'=>'Sur commande','price'=>45000,'tone'=>'steel'],
            ['slug'=>'pate-kolor-kut','title'=>'Pâte Kolor Kut','stock'=>'En stock','price'=>18000,'tone'=>'coral'],
        ];
    }

    public function home(): void { View::render('site/home', ['title'=>'Accueil','active'=>'home'], 'site'); }
    public function trainings(): void { View::render('site/trainings', ['title'=>'Nos formations','active'=>'trainings','trainings'=>$this->trainingData()], 'site'); }
    public function training(): void { View::render('site/training', ['title'=>'Le Métier de Sous-Gérant','active'=>'trainings'], 'site'); }
    public function shop(): void { View::render('site/shop', ['title'=>'Boutique équipements','active'=>'shop','products'=>$this->products()], 'site'); }
    public function product(): void { View::render('site/product', ['title'=>'Sabre de jauge','active'=>'shop'], 'site'); }
    public function cart(): void { View::render('site/cart', ['title'=>'Votre panier','active'=>'cart'], 'site'); }
    public function removeCart(): void { unset($_SESSION['cart'][$_POST['key'] ?? '']); $_SESSION['flash']='Article retiré du panier.'; $this->redirect('/panier'); }
    public function clearCart(): void { $_SESSION['cart']=[]; $_SESSION['flash']='Panier vidé.'; $this->redirect('/panier'); }
    public function checkout(): void
    {
        if (empty($_SESSION['cart'])) { $_SESSION['flash']='Votre panier est vide.'; $this->redirect('/panier'); }
        View::render('site/checkout', ['title'=>'Informations et paiement','active'=>'cart','cart'=>$_SESSION['cart']], 'site');
    }
    public function placeOrder(): void
    {
        if (empty($_SESSION['cart'])) $this->redirect('/panier');
        $required=['name','email','phone','payment','delivery']; foreach($required as $field) if(trim($_POST[$field]??'')===''){$_SESSION['checkout_error']='Veuillez remplir tous les champs obligatoires.';$this->redirect('/commande');}
        if(!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL)){$_SESSION['checkout_error']='Adresse email invalide.';$this->redirect('/commande');}
        $subtotal=array_sum(array_map(fn($i)=>$i['price']*$i['quantity'],$_SESSION['cart']));
        $db=Database::connection();$db->beginTransaction();try{$reference='IF-'.date('Y').'-'.str_pad((string)((int)$db->query('SELECT COUNT(*)+1 FROM orders')->fetchColumn()),4,'0',STR_PAD_LEFT);$userId=$_SESSION['user']['id']??null;$status=$_POST['payment']==='delivery'?'pending':'paid';$paymentStatus=$_POST['payment']==='delivery'?'cod':'paid';$customer=['name'=>trim($_POST['name']),'email'=>trim($_POST['email']),'phone'=>trim($_POST['phone'])];$stmt=$db->prepare('INSERT INTO orders(user_id,reference,status,payment_method,payment_status,delivery_method,subtotal,total,customer_data) VALUES(?,?,?,?,?,?,?,?,?)');$stmt->execute([$userId,$reference,$status,$_POST['payment'],$paymentStatus,$_POST['delivery'],$subtotal,$subtotal,json_encode($customer,JSON_UNESCAPED_UNICODE)]);$orderId=(int)$db->lastInsertId();$itemStmt=$db->prepare('INSERT INTO order_items(order_id,item_type,item_id,label,quantity,unit_price,total) VALUES(?,?,?,?,?,?,?)');foreach($_SESSION['cart'] as $key=>$item)$itemStmt->execute([$orderId,$key==='training'?'course':'product',0,$item['name'],$item['quantity'],$item['price'],$item['price']*$item['quantity']]);$db->commit();$order=['id'=>$orderId,'reference'=>$reference,'customer'=>$customer['name'],'email'=>$customer['email'],'payment'=>$_POST['payment'],'items'=>$_SESSION['cart'],'total'=>$subtotal,'status'=>$status,'created_at'=>date('c')];}catch(\Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['checkout_error']='La commande n’a pas pu être enregistrée.';$this->redirect('/commande');}
        if(isset($_SESSION['cart']['training'])) $_SESSION['enrolled']=true;
        $_SESSION['last_order']=$order; $_SESSION['cart']=[]; $this->redirect('/commande/confirmation');
    }
    public function confirmation(): void { if(empty($_SESSION['last_order'])) $this->redirect('/'); View::render('site/confirmation',['title'=>'Commande confirmée','active'=>'cart','order'=>$_SESSION['last_order']],'site'); }
    public function account(): void { if(!empty($_SESSION['admin_authenticated'])&&empty($_SESSION['user'])) $this->redirect('/admin'); if(empty($_SESSION['user'])) $this->redirect('/connexion'); $stmt=Database::connection()->prepare("SELECT *, JSON_OBJECT() AS items FROM orders WHERE JSON_UNQUOTE(JSON_EXTRACT(customer_data,'$.email'))=? ORDER BY id DESC");$stmt->execute([$_SESSION['user']['email']]);$orders=$stmt->fetchAll();foreach($orders as &$o)$o['items']=array_fill(0,(int)(Database::connection()->query('SELECT COUNT(*) FROM order_items WHERE order_id='.(int)$o['id'])->fetchColumn()),1); View::render('site/account', ['title'=>'Mon espace IFMAP','active'=>'account','orders'=>$orders,'enrolled'=>$_SESSION['enrolled']??false,'user'=>$_SESSION['user']], 'site'); }
    public function addCart(): void
    {
        $type = ($_POST['type'] ?? '') === 'product' ? 'product' : 'training';
        $_SESSION['cart'][$type] = ($type === 'product')
            ? ['name'=>'Sabre de jauge','quantity'=>max(1, (int)($_POST['quantity'] ?? 1)),'price'=>45000]
            : ['name'=>'Le Métier de Sous-Gérant en Station-Service','quantity'=>1,'price'=>75000];
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        header('Location: ' . $base . '/panier'); exit;
    }
    private function redirect(string $path): never { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/'); header('Location: '.$base.$path); exit; }
}
