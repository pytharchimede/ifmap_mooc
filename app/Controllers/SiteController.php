<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Store;
use App\Core\Database;

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
        try { return array_map(fn($r)=>['id'=>$r['id'],'slug'=>$r['slug'],'title'=>$r['name'],'description'=>$r['description'],'category'=>$r['category']??'Équipements','image'=>$r['image'],'stock'=>$r['stock_status']==='in_stock'?'En stock':($r['stock_status']==='on_order'?'Sur commande':'Rupture'),'price'=>$r['price']===null?null:(int)$r['price'],'promotional_price'=>isset($r['promotional_price'])&&$r['promotional_price']!==null?(int)$r['promotional_price']:null,'tone'=>'navy'], Database::connection()->query("SELECT * FROM products WHERE status='published' ORDER BY id DESC")->fetchAll()); } catch(\Throwable) {} return [
            ['slug'=>'pompe-carburant','title'=>'Pompe à carburant','stock'=>'En stock','price'=>null,'tone'=>'navy'],
            ['slug'=>'pistolet-distribution','title'=>'Pistolet de distribution','stock'=>'En stock','price'=>85000,'tone'=>'gold'],
            ['slug'=>'sabre-de-jauge','title'=>'Sabre de jauge','stock'=>'Sur commande','price'=>45000,'tone'=>'steel'],
            ['slug'=>'pate-kolor-kut','title'=>'Pâte Kolor Kut','stock'=>'En stock','price'=>18000,'tone'=>'coral'],
        ];
    }

    public function home(): void { View::render('site/home', ['title'=>'Accueil','active'=>'home'], 'site'); }
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
        View::render('site/checkout', ['title'=>'Informations et paiement','active'=>'cart','cart'=>$_SESSION['cart']], 'site');
    }
    public function placeOrder(): void
    {
        if (empty($_SESSION['cart'])) $this->redirect('/panier');
        $hasProduct = (bool)array_filter($_SESSION['cart'],fn($item)=>($item['kind']??'')==='product');
        if (!$hasProduct) { $_POST['delivery']='pickup'; if(($_POST['payment']??'')==='delivery')$_POST['payment']='online'; }
        $required=['name','email','phone','payment','delivery']; foreach($required as $field) if(trim($_POST[$field]??'')===''){$_SESSION['checkout_error']='Veuillez remplir tous les champs obligatoires.';$this->redirect('/commande');}
        if($hasProduct&&($_POST['delivery']??'')==='delivery'&&trim((string)($_POST['address']??''))===''){$_SESSION['checkout_error']='Indiquez l’adresse complète de livraison.';$this->redirect('/commande');}
        if(!filter_var($_POST['email'],FILTER_VALIDATE_EMAIL)){$_SESSION['checkout_error']='Adresse email invalide.';$this->redirect('/commande');}
        $subtotal=$this->cartSubtotal();$coupon=$_SESSION['coupon']??null;$discount=$this->couponDiscount($coupon,$subtotal);$total=max(0,$subtotal-$discount);
        $db=Database::connection();$db->beginTransaction();try{
            $reference='IF-'.date('Y').'-'.str_pad((string)((int)$db->query('SELECT COUNT(*)+1 FROM orders')->fetchColumn()),4,'0',STR_PAD_LEFT);$userId=$_SESSION['user']['id']??null;$status=$_POST['payment']==='delivery'?'pending':'paid';$paymentStatus=$_POST['payment']==='delivery'?'cod':'paid';$customer=['name'=>trim($_POST['name']),'email'=>trim($_POST['email']),'phone'=>trim($_POST['phone']),'company'=>trim((string)($_POST['company']??'')),'address'=>trim((string)($_POST['address']??''))];
            $stmt=$db->prepare('INSERT INTO orders(user_id,reference,status,payment_method,payment_status,delivery_method,subtotal,discount,total,coupon_code,customer_data) VALUES(?,?,?,?,?,?,?,?,?,?,?)');$stmt->execute([$userId,$reference,$status,$_POST['payment'],$paymentStatus,$_POST['delivery'],$subtotal,$discount,$total,$coupon['code']??null,json_encode($customer,JSON_UNESCAPED_UNICODE)]);$orderId=(int)$db->lastInsertId();$itemStmt=$db->prepare('INSERT INTO order_items(order_id,item_type,item_id,label,quantity,unit_price,total) VALUES(?,?,?,?,?,?,?)');
            foreach($_SESSION['cart'] as $key=>$item){$itemId=($item['kind']??'')==='formation'?(int)($item['course_id']??0):(int)($item['product_id']??0);$itemType=($item['kind']??'')==='formation'?'course':'product';$itemStmt->execute([$orderId,$itemType,$itemId,$item['name'],$item['quantity'],$item['price'],$item['price']*$item['quantity']]);if($itemType==='product'&&$itemId)$db->prepare("UPDATE products SET stock_quantity=GREATEST(0,stock_quantity-?),stock_status=CASE WHEN stock_status='in_stock' AND stock_quantity<=0 THEN 'out_of_stock' ELSE stock_status END WHERE id=?")->execute([(int)$item['quantity'],$itemId]);if($itemType==='course'&&$userId&&$paymentStatus==='paid'&&$itemId){$db->prepare("INSERT INTO enrollments(user_id,course_id,status,source) VALUES(?,?,'active','purchase') ON DUPLICATE KEY UPDATE status='active'")->execute([$userId,$itemId]);}}
            $db->prepare('INSERT INTO order_status_history(order_id,status,note) VALUES(?,?,?)')->execute([$orderId,$status,'Commande créée']);if($coupon)$db->prepare('UPDATE coupons SET used_count=used_count+1 WHERE id=?')->execute([(int)$coupon['id']]);
            $db->commit();$order=['id'=>$orderId,'reference'=>$reference,'customer'=>$customer['name'],'email'=>$customer['email'],'payment'=>$_POST['payment'],'items'=>$_SESSION['cart'],'total'=>$total,'discount'=>$discount,'status'=>$status,'created_at'=>date('c')];
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['checkout_error']='La commande n’a pas pu être enregistrée.';$this->redirect('/commande');}
        $_SESSION['last_order']=$order; $_SESSION['cart']=[]; unset($_SESSION['coupon']); $this->redirect('/commande/confirmation');
    }
    public function confirmation(): void { if(empty($_SESSION['last_order'])) $this->redirect('/'); View::render('site/confirmation',['title'=>'Commande confirmée','active'=>'cart','order'=>$_SESSION['last_order']],'site'); }
    public function account(): void { if(!empty($_SESSION['admin_authenticated'])&&empty($_SESSION['user'])) $this->redirect('/admin'); if(empty($_SESSION['user'])) $this->redirect('/connexion'); $stmt=Database::connection()->prepare("SELECT *, JSON_OBJECT() AS items FROM orders WHERE JSON_UNQUOTE(JSON_EXTRACT(customer_data,'$.email'))=? ORDER BY id DESC");$stmt->execute([$_SESSION['user']['email']]);$orders=$stmt->fetchAll();foreach($orders as &$o)$o['items']=array_fill(0,(int)(Database::connection()->query('SELECT COUNT(*) FROM order_items WHERE order_id='.(int)$o['id'])->fetchColumn()),1); View::render('site/account', ['title'=>'Mon espace IFMAP','active'=>'account','orders'=>$orders,'enrolled'=>$_SESSION['enrolled']??false,'user'=>$_SESSION['user']], 'site'); }
    public function profile(): void { if(empty($_SESSION['user']))$this->redirect('/connexion');$stmt=Database::connection()->prepare('SELECT id,name,email,phone,avatar,role FROM users WHERE id=?');$stmt->execute([$_SESSION['user']['id']]);$user=$stmt->fetch()?:$_SESSION['user'];View::render('site/profile',['title'=>'Mon profil','active'=>'account','user'=>$user,'flash'=>$_SESSION['profile_flash']??null,'error'=>$_SESSION['profile_error']??null],'site');unset($_SESSION['profile_flash'],$_SESSION['profile_error']); }
    public function saveProfile(): void
    {
        if(empty($_SESSION['user']))$this->redirect('/connexion');$name=trim($_POST['name']??'');$phone=trim($_POST['phone']??'');if($name===''){$_SESSION['profile_error']='Le nom est obligatoire.';$this->redirect('/profil');}$avatar=$_SESSION['user']['avatar']??null;
        if(!empty($_FILES['avatar']['tmp_name'])&&is_uploaded_file($_FILES['avatar']['tmp_name'])){$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($_FILES['avatar']['tmp_name']);if(!isset($allowed[$mime])||(int)$_FILES['avatar']['size']>5*1024*1024){$_SESSION['profile_error']='Photo invalide. Utilisez JPG, PNG ou WebP, 5 Mo maximum.';$this->redirect('/profil');}$dir=dirname(__DIR__,2).'/public/uploads/avatars';if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)){$_SESSION['profile_error']='Impossible de créer le dossier des avatars.';$this->redirect('/profil');}$filename='avatar-'.(int)$_SESSION['user']['id'].'-'.bin2hex(random_bytes(5)).'.'.$allowed[$mime];if(!move_uploaded_file($_FILES['avatar']['tmp_name'],$dir.'/'.$filename)){$_SESSION['profile_error']='Impossible d’enregistrer la photo.';$this->redirect('/profil');}$avatar='/public/uploads/avatars/'.$filename;}
        $stmt=Database::connection()->prepare('UPDATE users SET name=?,phone=?,avatar=? WHERE id=?');$stmt->execute([$name,$phone,$avatar,$_SESSION['user']['id']]);$_SESSION['user']['name']=$name;$_SESSION['user']['phone']=$phone;$_SESSION['user']['avatar']=$avatar;$_SESSION['profile_flash']='Profil mis à jour.';$this->redirect('/profil');
    }
    public function savePassword(): void
    {
        if(empty($_SESSION['user']))$this->redirect('/connexion');$current=(string)($_POST['current_password']??'');$password=(string)($_POST['password']??'');$confirmation=(string)($_POST['password_confirmation']??'');$stmt=Database::connection()->prepare('SELECT password FROM users WHERE id=?');$stmt->execute([$_SESSION['user']['id']]);$hash=$stmt->fetchColumn();if(!$hash||!password_verify($current,$hash)){$_SESSION['profile_error']='Le mot de passe actuel est incorrect.';$this->redirect('/academie#profil');}if(strlen($password)<8||$password!==$confirmation){$_SESSION['profile_error']='Le nouveau mot de passe doit contenir 8 caractères minimum et les deux saisies doivent correspondre.';$this->redirect('/academie#profil');}$stmt=Database::connection()->prepare('UPDATE users SET password=? WHERE id=?');$stmt->execute([password_hash($password,PASSWORD_DEFAULT),$_SESSION['user']['id']]);$_SESSION['profile_flash']='Mot de passe modifié avec succès.';$this->redirect('/academie#profil');
    }
    public function addCart(): void
    {
        $type = ($_POST['type'] ?? '') === 'product' ? 'product' : 'training';
        if ($type === 'product') {
            $productId=(int)($_POST['product_id']??0);$stmt=Database::connection()->prepare("SELECT id,name,price,promotional_price,image,stock_status,stock_quantity FROM products WHERE id=? AND status='published' LIMIT 1");$stmt->execute([$productId]);$product=$stmt->fetch();if(!$product||$product['stock_status']==='out_of_stock'){$_SESSION['flash']='Ce produit n’est plus disponible.';$this->redirect('/boutique');}$quantity=max(1,(int)($_POST['quantity']??1));if($product['stock_status']==='in_stock'&&(int)$product['stock_quantity']>0)$quantity=min($quantity,(int)$product['stock_quantity']);$price=$product['promotional_price']!==null?(int)$product['promotional_price']:(int)$product['price'];$key='product_'.$productId;$_SESSION['cart'][$key]=['name'=>$product['name'],'quantity'=>$quantity,'price'=>$price,'product_id'=>$productId,'kind'=>'product','image'=>$product['image']];
        } else {
            $courseId = (int) ($_POST['course_id'] ?? 0);
            $stmt = Database::connection()->prepare("SELECT title,price FROM courses WHERE id=? AND status='published' LIMIT 1");
            $stmt->execute([$courseId]);
            $course = $stmt->fetch();
            if (!$course) {
                $_SESSION['flash'] = 'Cette formation n’est plus disponible.';
                $this->redirect('/formations');
            }
            $_SESSION['cart'][$type] = ['name' => $course['title'], 'quantity' => 1, 'price' => (int) $course['price'], 'course_id' => $courseId, 'kind' => 'formation'];
        }
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        header('Location: ' . $base . '/panier'); exit;
    }
    private function cartSubtotal(): float { return array_sum(array_map(fn($i)=>(float)$i['price']*(int)$i['quantity'],$_SESSION['cart']??[])); }
    private function couponDiscount(?array $coupon,float $subtotal): float { if(!$coupon)return 0;return min($subtotal,$coupon['type']==='percent'?$subtotal*min(100,(float)$coupon['value'])/100:(float)$coupon['value']); }
    public function news(): void
    {
        $db=Database::connection();
        $posts=$db->query("SELECT p.*,u.name author_name,(SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id AND pc.status='approved') comment_count FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE p.status='published' AND (p.published_at IS NULL OR p.published_at<=NOW()) ORDER BY COALESCE(p.published_at,p.created_at) DESC")->fetchAll();
        View::render('site/news',['title'=>'Actualités','active'=>'news','posts'=>$posts],'site');
    }
    public function article(): void
    {
        $slug=trim((string)($_GET['slug']??''));$db=Database::connection();
        $stmt=$db->prepare("SELECT p.*,u.name author_name FROM posts p LEFT JOIN users u ON u.id=p.author_id WHERE p.slug=? AND p.status='published' AND (p.published_at IS NULL OR p.published_at<=NOW()) LIMIT 1");$stmt->execute([$slug]);$post=$stmt->fetch();
        if(!$post){http_response_code(404);View::render('errors/404',['title'=>'Article introuvable']);return;}
        $stmt=$db->prepare("SELECT pc.*,u.avatar FROM post_comments pc LEFT JOIN users u ON u.id=pc.user_id WHERE pc.post_id=? AND pc.status='approved' ORDER BY pc.created_at ASC");$stmt->execute([(int)$post['id']]);
        View::render('site/article',['title'=>$post['title'],'active'=>'news','post'=>$post,'comments'=>$stmt->fetchAll()],'site');
    }
    public function commentArticle(): void
    {
        $postId=(int)($_POST['post_id']??0);$name=mb_substr(trim((string)($_POST['name']??($_SESSION['user']['name']??''))),0,120);$email=mb_substr(trim((string)($_POST['email']??($_SESSION['user']['email']??''))),0,190);$content=mb_substr(trim((string)($_POST['content']??'')),0,3000);$db=Database::connection();
        $stmt=$db->prepare("SELECT slug FROM posts WHERE id=? AND status='published'");$stmt->execute([$postId]);$slug=$stmt->fetchColumn();if(!$slug)$this->redirect('/actualites');
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($content)<3){$_SESSION['flash']='Renseignez un nom, un email valide et votre commentaire.';$this->redirect('/actualites/'.$slug.'#commentaires');}
        $stmt=$db->prepare("INSERT INTO post_comments(post_id,user_id,name,email,content,status) VALUES(?,?,?,?,?,'approved')");$stmt->execute([$postId,$_SESSION['user']['id']??null,$name,$email,$content]);$_SESSION['flash']='Votre commentaire est publié.';$this->redirect('/actualites/'.$slug.'#commentaires');
    }
    private function redirect(string $path): never { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/'); header('Location: '.$base.$path); exit; }
}
