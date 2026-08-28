<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Env;
use App\Core\Store;
use App\Core\Database;
use App\Support\DemoData;

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
                $stmt = Database::connection()->prepare("SELECT id,name,email,password,role FROM users WHERE email=? AND role='admin' LIMIT 1");
                $stmt->execute([$identifier]);
                $admin = $stmt->fetch() ?: null;
                $valid = $admin && password_verify($password, $admin['password']);
            } catch (\Throwable) { $valid = false; }
        }
        if ($valid) {
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
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_name']);
        header('Location: ' . $this->baseUrl('/admin/connexion')); exit;
    }

    public function index(): void { $this->guard(); $courses=Database::connection()->query("SELECT c.id,c.title,c.category,COALESCE(ROUND(AVG(e.progress)),0) progress,COUNT(e.user_id) enrolled,c.tone color FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id GROUP BY c.id ORDER BY enrolled DESC LIMIT 5")->fetchAll(); View::render('admin/dashboard', ['title'=>'Vue d’ensemble','active'=>'admin-dashboard','courses'=>$courses], 'admin'); }
    public function courses(): void { $this->guard(); $courses=Database::connection()->query("SELECT id,title,category sector,mode,duration_label duration,price,tone,status FROM courses ORDER BY id DESC")->fetchAll(); View::render('admin/courses', ['title'=>'Gestion des cours','active'=>'admin-courses','courses'=>$courses], 'admin'); }
    public function courseForm(): void { $this->guard(); $availableCourses=Database::connection()->query("SELECT id,title FROM courses WHERE status IN ('published','draft') ORDER BY title")->fetchAll(); View::render('admin/course-form',['title'=>'Nouvelle formation','active'=>'admin-courses','availableCourses'=>$availableCourses],'admin'); }
    public function editCourse(): void { $this->guard();$id=(int)($_GET['id']??0);$db=Database::connection();$course=$db->query('SELECT * FROM courses WHERE id='.$id)->fetch();if(!$course)$this->redirect('/admin/cours');$availableCourses=$db->query("SELECT id,title FROM courses WHERE id<>$id ORDER BY title")->fetchAll();$skills=$db->query("SELECT skill_name FROM course_prerequisites WHERE course_id=$id AND type='skill' ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);$requiredCourses=$db->query("SELECT prerequisite_course_id FROM course_prerequisites WHERE course_id=$id AND type='course'")->fetchAll(\PDO::FETCH_COLUMN);View::render('admin/course-edit',['title'=>'Modifier la formation','active'=>'admin-courses','course'=>$course,'availableCourses'=>$availableCourses,'skills'=>$skills,'requiredCourses'=>$requiredCourses],'admin'); }
    public function updateCourse(): void
    {
        $this->guard();$id=(int)($_POST['course_id']??0);$title=mb_substr(trim($_POST['title']??''),0,190);if($id<1||$title===''){$_SESSION['flash']='Titre invalide.';$this->redirect('/admin/cours');}$db=Database::connection();$db->beginTransaction();try{$stmt=$db->prepare('UPDATE courses SET title=?,category=?,mode=?,duration_label=?,price=?,status=?,description=? WHERE id=?');$stmt->execute([$title,trim($_POST['sector']??'Autre'),$_POST['mode']??'Présentiel',trim($_POST['duration']??'1 jour'),max(0,(int)($_POST['price']??0)),in_array($_POST['status']??'', ['draft','published','archived'],true)?$_POST['status']:'draft',trim($_POST['description']??''),$id]);$db->prepare('DELETE FROM course_prerequisites WHERE course_id=?')->execute([$id]);$skillStmt=$db->prepare("INSERT INTO course_prerequisites(course_id,type,skill_name) VALUES(?,'skill',?)");foreach(array_filter(array_map('trim',$_POST['prerequisite_skills']??[])) as $skill)$skillStmt->execute([$id,mb_substr($skill,0,190)]);$courseStmt=$db->prepare("INSERT INTO course_prerequisites(course_id,type,prerequisite_course_id) VALUES(?,'course',?)");foreach(array_unique(array_map('intval',$_POST['prerequisite_courses']??[])) as $requiredId)if($requiredId>0&&$requiredId!==$id)$courseStmt->execute([$id,$requiredId]);$db->commit();$_SESSION['flash']='Formation et prérequis mis à jour.';}catch(\Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='La mise à jour a échoué.';}$this->redirect('/admin/cours/modifier?id='.$id);
    }
    public function builder(): void { $this->guard();$id=(int)($_GET['course']??0);$db=Database::connection();$course=$db->query('SELECT * FROM courses WHERE id='.$id)->fetch();if(!$course)$this->redirect('/admin/cours');$modules=$db->query('SELECT * FROM modules WHERE course_id='.$id.' ORDER BY position,id')->fetchAll();foreach($modules as &$m){$m['lessons']=$db->query('SELECT * FROM lessons WHERE module_id='.(int)$m['id'].' ORDER BY position,id')->fetchAll();$m['assessments']=$db->query('SELECT * FROM assessments WHERE module_id='.(int)$m['id'])->fetchAll();}View::render('admin/builder',['title'=>'Programme du cours','active'=>'admin-courses','course'=>$course,'modules'=>$modules],'admin'); }
    public function builderAction(): void
    {
        $this->guard(); $db=Database::connection(); $courseId=(int)($_POST['course_id']??0); $action=$_POST['builder_action']??'';
        if($action==='module'){$title=mb_substr(trim($_POST['title']??''),0,190);if($title===''){$_SESSION['flash']='Le titre de la section est obligatoire.';$this->redirect('/admin/cours/programme?course='.$courseId);}$s=$db->prepare('INSERT INTO modules(course_id,title,position) VALUES(?,?,?)');$s->execute([$courseId,$title,(int)$_POST['position']]);}
        elseif($action==='lesson'){
            $type=in_array($_POST['type']??'', ['video','text','file'],true)?$_POST['type']:'text'; $content=trim($_POST['content']??'');
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
            $title=mb_substr(trim($_POST['title']??''),0,190);if($title===''){$_SESSION['flash']='Le titre de la leçon est obligatoire.';$this->redirect('/admin/cours/programme?course='.$courseId);}$s=$db->prepare('INSERT INTO lessons(module_id,title,type,content,duration,position) VALUES(?,?,?,?,?,?)');$s->execute([(int)$_POST['module_id'],$title,$type,$content,max(0,(int)$_POST['duration']),(int)$_POST['position']]);
        }
        elseif($action==='assessment'){$moduleId=!empty($_POST['module_id'])?(int)$_POST['module_id']:null;$title=mb_substr(trim($_POST['title']??''),0,190);if($title===''){$_SESSION['flash']='Le titre de la composition est obligatoire.';$this->redirect('/admin/cours/programme?course='.$courseId);}$score=min(100,max(1,(int)($_POST['passing_score']??70)));$attempts=min(20,max(1,(int)($_POST['attempts_allowed']??3)));$type=($_POST['type']??'')==='final_exam'?'final_exam':'section_quiz';$s=$db->prepare('INSERT INTO assessments(course_id,module_id,title,type,passing_score,attempts_allowed,status) VALUES(?,?,?,?,?,?,?)');$s->execute([$courseId,$moduleId,$title,$type,$score,$attempts,'published']);}
        elseif($action==='question'){$options=array_values(array_filter(array_map('trim',[$_POST['option_a']??'',$_POST['option_b']??'',$_POST['option_c']??'',$_POST['option_d']??''])));$correct=(string)($_POST['correct_answer']??'');$s=$db->prepare('INSERT INTO questions(assessment_id,question,options,correct_answer,points,explanation) VALUES(?,?,?,?,1,?)');$s->execute([(int)$_POST['assessment_id'],trim($_POST['question']),json_encode($options,JSON_UNESCAPED_UNICODE),$correct,trim($_POST['explanation']??'')]);}
        $_SESSION['flash']='Programme mis à jour.';$this->redirect('/admin/cours/programme?course='.$courseId);
    }
    public function saveCourse(): void
    {
        $this->guard(); $title=mb_substr(trim($_POST['title']??''),0,190); if($title===''){$_SESSION['flash']='Le titre est obligatoire.';$this->redirect('/admin/cours/nouveau');}
        $db=Database::connection();$db->beginTransaction();try{$stmt=$db->prepare('INSERT INTO courses(title,slug,category,mode,duration_label,price,tone,status,description) VALUES(?,?,?,?,?,?,?,?,?)');$stmt->execute([$title,strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-')),trim($_POST['sector']??'Autre'),$_POST['mode']??'Présentiel',trim($_POST['duration']??'1 jour'),(int)($_POST['price']??0),'navy',$_POST['status']??'draft',trim($_POST['description']??'')]);$courseId=(int)$db->lastInsertId();$skillStmt=$db->prepare("INSERT INTO course_prerequisites(course_id,type,skill_name) VALUES(?,'skill',?)");foreach(array_filter(array_map('trim',$_POST['prerequisite_skills']??[])) as $skill)$skillStmt->execute([$courseId,mb_substr($skill,0,190)]);$courseStmt=$db->prepare("INSERT INTO course_prerequisites(course_id,type,prerequisite_course_id) VALUES(?,'course',?)");foreach(array_unique(array_map('intval',$_POST['prerequisite_courses']??[])) as $requiredId)if($requiredId>0&&$requiredId!==$courseId)$courseStmt->execute([$courseId,$requiredId]);$db->commit();}catch(\Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='La formation n’a pas pu être créée.';$this->redirect('/admin/cours/nouveau');}
        $_SESSION['flash']='Formation créée avec ses prérequis. Ajoutez maintenant ses sections et leçons.'; $this->redirect('/admin/cours/programme?course='.$courseId);
    }
    public function deleteCourse(): void { $this->guard(); $stmt=Database::connection()->prepare('DELETE FROM courses WHERE id=?');$stmt->execute([(int)($_POST['id']??0)]);$_SESSION['flash']='Formation supprimée.';$this->redirect('/admin/cours'); }
    public function products(): void { $this->guard(); $items=Database::connection()->query("SELECT id,name title,CASE stock_status WHEN 'in_stock' THEN 'En stock' WHEN 'on_order' THEN 'Sur commande' ELSE 'Rupture' END stock,price FROM products ORDER BY id DESC")->fetchAll();View::render('admin/resources',['title'=>'Produits','active'=>'admin-products','type'=>'products','items'=>$items],'admin'); }
    public function users(): void { $this->guard(); $items=Database::connection()->query('SELECT id,name,email,role,status FROM users ORDER BY id DESC')->fetchAll();View::render('admin/resources',['title'=>'Utilisateurs','active'=>'admin-users','type'=>'users','items'=>$items],'admin'); }
    public function orders(): void { $this->guard(); $items=Database::connection()->query("SELECT reference,JSON_UNQUOTE(JSON_EXTRACT(customer_data,'$.name')) customer,JSON_UNQUOTE(JSON_EXTRACT(customer_data,'$.email')) email,total,payment_method payment,status,created_at FROM orders ORDER BY id DESC")->fetchAll();View::render('admin/resources',['title'=>'Commandes','active'=>'admin-orders','type'=>'orders','items'=>$items],'admin'); }
    private function redirect(string $path): never { header('Location: '.$this->baseUrl($path)); exit; }
    public function branding(): void { $this->guard(); View::render('admin/branding', ['title'=>'Identité visuelle','active'=>'admin-branding','brand'=>$_SESSION['brand'] ?? ['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b']], 'admin'); }
    public function saveBranding(): void {
        $this->guard();
        $_SESSION['brand'] = [
            'name'=>trim($_POST['name'] ?? 'IFMAP Learning'),
            'primary'=>preg_match('/^#[0-9a-f]{6}$/i', $_POST['primary'] ?? '') ? $_POST['primary'] : '#5547e8',
            'accent'=>preg_match('/^#[0-9a-f]{6}$/i', $_POST['accent'] ?? '') ? $_POST['accent'] : '#f59e0b',
        ];
        $stmt=Database::connection()->prepare("INSERT INTO settings(`key`,`value`,`group`) VALUES(?,?,'branding') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");foreach($_SESSION['brand'] as $key=>$value)$stmt->execute([$key,$value]);
        $_SESSION['flash'] = 'Identité visuelle mise à jour avec succès.';
        header('Location: ' . $this->baseUrl('/admin/branding')); exit;
    }
}
