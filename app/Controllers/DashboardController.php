<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Database;

final class DashboardController
{
    private function guard(): void
    {
        if (!empty($_SESSION['admin_authenticated']) && empty($_SESSION['user'])) {
            $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
            header('Location: ' . $base . '/admin'); exit;
        }
        if (!empty($_SESSION['user'])) return;
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/academie', PHP_URL_PATH) ?: '/academie';
        $_SESSION['intended_url'] = $base !== '' && str_starts_with($requested, $base) ? (substr($requested, strlen($base)) ?: '/academie') : $requested;
        header('Location: ' . $base . '/connexion'); exit;
    }
    private function courses(): array { return array_map(fn($r)=>['id'=>(int)$r['id'],'slug'=>$r['slug'],'title'=>$r['title'],'category'=>$r['category'],'thumbnail'=>$r['thumbnail'],'price'=>(float)$r['price'],'progress'=>(int)($r['progress']??0),'lessons'=>(int)$r['lessons'],'duration'=>$r['duration_label'],'color'=>$r['tone'],'icon'=>'book','teacher'=>$r['teacher']?:'Équipe IFMAP','enrolled'=>$r['enrollment_user']!==null,'next'=>'Continuer la formation'],Database::connection()->query("SELECT c.*,e.user_id enrollment_user,COALESCE(e.progress,0) progress,COUNT(l.id) lessons,u.name teacher FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id AND e.status IN ('active','completed') AND e.user_id=".(int)($_SESSION['user']['id']??0)." LEFT JOIN modules m ON m.course_id=c.id LEFT JOIN lessons l ON l.module_id=m.id LEFT JOIN users u ON u.id=c.instructor_id WHERE c.status='published' GROUP BY c.id,e.user_id,e.progress,u.name")->fetchAll()); }
    public function index(): void
    {
        $this->guard();
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id,name,email,phone,avatar,specialty,bio,cv_path,role FROM users WHERE id=?');
        $stmt->execute([(int) $_SESSION['user']['id']]);
        $user = $stmt->fetch() ?: $_SESSION['user'];
        $_SESSION['user'] = array_merge($_SESSION['user'], $user);
        $instructorCourses = [];
        if (($user['role'] ?? '') === 'instructor') {
            $stmt = $db->prepare("SELECT c.id,c.title,c.status,c.thumbnail,c.tone,COUNT(DISTINCT m.id) modules,COUNT(DISTINCT l.id) lessons,COUNT(DISTINCT e.user_id) learners FROM courses c LEFT JOIN modules m ON m.course_id=c.id LEFT JOIN lessons l ON l.module_id=m.id LEFT JOIN enrollments e ON e.course_id=c.id WHERE c.instructor_id=? GROUP BY c.id ORDER BY c.id DESC");
            $stmt->execute([(int) $user['id']]);
            $instructorCourses = $stmt->fetchAll();
        }
        $stmt=$db->prepare('SELECT COUNT(*) FROM certificates WHERE user_id=?');$stmt->execute([(int)$user['id']]);$certificateCount=(int)$stmt->fetchColumn();
        View::render('dashboard', ['title'=>'Tableau de bord','active'=>'dashboard','courses'=>$this->courses(),'user'=>$user,'instructorCourses'=>$instructorCourses,'certificateCount'=>$certificateCount,'flash'=>$_SESSION['profile_flash']??null,'error'=>$_SESSION['profile_error']??null]);
        unset($_SESSION['profile_flash'], $_SESSION['profile_error']);
    }
    public function catalog(): void { $this->guard(); View::render('catalog', ['title'=>'Catalogue des cours','active'=>'catalog','courses'=>$this->courses()]); }
    public function enroll(): void
    {
        $this->guard(); $id=(int)($_POST['course_id']??0); $db=Database::connection();
        $stmt=$db->prepare("SELECT id,slug,price FROM courses WHERE id=? AND status='published'");$stmt->execute([$id]);$course=$stmt->fetch();
        $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');
        if (!$course) { http_response_code(404);return; }
        if ((float)$course['price']>0) { $_SESSION['flash']='Cette formation nécessite un achat. Si elle est déjà payée, ouvrez Mes formations.';header('Location: '.$base.'/formations/'.rawurlencode($course['slug']));exit; }
        $db->prepare("INSERT INTO enrollments(user_id,course_id,status,source,payment_status) VALUES(?,?,'active','catalog','free') ON DUPLICATE KEY UPDATE status='active',payment_status='free'")->execute([$_SESSION['user']['id'],$id]);
        header('Location: '.$base.'/academie/formation?course='.$id);exit;
    }
    public function course(): void
    {
        $this->guard();
        if(($_SESSION['user']['role']??'')==='admin'){$base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');header('Location: '.$base.'/admin');exit;}
        $db=Database::connection();$userId=(int)$_SESSION['user']['id'];
        $stmt=$db->prepare("SELECT c.id,c.title,c.category,c.duration_label,c.thumbnail,c.tone,e.progress,e.status,e.enrolled_at,e.completed_at,e.payment_status,e.order_id,u.name teacher,COUNT(DISTINCT m.id) modules,COUNT(DISTINCT l.id) lessons,COUNT(DISTINCT lp.lesson_id) completed_lessons,(SELECT id FROM certificates ce WHERE ce.user_id=e.user_id AND ce.course_id=c.id ORDER BY ce.id DESC LIMIT 1) certificate_id FROM enrollments e JOIN courses c ON c.id=e.course_id LEFT JOIN users u ON u.id=c.instructor_id LEFT JOIN modules m ON m.course_id=c.id LEFT JOIN lessons l ON l.module_id=m.id LEFT JOIN lesson_progress lp ON lp.lesson_id=l.id AND lp.user_id=e.user_id WHERE e.user_id=? AND e.status IN ('active','completed') GROUP BY c.id,e.user_id,e.progress,e.status,e.enrolled_at,e.completed_at,e.payment_status,e.order_id,u.name ORDER BY e.enrolled_at DESC");
        $stmt->execute([$userId]);$courses=$stmt->fetchAll();foreach($courses as &$course){$course['purchase']=null;if($course['order_id']){$purchase=$db->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');$purchase->execute([$course['order_id'],$userId]);$course['purchase']=$purchase->fetch()?:null;if($course['purchase'])$course['purchase']['payments']=\App\Services\CommerceDetails::payments($db,(int)$course['order_id']);}}unset($course);$_SESSION['my_course_count']=count($courses);
        View::render('course',['title'=>'Mes formations','active'=>'courses','courses'=>$courses]);
    }
}
