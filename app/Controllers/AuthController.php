<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['user'])) $this->redirect($this->intended('/mon-compte'));
        View::render('auth/login',['title'=>'Connexion','error'=>$_SESSION['auth_error']??null,'flash'=>$_SESSION['auth_flash']??null],'customer-auth');
        unset($_SESSION['auth_error'],$_SESSION['auth_flash']);
    }
    public function showRegister(): void
    {
        if (!empty($_SESSION['user'])) $this->redirect($this->intended('/mon-compte'));
        View::render('auth/register',['title'=>'Créer mon compte','error'=>$_SESSION['auth_error']??null],'customer-auth'); unset($_SESSION['auth_error']);
    }
    public function register(): void
    {
        $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $phone=trim($_POST['phone']??''); $password=(string)($_POST['password']??'');
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<6){$_SESSION['auth_error']='Renseignez un nom, un email valide et un mot de passe de 6 caractères minimum.';$this->redirect('/inscription');}
        if($this->findByEmail($email)){$_SESSION['auth_error']='Un compte existe déjà avec cette adresse email.';$this->redirect('/inscription');}
        $hash=password_hash($password,PASSWORD_DEFAULT);
        try{$db=Database::connection();$stmt=$db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'learner')");$stmt->execute([$name,$email,$hash]);$user=['id'=>$db->lastInsertId(),'name'=>$name,'email'=>$email,'phone'=>$phone,'role'=>'learner'];}catch(\Throwable){$_SESSION['auth_error']='La base de données est momentanément indisponible.';$this->redirect('/inscription');}
        session_regenerate_id(true); $_SESSION['user']=$this->safe($user); $_SESSION['auth_flash']='Votre compte a été créé avec succès.'; $this->redirect($this->intended('/mon-compte'));
    }
    public function login(): void
    {
        $user=$this->findByEmail(strtolower(trim($_POST['email']??''))); $password=(string)($_POST['password']??'');
        if($user&&!empty($user['password'])&&password_verify($password,$user['password'])){
            session_regenerate_id(true); $_SESSION['user']=$this->safe($user);
            if(($user['role']??'learner')==='admin'){$_SESSION['admin_authenticated']=true;$_SESSION['admin_name']=$user['name'];unset($_SESSION['intended_url']);$this->redirect('/admin');}
            $this->redirect($this->intended('/mon-compte'));
        }
        $_SESSION['auth_error']='Email ou mot de passe incorrect.';$this->redirect('/connexion');
    }
    public function logout(): void { unset($_SESSION['user']);session_regenerate_id(true);$_SESSION['auth_flash']='Vous êtes maintenant déconnecté.';$this->redirect('/connexion'); }
    private function findByEmail(string $email): ?array
    {
        try{$stmt=Database::connection()->prepare('SELECT id,name,email,phone,avatar,password,role FROM users WHERE email=? LIMIT 1');$stmt->execute([$email]);return $stmt->fetch()?:null;}catch(\Throwable){return null;}
    }
    private function intended(string $fallback): string { $path=$_SESSION['intended_url']??$fallback;unset($_SESSION['intended_url']);return str_starts_with($path,'/')?$path:$fallback; }
    private function safe(array $user): array { unset($user['password']);return $user; }
    private function redirect(string $path): never { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');header('Location: '.$base.$path);exit; }
}
