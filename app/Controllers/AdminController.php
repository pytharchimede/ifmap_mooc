<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Env;
use App\Core\Store;
use App\Core\Database;
use App\Core\HtmlSanitizer;
use App\Services\R2Storage;

final class AdminController
{
    private function guard(): void
    {
        if (!($_SESSION['admin_authenticated'] ?? false)) {
            header('Location: ' . $this->baseUrl('/admin/connexion'));
            exit;
        }
    }

    private function baseUrl(string $path): string
    {
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        return $base . $path;
    }

    public function login(): void
    {
        if ($_SESSION['admin_authenticated'] ?? false) { header('Location: ' . $this->baseUrl('/admin')); exit; }
        View::render('admin/login', ['title'=>'Connexion administration','error'=>$_SESSION['login_error'] ?? null], 'auth');
        unset($_SESSION['login_error']);
    }

    public function authenticate(): void
    {
        $identifier = strtolower(trim((string)($_POST['username'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $valid = hash_equals(strtolower((string)Env::get('ADMIN_USERNAME', 'admin')), $identifier)
            && hash_equals((string)Env::get('ADMIN_PASSWORD', 'admin'), $password);
        $admin = null;
        if (!$valid) {
            try {
                $phone=preg_replace('/[^0-9+]/','',$identifier);$stmt = Database::connection()->prepare("SELECT id,name,email,phone,password,role,status FROM users WHERE (LOWER(email)=LOWER(?) OR phone=?) AND role='admin' LIMIT 1");
                $stmt->execute([$identifier,$phone]);
                $admin = $stmt->fetch() ?: null;
                $valid = $admin && ($admin['status']??'active')==='active' && password_verify($password, $admin['password']);
            } catch (\Throwable) { $valid = false; }
        }
        if ($valid) {
            if (!$admin) { try { $admin=Database::connection()->query("SELECT id,name,email,role FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetch()?:null; } catch (\Throwable) {} }
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_name'] = $admin['name'] ?? 'Administrateur IFMAP';
            if ($admin) { unset($admin['password']); $_SESSION['user'] = $admin; }
            header('Location: ' . $this->baseUrl('/admin')); exit;
        }
        $_SESSION['login_error'] = 'Identifiant ou mot de passe incorrect.';
        header('Location: ' . $this->baseUrl('/admin/connexion')); exit;
    }

    public function logout(): void
    {
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_name'], $_SESSION['user']);
        header('Location: ' . $this->baseUrl('/admin/connexion')); exit;
    }

    public function index(): void { $this->guard();$db=Database::connection();$courses=$db->query("SELECT c.id,c.title,c.category,COALESCE(ROUND(AVG(e.progress)),0) progress,COUNT(e.user_id) enrolled,c.tone color FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id GROUP BY c.id ORDER BY enrolled DESC LIMIT 5")->fetchAll();$metrics=['users'=>(int)$db->query("SELECT COUNT(*) FROM users WHERE role<>'admin'")->fetchColumn(),'pending'=>(int)$db->query("SELECT COUNT(*) FROM users WHERE status='disabled'")->fetchColumn(),'enrollments'=>(int)$db->query("SELECT COUNT(*) FROM enrollments")->fetchColumn(),'revenue'=>(float)$db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='paid'")->fetchColumn(),'pendingRevenue'=>(float)$db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status IN ('pending','cod')")->fetchColumn()];$inventory=['units'=>(int)$db->query("SELECT COALESCE(SUM(stock_quantity),0) FROM products WHERE status<>'archived' AND product_type='physical'")->fetchColumn(),'value'=>(float)$db->query("SELECT COALESCE(SUM(stock_quantity*COALESCE(promotional_price,price,0)),0) FROM products WHERE status<>'archived' AND product_type='physical'")->fetchColumn(),'low'=>(int)$db->query("SELECT COUNT(*) FROM products WHERE status<>'archived' AND product_type='physical' AND stock_status='in_stock' AND stock_quantity<=low_stock_threshold")->fetchColumn(),'out'=>(int)$db->query("SELECT COUNT(*) FROM products WHERE status<>'archived' AND product_type='physical' AND (stock_status='out_of_stock' OR stock_quantity=0)")->fetchColumn()];$stockProducts=$db->query("SELECT id,name,reference,stock_quantity,low_stock_threshold,stock_status,image FROM products WHERE status<>'archived' AND product_type='physical' ORDER BY (stock_status='out_of_stock' OR stock_quantity=0) DESC,(stock_quantity<=low_stock_threshold) DESC,stock_quantity ASC LIMIT 8")->fetchAll();$movements=$db->query("SELECT sm.*,p.name product_name FROM stock_movements sm JOIN products p ON p.id=sm.product_id ORDER BY sm.id DESC LIMIT 8")->fetchAll();View::render('admin/dashboard',['title'=>'Vue d’ensemble','active'=>'admin-dashboard','courses'=>$courses,'metrics'=>$metrics,'inventory'=>$inventory,'stockProducts'=>$stockProducts,'movements'=>$movements],'admin'); }
    public function courses(): void { $this->guard(); $courses=Database::connection()->query("SELECT id,title,category sector,mode,duration_label duration,price,tone,status FROM courses ORDER BY id DESC")->fetchAll(); View::render('admin/courses', ['title'=>'Gestion des cours','active'=>'admin-courses','courses'=>$courses], 'admin'); }
    public function courseForm(): void { $this->guard(); $db=Database::connection(); $availableCourses=$db->query("SELECT id,title FROM courses WHERE status IN ('published','draft') ORDER BY title")->fetchAll(); $instructors=$db->query("SELECT id,name FROM users WHERE role='instructor' AND status='active' ORDER BY name")->fetchAll(); View::render('admin/course-form',['title'=>'Nouvelle formation','active'=>'admin-courses','availableCourses'=>$availableCourses,'instructors'=>$instructors],'admin'); }
    public function editCourse(): void { $this->guard();$id=(int)($_GET['id']??0);$db=Database::connection();$course=$db->query('SELECT * FROM courses WHERE id='.$id)->fetch();if(!$course)$this->redirect('/admin/cours');$availableCourses=$db->query("SELECT id,title FROM courses WHERE id<>$id ORDER BY title")->fetchAll();$instructors=$db->query("SELECT id,name FROM users WHERE role='instructor' AND status='active' ORDER BY name")->fetchAll();$skills=$db->query("SELECT skill_name FROM course_prerequisites WHERE course_id=$id AND type='skill' ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);$requiredCourses=$db->query("SELECT prerequisite_course_id FROM course_prerequisites WHERE course_id=$id AND type='course'")->fetchAll(\PDO::FETCH_COLUMN);View::render('admin/course-edit',['title'=>'Modifier la formation','active'=>'admin-courses','course'=>$course,'availableCourses'=>$availableCourses,'instructors'=>$instructors,'skills'=>$skills,'requiredCourses'=>$requiredCourses],'admin'); }
    public function updateCourse(): void
    {
        $this->guard();
        $id = (int) ($_POST['course_id'] ?? 0);
        $title = mb_substr(trim($_POST['title'] ?? ''), 0, 190);

        if ($id < 1 || $title === '') {
            $_SESSION['flash'] = 'Titre invalide.';
            $this->redirect('/admin/cours');
        }

        $db = Database::connection();
        $course = $db->query('SELECT thumbnail FROM courses WHERE id=' . $id)->fetch();
        $thumbnail = $this->uploadThumbnail($course['thumbnail'] ?? null, '/admin/cours/modifier?id=' . $id);
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('UPDATE courses SET title=?,category=?,mode=?,duration_label=?,price=?,status=?,description=?,thumbnail=?,instructor_id=? WHERE id=?');
            $stmt->execute([
                $title,
                trim($_POST['sector'] ?? 'Autre'),
                $_POST['mode'] ?? 'Présentiel',
                trim($_POST['duration'] ?? '1 jour'),
                max(0, (int) ($_POST['price'] ?? 0)),
                in_array($_POST['status'] ?? '', ['draft', 'published', 'archived'], true) ? $_POST['status'] : 'draft',
                trim($_POST['description'] ?? ''),
                $thumbnail,
                !empty($_POST['instructor_id']) ? (int) $_POST['instructor_id'] : null,
                $id,
            ]);

            $this->savePrerequisites($db, $id);
            $db->commit();
            $_SESSION['flash'] = 'Formation et prérequis mis à jour.';
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash'] = 'La mise à jour a échoué.';
        }

        $this->redirect('/admin/cours/modifier?id=' . $id);
    }
    public function builder(): void { $this->guard();$id=(int)($_GET['course']??0);$db=Database::connection();$course=$db->query('SELECT * FROM courses WHERE id='.$id)->fetch();if(!$course)$this->redirect('/admin/cours');$modules=$db->query('SELECT * FROM modules WHERE course_id='.$id.' ORDER BY position,id')->fetchAll();foreach($modules as &$m){$m['lessons']=$db->query('SELECT * FROM lessons WHERE module_id='.(int)$m['id'].' ORDER BY position,id')->fetchAll();$m['assessments']=$db->query('SELECT * FROM assessments WHERE module_id='.(int)$m['id'])->fetchAll();}View::render('admin/builder',['title'=>'Programme du cours','active'=>'admin-courses','course'=>$course,'modules'=>$modules],'admin'); }
    public function previewCourse(): void
    {
        $this->guard();$courseId=(int)($_GET['course']??0);$db=Database::connection();$stmt=$db->prepare('SELECT * FROM courses WHERE id=?');$stmt->execute([$courseId]);$course=$stmt->fetch();if(!$course)$this->redirect('/admin/cours');$modules=$db->query('SELECT * FROM modules WHERE course_id='.$courseId.' ORDER BY position,id')->fetchAll();foreach($modules as &$module){$module['lessons']=$db->query('SELECT * FROM lessons WHERE module_id='.(int)$module['id'].' ORDER BY position,id')->fetchAll();$module['assessment']=$db->query('SELECT * FROM assessments WHERE module_id='.(int)$module['id'].' LIMIT 1')->fetch()?:null;}unset($module);$final=$db->query("SELECT * FROM assessments WHERE course_id=$courseId AND type='final_exam' LIMIT 1")->fetch()?:null;View::render('academy/course',['title'=>'Aperçu — '.$course['title'],'active'=>'admin-courses','course'=>$course,'modules'=>$modules,'final'=>$final,'enrollment'=>null,'previewMode'=>true],'admin');
    }
    public function previewLesson(): void
    {
        $this->guard();$id=(int)($_GET['id']??0);$db=Database::connection();$stmt=$db->prepare('SELECT l.*,m.course_id,m.title module_title,c.title course_title FROM lessons l JOIN modules m ON m.id=l.module_id JOIN courses c ON c.id=m.course_id WHERE l.id=?');$stmt->execute([$id]);$lesson=$stmt->fetch();if(!$lesson){http_response_code(404);return;}if($lesson['type']==='video'&&str_starts_with((string)$lesson['content'],'r2://')){try{$lesson['content']=(new R2Storage())->playbackUrl((string)$lesson['content']);}catch(\Throwable $e){error_log('Aperçu R2 leçon '.$id.' : '.$e->getMessage());$lesson['content']='';}}View::render('academy/lesson',['title'=>'Aperçu — '.$lesson['title'],'active'=>'admin-courses','lesson'=>$lesson,'previewMode'=>true],'admin');
    }
    public function r2VideoUploadUrl(): void
    {
        $this->guard();header('Content-Type: application/json; charset=utf-8');$payload=json_decode((string)file_get_contents('php://input'),true)?:[];try{$courseId=(int)($payload['course_id']??0);if($courseId<1)throw new \InvalidArgumentException('Formation invalide.');$stmt=Database::connection()->prepare('SELECT 1 FROM courses WHERE id=?');$stmt->execute([$courseId]);if(!$stmt->fetchColumn())throw new \InvalidArgumentException('Formation introuvable.');$result=(new R2Storage())->createVideoUpload($courseId,(string)($payload['name']??'video'),(string)($payload['type']??''),(int)($payload['size']??0));echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_SLASHES);return;}catch(\Throwable $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
    }
    public function lessonDrafts(): void
    {
        $this->guard();header('Content-Type: application/json; charset=utf-8');$drafts=$_SESSION['builder_lesson_drafts']??[];unset($_SESSION['builder_lesson_drafts']);$storage=new R2Storage();foreach($drafts as &$draft){$draft['preview_url']=null;if(str_starts_with((string)($draft['content']??''),'r2://'))try{$draft['preview_url']=$storage->playbackUrl((string)$draft['content']);}catch(\Throwable){}}unset($draft);echo json_encode(['ok'=>true,'drafts'=>$drafts],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    public function exams(): void { $this->guard();$id=(int)($_GET['course']??0);$db=Database::connection();$course=$db->query('SELECT id,title FROM courses WHERE id='.$id)->fetch();if(!$course)$this->redirect('/admin/cours');$assessments=$db->query("SELECT a.*,m.title module_title,(SELECT COUNT(*) FROM questions q WHERE q.assessment_id=a.id) question_count FROM assessments a LEFT JOIN modules m ON m.id=a.module_id WHERE a.course_id=$id ORDER BY a.type,a.id")->fetchAll();View::render('admin/exams',['title'=>'Compositions et examen final','active'=>'admin-courses','course'=>$course,'assessments'=>$assessments],'admin'); }
    public function builderAction(): void
    {
        $this->guard(); $db=Database::connection(); $courseId=(int)($_POST['course_id']??0); $action=$_POST['builder_action']??'';
        if($action==='module'){$title=mb_substr(trim($_POST['title']??''),0,190);if($title===''){$_SESSION['flash']='Le titre de la section est obligatoire.';$this->redirect('/admin/cours/programme?course='.$courseId);}$s=$db->prepare('INSERT INTO modules(course_id,title,position) VALUES(?,?,?)');$s->execute([$courseId,$title,(int)$_POST['position']]);}
        elseif($action==='lesson'){
            $moduleId=(int)($_POST['module_id']??0);$type=in_array($_POST['type']??'', ['video','text','file'],true)?$_POST['type']:'text';$r2Content=trim((string)($_POST['r2_content']??''));$content=$r2Content!==''?$r2Content:trim((string)($_POST['content']??''));$_SESSION['builder_lesson_drafts'][$moduleId]=['title'=>trim((string)($_POST['title']??'')),'type'=>$type,'content'=>$content,'duration'=>(int)($_POST['duration']??10),'position'=>(int)($_POST['position']??1)];
            if(!empty($_FILES['attachment']['tmp_name'])&&is_uploaded_file($_FILES['attachment']['tmp_name'])){
                $allowed=['video/mp4'=>'mp4','video/webm'=>'webm','application/pdf'=>'pdf','application/zip'=>'zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx','application/vnd.openxmlformats-officedocument.presentationml.presentation'=>'pptx'];
                $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($_FILES['attachment']['tmp_name']);
                if(!isset($allowed[$mime])){$_SESSION['flash']='Format de fichier non autorisé.';$this->redirect('/admin/cours/programme?course='.$courseId);}
                if((int)$_FILES['attachment']['size']>150*1024*1024){$_SESSION['flash']='Le fichier dépasse 150 Mo.';$this->redirect('/admin/cours/programme?course='.$courseId);}
                $directory=dirname(__DIR__,2).'/public/uploads/courses';
                if(!is_dir($directory)&&!mkdir($directory,0775,true)&&!is_dir($directory)){$_SESSION['flash']='Impossible de créer le dossier des fichiers.';$this->redirect('/admin/cours/programme?course='.$courseId);}
                $filename=date('YmdHis').'-'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
                if(!move_uploaded_file($_FILES['attachment']['tmp_name'],$directory.'/'.$filename)){$_SESSION['flash']='Échec de l’enregistrement du fichier.';$this->redirect('/admin/cours/programme?course='.$courseId);}
                $content='/public/uploads/courses/'.$filename;
            }
            if($type==='text')$content=\App\Core\HtmlSanitizer::clean($content);
            if($content===''){$_SESSION['flash']='Ajoutez un contenu, une URL ou un fichier.';$this->redirect('/admin/cours/programme?course='.$courseId);}
            $title=mb_substr(trim($_POST['title']??''),0,190);if($title===''){$_SESSION['flash']='Le titre de la leçon est obligatoire.';$this->redirect('/admin/cours/programme?course='.$courseId);}$s=$db->prepare('INSERT INTO lessons(module_id,title,type,content,duration,position) VALUES(?,?,?,?,?,?)');$s->execute([$moduleId,$title,$type,$content,max(0,(int)$_POST['duration']),(int)$_POST['position']]);unset($_SESSION['builder_lesson_drafts'][$moduleId]);$_SESSION['flash']='✓ Leçon « '.$title.' » créée avec succès.';
        }
        elseif($action==='assessment'){$moduleId=!empty($_POST['module_id'])?(int)$_POST['module_id']:null;$title=mb_substr(trim($_POST['title']??''),0,190);if($title===''){$_SESSION['flash']='Le titre de la composition est obligatoire.';$this->redirect('/admin/cours/programme?course='.$courseId);}$score=min(100,max(1,(int)($_POST['passing_score']??70)));$attempts=min(20,max(1,(int)($_POST['attempts_allowed']??3)));$type=($_POST['type']??'')==='final_exam'?'final_exam':'section_quiz';$s=$db->prepare('INSERT INTO assessments(course_id,module_id,title,type,passing_score,attempts_allowed,status) VALUES(?,?,?,?,?,?,?)');$s->execute([$courseId,$moduleId,$title,$type,$score,$attempts,'published']);}
        elseif($action==='question'){$options=array_values(array_filter(array_map('trim',[$_POST['option_a']??'',$_POST['option_b']??'',$_POST['option_c']??'',$_POST['option_d']??''])));$correct=(string)($_POST['correct_answer']??'');$s=$db->prepare('INSERT INTO questions(assessment_id,question,options,correct_answer,points,explanation) VALUES(?,?,?,?,1,?)');$s->execute([(int)$_POST['assessment_id'],trim($_POST['question']),json_encode($options,JSON_UNESCAPED_UNICODE),$correct,trim($_POST['explanation']??'')]);}
        $_SESSION['flash']='Programme mis à jour.';$this->redirect('/admin/cours/programme?course='.$courseId);
    }
    public function saveCourse(): void
    {
        $this->guard();
        $title = mb_substr(trim($_POST['title'] ?? ''), 0, 190);

        if ($title === '') {
            $_SESSION['flash'] = 'Le titre est obligatoire.';
            $this->redirect('/admin/cours/nouveau');
        }

        $thumbnail = $this->uploadThumbnail(null, '/admin/cours/nouveau');
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('INSERT INTO courses(title,slug,category,mode,duration_label,price,tone,status,description,thumbnail,instructor_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $title,
                strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')),
                trim($_POST['sector'] ?? 'Autre'),
                $_POST['mode'] ?? 'Présentiel',
                trim($_POST['duration'] ?? '1 jour'),
                max(0, (int) ($_POST['price'] ?? 0)),
                'navy',
                $_POST['status'] ?? 'draft',
                trim($_POST['description'] ?? ''),
                $thumbnail,
                !empty($_POST['instructor_id']) ? (int) $_POST['instructor_id'] : null,
            ]);

            $courseId = (int) $db->lastInsertId();
            $this->savePrerequisites($db, $courseId);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash'] = 'La formation n’a pas pu être créée.';
            $this->redirect('/admin/cours/nouveau');
        }

        $_SESSION['flash']='Formation créée avec ses prérequis. Ajoutez maintenant ses sections et leçons.'; $this->redirect('/admin/cours/programme?course='.$courseId);
    }
    public function deleteCourse(): void { $this->guard(); $stmt=Database::connection()->prepare('DELETE FROM courses WHERE id=?');$stmt->execute([(int)($_POST['id']??0)]);$_SESSION['flash']='Formation supprimée.';$this->redirect('/admin/cours'); }
    public function products(): void { $this->guard();$db=Database::connection();$items=$db->query("SELECT id,name title,reference,category,product_type,stock_quantity,low_stock_threshold,stock_status,CASE WHEN product_type='digital' THEN 'Numérique' WHEN stock_status='in_stock' THEN 'En stock' WHEN stock_status='on_order' THEN 'Sur commande' ELSE 'Rupture' END stock,price,promotional_price,status,image FROM products ORDER BY id DESC")->fetchAll();$movements=$db->query("SELECT sm.*,p.name product_name,p.reference FROM stock_movements sm JOIN products p ON p.id=sm.product_id ORDER BY sm.id DESC LIMIT 30")->fetchAll();View::render('admin/resources',['title'=>'Stock et produits','active'=>'admin-products','type'=>'products','items'=>$items,'movements'=>$movements],'admin'); }
    public function productForm(): void { $this->guard();$id=(int)($_GET['id']??0);$product=null;if($id){$stmt=Database::connection()->prepare('SELECT * FROM products WHERE id=?');$stmt->execute([$id]);$product=$stmt->fetch();}View::render('admin/product-form',['title'=>$product?'Modifier le produit':'Nouveau produit','active'=>'admin-products','product'=>$product],'admin'); }
    public function saveProduct(): void
    {
        $this->guard();$id=(int)($_POST['id']??0);$name=mb_substr(trim((string)($_POST['name']??'')),0,190);$redirect=$id?'/admin/produits/modifier?id='.$id:'/admin/produits/nouveau';if($name===''){$_SESSION['flash']='Le nom du produit est obligatoire.';$this->redirect($redirect);}$db=Database::connection();$current=[];if($id){$stmt=$db->prepare('SELECT image,gallery,digital_file FROM products WHERE id=?');$stmt->execute([$id]);$current=$stmt->fetch()?:[];}$images=$this->uploadProductImages($current,$redirect);$productType=($_POST['product_type']??'physical')==='digital'?'digital':'physical';$digitalFile=$this->uploadDigitalProduct($current['digital_file']??null,$redirect);if($productType==='digital'&&!$digitalFile){$_SESSION['flash']='Ajoutez le fichier téléchargeable du produit numérique.';$this->redirect($redirect);}$slug=$this->uniqueProductSlug($db,$name,$id);$price=($_POST['price']??'')===''?null:max(0,(float)$_POST['price']);$promo=($_POST['promotional_price']??'')===''?null:max(0,(float)$_POST['promotional_price']);if($promo!==null&&$price!==null&&$promo>=$price)$promo=null;$stockStatus=$productType==='digital'?'in_stock':($_POST['stock_status']??'in_stock');$stockQuantity=$productType==='digital'?0:max(0,(int)($_POST['stock_quantity']??0));$values=[$name,$slug,mb_substr(trim((string)($_POST['reference']??'')),0,80)?:'IFMAP-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8)),mb_substr(trim((string)($_POST['category']??'Équipements')),0,120),$productType,trim((string)($_POST['description']??'')),$price,$promo,$stockStatus,$stockQuantity,max(0,(int)($_POST['low_stock_threshold']??5)),mb_substr(trim((string)($_POST['lead_time']??'')),0,100),$images['image'],json_encode($images['gallery']),$digitalFile,max(1,(int)($_POST['download_limit']??5)),in_array($_POST['status']??'',['draft','published','archived'],true)?$_POST['status']:'draft'];try{if($id){$values[]=$id;$db->prepare('UPDATE products SET name=?,slug=?,reference=?,category=?,product_type=?,description=?,price=?,promotional_price=?,stock_status=?,stock_quantity=?,low_stock_threshold=?,lead_time=?,image=?,gallery=?,digital_file=?,download_limit=?,status=? WHERE id=?')->execute($values);}else{$db->prepare('INSERT INTO products(name,slug,reference,category,product_type,description,price,promotional_price,stock_status,stock_quantity,low_stock_threshold,lead_time,image,gallery,digital_file,download_limit,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values);$id=(int)$db->lastInsertId();}foreach($images['delete'] as $old)$this->deleteProductImageFile($old);$_SESSION['flash']='Produit et galerie enregistrés avec succès.';$this->redirect('/admin/produits/modifier?id='.$id);}catch(\Throwable){$_SESSION['flash']='Enregistrement impossible : vérifiez notamment que la référence est unique.';$this->redirect($redirect);}
    }
    public function deleteProduct(): void { $this->guard();$id=(int)($_POST['id']??0);Database::connection()->prepare("UPDATE products SET status='archived' WHERE id=?")->execute([$id]);$_SESSION['flash']='Produit archivé.';$this->redirect('/admin/produits'); }
    public function adjustStock(): void { $this->guard();$id=(int)($_POST['id']??0);$mode=in_array($_POST['mode']??'',['in','out','set'],true)?$_POST['mode']:'in';$quantity=max(0,(int)($_POST['quantity']??0));$note=mb_substr(trim((string)($_POST['note']??'')),0,255);if(!$id||($quantity===0&&$mode!=='set')){$_SESSION['flash']='Indiquez une quantité valide.';$this->redirect('/admin/produits');}$db=Database::connection();$db->beginTransaction();try{$stmt=$db->prepare('SELECT stock_quantity FROM products WHERE id=? FOR UPDATE');$stmt->execute([$id]);$before=$stmt->fetchColumn();if($before===false)throw new \RuntimeException('Produit introuvable');$before=(int)$before;$after=$mode==='set'?$quantity:($mode==='in'?$before+$quantity:max(0,$before-$quantity));$type=$mode==='set'?'adjustment':$mode;$db->prepare("UPDATE products SET stock_quantity=?,stock_status=CASE WHEN ?=0 THEN 'out_of_stock' WHEN stock_status='out_of_stock' THEN 'in_stock' ELSE stock_status END WHERE id=?")->execute([$after,$after,$id]);$db->prepare('INSERT INTO stock_movements(product_id,movement_type,quantity,stock_before,stock_after,note) VALUES(?,?,?,?,?,?)')->execute([$id,$type,$mode==='out'?-$quantity:($mode==='set'?$after-$before:$quantity),$before,$after,$note?:'Ajustement manuel']);$db->commit();$_SESSION['flash']='Stock mis à jour : '.$before.' → '.$after.' unité(s).';}catch(\Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Impossible de mettre à jour le stock.';}$this->redirect('/admin/produits'); }
    public function coupons(): void { $this->guard();$items=Database::connection()->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();View::render('admin/coupons',['title'=>'Coupons promotionnels','active'=>'admin-coupons','items'=>$items],'admin'); }
    public function saveCoupon(): void { $this->guard();$code=strtoupper(preg_replace('/[^A-Z0-9_-]/i','',trim((string)($_POST['code']??''))));if($code===''){$_SESSION['flash']='Le code coupon est obligatoire.';$this->redirect('/admin/coupons');}$type=($_POST['type']??'')==='fixed'?'fixed':'percent';$value=max(0,(float)($_POST['value']??0));$stmt=Database::connection()->prepare("INSERT INTO coupons(code,type,value,minimum_amount,usage_limit,starts_at,expires_at,status) VALUES(?,?,?,?,?,?,?,'active') ON DUPLICATE KEY UPDATE type=VALUES(type),value=VALUES(value),minimum_amount=VALUES(minimum_amount),usage_limit=VALUES(usage_limit),starts_at=VALUES(starts_at),expires_at=VALUES(expires_at),status='active'");$stmt->execute([$code,$type,$value,max(0,(float)($_POST['minimum_amount']??0)),($_POST['usage_limit']??'')===''?null:max(1,(int)$_POST['usage_limit']),($_POST['starts_at']??'')?:null,($_POST['expires_at']??'')?:null]);$_SESSION['flash']='Coupon enregistré.';$this->redirect('/admin/coupons'); }
    public function deleteCoupon(): void { $this->guard();Database::connection()->prepare("UPDATE coupons SET status='inactive' WHERE id=?")->execute([(int)($_POST['id']??0)]);$_SESSION['flash']='Coupon désactivé.';$this->redirect('/admin/coupons'); }
    public function users(): void { $this->guard(); $items=Database::connection()->query('SELECT id,name,email,phone,role,status,created_at FROM users ORDER BY id DESC')->fetchAll();View::render('admin/resources',['title'=>'Utilisateurs','active'=>'admin-users','type'=>'users','items'=>$items],'admin'); }
    public function activateUser(): void { $this->guard();$id=(int)($_POST['id']??0);$db=Database::connection();$db->prepare("UPDATE users SET status='active',phone_verified_at=COALESCE(phone_verified_at,NOW()),otp_code=NULL,otp_expires_at=NULL WHERE id=?")->execute([$id]);$db->prepare("UPDATE enrollments SET status='active' WHERE user_id=? AND status='pending'")->execute([$id]);$_SESSION['flash']='Compte et inscriptions activés.';$this->redirect('/admin/utilisateurs'); }
    public function editUser(): void
    {
        $this->guard();$id=(int)($_GET['id']??0);$db=Database::connection();$stmt=$db->prepare('SELECT id,name,email,phone,role,status,created_at FROM users WHERE id=?');$stmt->execute([$id]);$user=$stmt->fetch();if(!$user){$_SESSION['flash']='Utilisateur introuvable.';$this->redirect('/admin/utilisateurs');}$stmt=$db->prepare('SELECT e.status,e.progress,e.enrolled_at,c.title FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? ORDER BY e.enrolled_at DESC');$stmt->execute([$id]);View::render('admin/user-edit',['title'=>'Gérer le compte','active'=>'admin-users','managedUser'=>$user,'enrollments'=>$stmt->fetchAll()],'admin');
    }
    public function updateUser(): void
    {
        $this->guard();$id=(int)($_POST['id']??0);$name=mb_substr(trim((string)($_POST['name']??'')),0,120);$email=strtolower(trim((string)($_POST['email']??'')));$phone=preg_replace('/[^0-9+]/','',trim((string)($_POST['phone']??'')))?:'';$role=in_array($_POST['role']??'',['learner','instructor','admin'],true)?$_POST['role']:'learner';$status=($_POST['status']??'')==='active'?'active':'disabled';$password=(string)($_POST['password']??'');$confirmation=(string)($_POST['password_confirmation']??'');
        if($id<1||$name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($phone)<8){$_SESSION['flash']='Nom, email ou téléphone invalide.';$this->redirect('/admin/utilisateurs/modifier?id='.$id);}$db=Database::connection();$check=$db->prepare('SELECT 1 FROM users WHERE id<>? AND (LOWER(email)=LOWER(?) OR phone=?) LIMIT 1');$check->execute([$id,$email,$phone]);if($check->fetchColumn()){$_SESSION['flash']='Cet email ou ce téléphone appartient déjà à un autre compte.';$this->redirect('/admin/utilisateurs/modifier?id='.$id);}
        if($password!==''&&(strlen($password)<8||$password!==$confirmation)){$_SESSION['flash']='Le nouveau mot de passe doit contenir au moins 8 caractères et être confirmé à l’identique.';$this->redirect('/admin/utilisateurs/modifier?id='.$id);}
        $db->beginTransaction();try{$sql='UPDATE users SET name=?,email=?,phone=?,role=?,status=?,otp_code='.($status==='active'?'NULL':'otp_code').',otp_expires_at='.($status==='active'?'NULL':'otp_expires_at').($password!==''?',password=?':'').' WHERE id=?';$values=[$name,$email,$phone,$role,$status];if($password!=='')$values[]=password_hash($password,PASSWORD_DEFAULT);$values[]=$id;$db->prepare($sql)->execute($values);if($status==='active')$db->prepare("UPDATE enrollments SET status='active' WHERE user_id=? AND status='pending'")->execute([$id]);$db->commit();$_SESSION['flash']=$password!==''?'Compte mis à jour et nouveau mot de passe défini.':'Compte mis à jour.';}catch(\Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='La mise à jour du compte a échoué.';}$this->redirect('/admin/utilisateurs/modifier?id='.$id);
    }
    public function enrollments(): void { $this->guard();$items=Database::connection()->query("SELECT e.enrolled_at,e.status,e.progress,u.name,u.email,u.phone,c.title course_title FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id ORDER BY e.enrolled_at DESC")->fetchAll();View::render('admin/enrollments',['title'=>'Inscriptions aux cours','active'=>'admin-enrollments','items'=>$items],'admin'); }
    public function orders(): void { $this->guard(); $items=Database::connection()->query("SELECT id,reference,JSON_UNQUOTE(JSON_EXTRACT(customer_data,'$.name')) customer,JSON_UNQUOTE(JSON_EXTRACT(customer_data,'$.email')) email,total,payment_method payment,status,created_at FROM orders ORDER BY id DESC")->fetchAll();View::render('admin/resources',['title'=>'Commandes','active'=>'admin-orders','type'=>'orders','items'=>$items],'admin'); }
    public function updateOrderStatus(): void { $this->guard();$id=(int)($_POST['id']??0);$status=in_array($_POST['status']??'',['pending','paid','processing','shipping','completed','cancelled'],true)?$_POST['status']:'pending';$db=Database::connection();$db->beginTransaction();try{$stmt=$db->prepare('SELECT status FROM orders WHERE id=? FOR UPDATE');$stmt->execute([$id]);$previous=$stmt->fetchColumn();if(!$previous)throw new \RuntimeException('Commande absente');$db->prepare('UPDATE orders SET status=?,payment_status=CASE WHEN ?="paid" THEN "paid" WHEN ?="cancelled" AND payment_status="paid" THEN "refunded" ELSE payment_status END WHERE id=?')->execute([$status,$status,$status,$id]);if($status==='cancelled'&&$previous!=='cancelled'){$items=$db->prepare("SELECT oi.item_id,oi.quantity FROM order_items oi JOIN products p ON p.id=oi.item_id WHERE oi.order_id=? AND oi.item_type='product' AND p.product_type='physical'");$items->execute([$id]);foreach($items as $item){$stock=$db->prepare('SELECT stock_quantity FROM products WHERE id=? FOR UPDATE');$stock->execute([(int)$item['item_id']]);$before=(int)$stock->fetchColumn();$after=$before+(int)$item['quantity'];$db->prepare("UPDATE products SET stock_quantity=?,stock_status=CASE WHEN stock_status='out_of_stock' THEN 'in_stock' ELSE stock_status END WHERE id=?")->execute([$after,(int)$item['item_id']]);$db->prepare("INSERT INTO stock_movements(product_id,order_id,movement_type,quantity,stock_before,stock_after,note) VALUES(?,?,'return',?,?,?,'Retour après annulation')")->execute([(int)$item['item_id'],$id,(int)$item['quantity'],$before,$after]);}}$db->prepare('INSERT INTO order_status_history(order_id,status,note) VALUES(?,?,?)')->execute([$id,$status,mb_substr(trim((string)($_POST['note']??'')),0,255)]);$db->commit();$_SESSION['flash']='Statut de commande mis à jour.';}catch(\Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Impossible de modifier cette commande.';}$this->redirect('/admin/commandes'); }
    public function documents(): void { $this->guard();$db=Database::connection();$stats=['orders'=>(int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),'certificates'=>(int)$db->query('SELECT COUNT(*) FROM certificates')->fetchColumn(),'learners'=>(int)$db->query("SELECT COUNT(*) FROM users WHERE role='learner'")->fetchColumn()];View::render('admin/documents',['title'=>'Documents et exports','active'=>'admin-documents','stats'=>$stats],'admin'); }
    public function news(): void
    {
        $this->guard();$db=Database::connection();$posts=$db->query("SELECT p.*,u.name author_name,(SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id) comment_count FROM posts p LEFT JOIN users u ON u.id=p.author_id ORDER BY p.created_at DESC")->fetchAll();View::render('admin/news',['title'=>'Actualités & blog','active'=>'admin-news','posts'=>$posts],'admin');
    }
    public function savePost(): void
    {
        $this->guard();$title=mb_substr(trim((string)($_POST['title']??'')),0,190);$content=HtmlSanitizer::clean((string)($_POST['content']??''));if($title===''||trim(strip_tags($content))===''){$_SESSION['flash']='Le titre et le contenu de l’article sont obligatoires.';$this->redirect('/admin/actualites');}
        $cover=trim((string)($_POST['cover_url']??''));if($cover!==''&&!filter_var($cover,FILTER_VALIDATE_URL))$cover='';
        if(!empty($_FILES['cover_image']['tmp_name'])&&is_uploaded_file($_FILES['cover_image']['tmp_name'])){$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($_FILES['cover_image']['tmp_name']);if(!isset($allowed[$mime])||(int)$_FILES['cover_image']['size']>8*1024*1024){$_SESSION['flash']='Image invalide : JPG, PNG ou WebP, 8 Mo maximum.';$this->redirect('/admin/actualites');}$dir=dirname(__DIR__,2).'/public/uploads/news';if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir)){$_SESSION['flash']='Impossible de créer le dossier des actualités.';$this->redirect('/admin/actualites');}$file='article-'.date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$allowed[$mime];if(!move_uploaded_file($_FILES['cover_image']['tmp_name'],$dir.'/'.$file)){$_SESSION['flash']='Impossible d’enregistrer l’image.';$this->redirect('/admin/actualites');}$cover='/public/uploads/news/'.$file;}
        $video=trim((string)($_POST['video_url']??''));if($video!==''&&!filter_var($video,FILTER_VALIDATE_URL))$video='';$status=($_POST['status']??'draft')==='published'?'published':'draft';$baseSlug=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title)?:$title),'-'));$slug=$baseSlug?:'article';$db=Database::connection();$candidate=$slug;$i=2;while(true){$check=$db->prepare('SELECT 1 FROM posts WHERE slug=?');$check->execute([$candidate]);if(!$check->fetchColumn())break;$candidate=$slug.'-'.$i++;}$stmt=$db->prepare('INSERT INTO posts(title,slug,excerpt,content,cover_image,video_url,status,author_id,published_at) VALUES(?,?,?,?,?,?,?,?,?)');$stmt->execute([$title,$candidate,mb_substr(trim((string)($_POST['excerpt']??'')),0,500),$content,$cover?:null,$video?:null,$status,$_SESSION['user']['id']??null,$status==='published'?date('Y-m-d H:i:s'):null]);$_SESSION['flash']=$status==='published'?'Article publié avec succès.':'Brouillon enregistré.';$this->redirect('/admin/actualites');
    }
    public function exportOrders(): void { $this->guard();$rows=Database::connection()->query("SELECT o.reference,o.status,o.payment_method,o.payment_status,o.total,o.created_at,JSON_UNQUOTE(JSON_EXTRACT(o.customer_data,'$.name')) client,JSON_UNQUOTE(JSON_EXTRACT(o.customer_data,'$.email')) email,JSON_UNQUOTE(JSON_EXTRACT(o.customer_data,'$.phone')) telephone FROM orders o ORDER BY o.id DESC")->fetchAll();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename=commandes-ifmap-'.date('Y-m-d').'.csv');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Référence','Client','Email','Téléphone','Statut','Paiement','État paiement','Total FCFA','Date'],';');foreach($rows as $row)fputcsv($out,[$row['reference'],$row['client'],$row['email'],$row['telephone'],$row['status'],$row['payment_method'],$row['payment_status'],$row['total'],$row['created_at']],';');fclose($out);exit; }
    public function exportEnrollments(): void { $this->guard();$rows=Database::connection()->query("SELECT u.name,u.email,u.phone,c.title,e.status,e.progress,e.enrolled_at,e.completed_at FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id ORDER BY e.enrolled_at DESC")->fetchAll();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename=inscriptions-ifmap-'.date('Y-m-d').'.csv');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Apprenant','Email','Téléphone','Formation','Statut','Progression %','Inscription','Fin'],';');foreach($rows as $row)fputcsv($out,array_values($row),';');fclose($out);exit; }
    public function orderDocument(): void { $this->renderOrderDocument('order'); }
    public function deliveryDocument(): void { $this->renderOrderDocument('delivery'); }
    private function renderOrderDocument(string $type): void { $this->guard();$id=(int)($_GET['id']??0);$db=Database::connection();$stmt=$db->prepare('SELECT * FROM orders WHERE id=?');$stmt->execute([$id]);$order=$stmt->fetch();if(!$order){http_response_code(404);return;}$stmt=$db->prepare('SELECT oi.*,p.product_type FROM order_items oi LEFT JOIN products p ON p.id=oi.item_id AND oi.item_type="product" WHERE oi.order_id=? ORDER BY oi.id');$stmt->execute([$id]);$items=$stmt->fetchAll();if($type==='delivery')$items=array_values(array_filter($items,fn($item)=>$item['item_type']==='product'&&($item['product_type']??'physical')==='physical'));View::render('admin/order-document',['title'=>$type==='delivery'?'Bon de livraison':'Bon de commande','order'=>$order,'items'=>$items,'documentType'=>$type,'brand'=>$this->documentBrand($db)],'document'); }
    private function documentBrand(\PDO $db): array { $brand=['name'=>'IFMAP','primary'=>'#123f3a','accent'=>'#d89b2b','logo'=>null];foreach($db->query("SELECT `key`,`value` FROM settings WHERE `group`='branding'") as $row)if(array_key_exists($row['key'],$brand))$brand[$row['key']]=$row['value'];return $brand; }
    private function savePrerequisites(\PDO $db, int $courseId): void
    {
        $db->prepare('DELETE FROM course_prerequisites WHERE course_id=?')->execute([$courseId]);

        $skillStmt = $db->prepare("INSERT INTO course_prerequisites(course_id,type,skill_name) VALUES(?,'skill',?)");
        foreach (array_filter(array_map('trim', $_POST['prerequisite_skills'] ?? [])) as $skill) {
            $skillStmt->execute([$courseId, mb_substr($skill, 0, 190)]);
        }

        $courseStmt = $db->prepare("INSERT INTO course_prerequisites(course_id,type,prerequisite_course_id) VALUES(?,'course',?)");
        foreach (array_unique(array_map('intval', $_POST['prerequisite_courses'] ?? [])) as $requiredId) {
            if ($requiredId > 0 && $requiredId !== $courseId) {
                $courseStmt->execute([$courseId, $requiredId]);
            }
        }
    }

    private function uploadThumbnail(?string $current, string $redirectPath): ?string
    {
        if (empty($_FILES['thumbnail']['tmp_name']) || !is_uploaded_file($_FILES['thumbnail']['tmp_name'])) {
            return $current;
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $file = $_FILES['thumbnail'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset($allowed[$mime]) || (int) $file['size'] > 5 * 1024 * 1024) {
            $_SESSION['flash'] = 'Vignette invalide. Utilisez JPG, PNG ou WebP, 5 Mo maximum.';
            $this->redirect($redirectPath);
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/courses';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $_SESSION['flash'] = 'Impossible de créer le dossier des vignettes.';
            $this->redirect($redirectPath);
        }

        $filename = 'thumbnail-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            $_SESSION['flash'] = 'Impossible d’enregistrer la vignette.';
            $this->redirect($redirectPath);
        }

        return '/public/uploads/courses/' . $filename;
    }

    private function uniqueProductSlug(\PDO $db,string $name,int $except=0): string
    {
        $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$name)?:$name;$base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$ascii),'-'))?:'produit';$slug=$base;$i=2;$stmt=$db->prepare('SELECT 1 FROM products WHERE slug=? AND id<>?');do{$stmt->execute([$slug,$except]);if(!$stmt->fetchColumn())return $slug;$slug=$base.'-'.$i++;}while(true);
    }

    private function uploadProductImages(array $current,string $redirectPath): array
    {
        $gallery=json_decode((string)($current['gallery']??'[]'),true)?:[];$primary=trim((string)($current['image']??''));if($primary!==''&&!in_array($primary,$gallery,true))array_unshift($gallery,$primary);$gallery=array_values(array_unique(array_filter($gallery,'is_string')));$delete=[];$removed=array_map('intval',(array)($_POST['remove_existing']??[]));$coverExisting=isset($_POST['cover_existing'])?(int)$_POST['cover_existing']:null;$replacements=$_FILES['replace_images']??[];$resolved=[];
        foreach($gallery as $index=>$path){if(in_array($index,$removed,true)){$delete[]=$path;unset($gallery[$index]);continue;}$tmp=$replacements['tmp_name'][$index]??'';if($tmp&&is_uploaded_file($tmp)){$new=$this->storeProductImage($tmp,(int)($replacements['size'][$index]??0),$redirectPath);$delete[]=$path;$gallery[$index]=$new;}$resolved[$index]=$gallery[$index];}
        $gallery=array_values($gallery);$newPaths=[];foreach((array)($_FILES['images']['tmp_name']??[]) as $index=>$tmp){if(!$tmp||!is_uploaded_file($tmp))continue;$newPaths[$index]=$this->storeProductImage($tmp,(int)($_FILES['images']['size'][$index]??0),$redirectPath);$gallery[]=$newPaths[$index];}
        if(isset($_POST['cover_new'])&&isset($newPaths[(int)$_POST['cover_new']]))$primary=$newPaths[(int)$_POST['cover_new']];elseif($coverExisting!==null&&isset($resolved[$coverExisting]))$primary=$resolved[$coverExisting];
        if(!in_array($primary,$gallery,true))$primary=$gallery[0]??null;return ['image'=>$primary?:null,'gallery'=>array_values(array_unique($gallery)),'delete'=>array_values(array_unique($delete))];
    }

    private function storeProductImage(string $tmp,int $size,string $redirectPath): string
    {
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp);if(!isset($allowed[$mime])||$size>6*1024*1024){$_SESSION['flash']='Image invalide : JPG, PNG ou WebP, 6 Mo maximum.';$this->redirect($redirectPath);}$directory=dirname(__DIR__,2).'/public/uploads/products';if(!is_dir($directory)&&!mkdir($directory,0775,true)&&!is_dir($directory)){$_SESSION['flash']='Impossible de créer le dossier produits.';$this->redirect($redirectPath);}$filename='product-'.date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$allowed[$mime];if(!move_uploaded_file($tmp,$directory.'/'.$filename)){$_SESSION['flash']='Impossible d’enregistrer une image.';$this->redirect($redirectPath);}return '/public/uploads/products/'.$filename;
    }
    private function deleteProductImageFile(string $path): void { if(!str_starts_with($path,'/public/uploads/products/'))return;$file=dirname(__DIR__,2).$path;if(is_file($file))@unlink($file); }

    private function uploadDigitalProduct(?string $current,string $redirectPath): ?string
    {
        if(empty($_FILES['digital_file']['tmp_name'])||!is_uploaded_file($_FILES['digital_file']['tmp_name']))return $current;$file=$_FILES['digital_file'];$allowed=['application/pdf'=>'pdf','application/epub+zip'=>'epub','application/zip'=>'zip','application/x-zip-compressed'=>'zip'];$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$extension=strtolower(pathinfo((string)$file['name'],PATHINFO_EXTENSION));if(!isset($allowed[$mime])||!in_array($extension,['pdf','epub','zip'],true)||(int)$file['size']>150*1024*1024){$_SESSION['flash']='Fichier invalide : PDF, EPUB ou ZIP, 150 Mo maximum.';$this->redirect($redirectPath);}$directory=dirname(__DIR__,2).'/storage/digital-products';if(!is_dir($directory)&&!mkdir($directory,0770,true)&&!is_dir($directory)){$_SESSION['flash']='Impossible de créer le stockage numérique.';$this->redirect($redirectPath);}$filename='digital-'.date('YmdHis').'-'.bin2hex(random_bytes(16)).'.'.$extension;if(!move_uploaded_file($file['tmp_name'],$directory.'/'.$filename)){$_SESSION['flash']='Impossible d’enregistrer le fichier numérique.';$this->redirect($redirectPath);}return 'storage/digital-products/'.$filename;
    }

    private function redirect(string $path): never { header('Location: '.$this->baseUrl($path)); exit; }
    public function branding(): void { $this->guard();$brand=['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b','logo'=>null];try{foreach(Database::connection()->query("SELECT `key`,`value` FROM settings WHERE `group`='branding'") as $row)if(array_key_exists($row['key'],$brand))$brand[$row['key']]=$row['value'];}catch(\Throwable){}$_SESSION['brand']=$brand;View::render('admin/branding', ['title'=>'Identité visuelle','active'=>'admin-branding','brand'=>$brand], 'admin'); }
    public function saveBranding(): void {
        $this->guard();
        $logo=$_SESSION['brand']['logo']??null;
        if(!empty($_FILES['logo']['tmp_name'])&&is_uploaded_file($_FILES['logo']['tmp_name'])){$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new \finfo(FILEINFO_MIME_TYPE))->file($_FILES['logo']['tmp_name']);if(!isset($allowed[$mime])||(int)$_FILES['logo']['size']>2*1024*1024){$_SESSION['flash']='Logo invalide : JPG, PNG ou WebP, 2 Mo maximum.';$this->redirect('/admin/branding');}$directory=dirname(__DIR__,2).'/public/uploads/branding';if(!is_dir($directory)&&!mkdir($directory,0775,true)&&!is_dir($directory)){$_SESSION['flash']='Impossible de créer le dossier du branding.';$this->redirect('/admin/branding');}$filename='logo-'.date('YmdHis').'-'.bin2hex(random_bytes(5)).'.'.$allowed[$mime];if(!move_uploaded_file($_FILES['logo']['tmp_name'],$directory.'/'.$filename)){$_SESSION['flash']='Impossible d’enregistrer le logo.';$this->redirect('/admin/branding');}$logo='/public/uploads/branding/'.$filename;}
        $_SESSION['brand'] = [
            'name'=>trim($_POST['name'] ?? 'IFMAP Learning'),
            'primary'=>preg_match('/^#[0-9a-f]{6}$/i', $_POST['primary'] ?? '') ? $_POST['primary'] : '#5547e8',
            'accent'=>preg_match('/^#[0-9a-f]{6}$/i', $_POST['accent'] ?? '') ? $_POST['accent'] : '#f59e0b',
            'logo'=>$logo,
        ];
        $stmt=Database::connection()->prepare("INSERT INTO settings(`key`,`value`,`group`) VALUES(?,?,'branding') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");foreach($_SESSION['brand'] as $key=>$value)$stmt->execute([$key,$value]);
        $_SESSION['flash'] = 'Identité visuelle mise à jour avec succès.';
        header('Location: ' . $this->baseUrl('/admin/branding')); exit;
    }
}
