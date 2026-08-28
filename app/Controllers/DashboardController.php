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
    private function courses(): array { return array_map(fn($r)=>['title'=>$r['title'],'category'=>$r['category'],'progress'=>(int)($r['progress']??0),'lessons'=>(int)$r['lessons'],'duration'=>$r['duration_label'],'color'=>$r['tone'],'icon'=>'book','teacher'=>$r['teacher']?:'Équipe IFMAP','next'=>'Continuer la formation'],Database::connection()->query("SELECT c.*,COALESCE(e.progress,0) progress,COUNT(l.id) lessons,u.name teacher FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id AND e.user_id=".(int)($_SESSION['user']['id']??0)." LEFT JOIN modules m ON m.course_id=c.id LEFT JOIN lessons l ON l.module_id=m.id LEFT JOIN users u ON u.id=c.instructor_id WHERE c.status='published' GROUP BY c.id,e.progress,u.name")->fetchAll()); }
    public function index(): void { $this->guard(); View::render('dashboard', ['title'=>'Tableau de bord','active'=>'dashboard','courses'=>$this->courses(),'user'=>$_SESSION['user']]); }
    public function catalog(): void { $this->guard(); View::render('catalog', ['title'=>'Catalogue des cours','active'=>'catalog','courses'=>$this->courses()]); }
    public function course(): void { $this->guard(); View::render('course', ['title'=>'Pilotage de la performance publique','active'=>'courses','modules'=>DemoData::modules()], 'course'); }
}
