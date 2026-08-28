<?php
namespace App\Controllers;

use App\Core\View;
use App\Support\DemoData;
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
    private function courses(): array { return array_map(fn($r)=>['id'=>(int)$r['id'],'title'=>$r['title'],'category'=>$r['category'],'progress'=>(int)($r['progress']??0),'lessons'=>(int)$r['lessons'],'duration'=>$r['duration_label'],'color'=>$r['tone'],'icon'=>'book','teacher'=>$r['teacher']?:'Équipe IFMAP','enrolled'=>$r['enrollment_user']!==null,'next'=>'Continuer la formation'],Database::connection()->query("SELECT c.*,e.user_id enrollment_user,COALESCE(e.progress,0) progress,COUNT(l.id) lessons,u.name teacher FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id AND e.user_id=".(int)($_SESSION['user']['id']??0)." LEFT JOIN modules m ON m.course_id=c.id LEFT JOIN lessons l ON l.module_id=m.id LEFT JOIN users u ON u.id=c.instructor_id WHERE c.status='published' GROUP BY c.id,e.user_id,e.progress,u.name")->fetchAll()); }
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
        View::render('dashboard', ['title'=>'Tableau de bord','active'=>'dashboard','courses'=>$this->courses(),'user'=>$user,'instructorCourses'=>$instructorCourses,'flash'=>$_SESSION['profile_flash']??null,'error'=>$_SESSION['profile_error']??null]);
        unset($_SESSION['profile_flash'], $_SESSION['profile_error']);
    }
    public function catalog(): void { $this->guard(); View::render('catalog', ['title'=>'Catalogue des cours','active'=>'catalog','courses'=>$this->courses()]); }
    public function enroll(): void { $this->guard();$courseId=(int)($_POST['course_id']??0);$stmt=Database::connection()->prepare("INSERT IGNORE INTO enrollments(user_id,course_id,status,source) SELECT ?,id,'active','catalog' FROM courses WHERE id=? AND status='published'");$stmt->execute([(int)$_SESSION['user']['id'],$courseId]);$_SESSION['flash']='Inscription au cours confirmée.';$base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');header('Location: '.$base.'/academie/formation?course='.$courseId);exit; }
    public function course(): void { $this->guard(); View::render('course', ['title'=>'Pilotage de la performance publique','active'=>'courses','modules'=>DemoData::modules()], 'course'); }
}
