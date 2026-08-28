<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\Env;
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
        if (hash_equals((string)Env::get('ADMIN_USERNAME', 'admin'), (string)($_POST['username'] ?? '')) && hash_equals((string)Env::get('ADMIN_PASSWORD', 'admin'), (string)($_POST['password'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_name'] = 'Administrateur IFMAP';
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

    public function index(): void { $this->guard(); View::render('admin/dashboard', ['title'=>'Vue d’ensemble','active'=>'admin-dashboard','courses'=>DemoData::courses()], 'admin'); }
    public function courses(): void { $this->guard(); View::render('admin/courses', ['title'=>'Gestion des cours','active'=>'admin-courses','courses'=>DemoData::courses()], 'admin'); }
    public function branding(): void { $this->guard(); View::render('admin/branding', ['title'=>'Identité visuelle','active'=>'admin-branding','brand'=>$_SESSION['brand'] ?? ['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b']], 'admin'); }
    public function saveBranding(): void {
        $this->guard();
        $_SESSION['brand'] = [
            'name'=>trim($_POST['name'] ?? 'IFMAP Learning'),
            'primary'=>preg_match('/^#[0-9a-f]{6}$/i', $_POST['primary'] ?? '') ? $_POST['primary'] : '#5547e8',
            'accent'=>preg_match('/^#[0-9a-f]{6}$/i', $_POST['accent'] ?? '') ? $_POST['accent'] : '#f59e0b',
        ];
        $_SESSION['flash'] = 'Identité visuelle mise à jour avec succès.';
        header('Location: ' . $this->baseUrl('/admin/branding')); exit;
    }
}
