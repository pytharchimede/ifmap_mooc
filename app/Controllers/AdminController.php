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
    public function courseForm(): void { $this->guard(); View::render('admin/course-form',['title'=>'Nouvelle formation','active'=>'admin-courses'],'admin'); }
    public function builder(): void { $this->guard();$id=(int)($_GET['course']??0);$db=Database::connection();$course=$db->query('SELECT * FROM courses WHERE id='.$id)->fetch();if(!$course)$this->redirect('/admin/cours');$modules=$db->query('SELECT * FROM modules WHERE course_id='.$id.' ORDER BY position,id')->fetchAll();foreach($modules as &$m){$m['lessons']=$db->query('SELECT * FROM lessons WHERE module_id='.(int)$m['id'].' ORDER BY position,id')->fetchAll();$m['assessments']=$db->query('SELECT * FROM assessments WHERE module_id='.(int)$m['id'])->fetchAll();}View::render('admin/builder',['title'=>'Programme du cours','active'=>'admin-courses','course'=>$course,'modules'=>$modules],'admin'); }
    public function builderAction(): void { $this->guard();$db=Database::connection();$courseId=(int)($_POST['course_id']??0);$action=$_POST['builder_action']??'';if($action==='module'){$s=$db->prepare('INSERT INTO modules(course_id,title,position) VALUES(?,?,?)');$s->execute([$courseId,trim($_POST['title']), (int)$_POST['position']]);}elseif($action==='lesson'){$s=$db->prepare('INSERT INTO lessons(module_id,title,type,content,duration,position) VALUES(?,?,?,?,?,?)');$s->execute([(int)$_POST['module_id'],trim($_POST['title']),$_POST['type'],trim($_POST['content']??''),(int)$_POST['duration'],(int)$_POST['position']]);}elseif($action==='assessment'){$moduleId=!empty($_POST['module_id'])?(int)$_POST['module_id']:null;$s=$db->prepare('INSERT INTO assessments(course_id,module_id,title,type,passing_score,attempts_allowed,status) VALUES(?,?,?,?,?,?,?)');$s->execute([$courseId,$moduleId,trim($_POST['title']),$_POST['type'],(int)$_POST['passing_score'],(int)$_POST['attempts_allowed'],'published']);}elseif($action==='question'){$options=array_values(array_filter(array_map('trim',[$_POST['option_a']??'',$_POST['option_b']??'',$_POST['option_c']??'',$_POST['option_d']??''])));$correct=(string)($_POST['correct_answer']??'');$s=$db->prepare('INSERT INTO questions(assessment_id,question,options,correct_answer,points) VALUES(?,?,?,?,1)');$s->execute([(int)$_POST['assessment_id'],trim($_POST['question']),json_encode($options,JSON_UNESCAPED_UNICODE),$correct]);}$_SESSION['flash']='Programme mis à jour.';$this->redirect('/admin/cours/programme?course='.$courseId); }
    public function saveCourse(): void
    {
        $this->guard(); $title=trim($_POST['title']??''); if($title===''){$_SESSION['flash']='Le titre est obligatoire.';$this->redirect('/admin/cours/nouveau');}
        $stmt=Database::connection()->prepare('INSERT INTO courses(title,slug,category,mode,duration_label,price,tone,status,description) VALUES(?,?,?,?,?,?,?,?,?)');$stmt->execute([$title,strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$title),'-')),trim($_POST['sector']??'Autre'),$_POST['mode']??'Présentiel',trim($_POST['duration']??'1 jour'),(int)($_POST['price']??0),'navy',$_POST['status']??'draft','']);
        $_SESSION['flash']='Formation créée. Ajoutez maintenant ses sections et leçons.'; $this->redirect('/admin/cours/programme?course='.Database::connection()->lastInsertId());
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
