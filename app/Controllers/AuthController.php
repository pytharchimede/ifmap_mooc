<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Core\Env;

final class AuthController
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['user'])) $this->redirect($this->intended('/academie'));
        View::render('auth/login',['title'=>'Connexion','error'=>$_SESSION['auth_error']??null,'flash'=>$_SESSION['auth_flash']??null],'customer-auth');
        unset($_SESSION['auth_error'],$_SESSION['auth_flash']);
    }
    public function showRegister(): void
    {
        if (!empty($_SESSION['user'])) $this->redirect($this->intended('/academie'));
        View::render('auth/register',['title'=>'Créer mon compte','error'=>$_SESSION['auth_error']??null,'courseId'=>(int)($_GET['course']??0)],'customer-auth'); unset($_SESSION['auth_error']);
    }
    public function register(): void
    {
        $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $phone=$this->normalizePhone($_POST['phone']??''); $password=(string)($_POST['password']??''); $role=($_POST['role']??'')==='instructor'?'instructor':'learner';$courseId=(int)($_POST['course_id']??0);
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($phone)<8||strlen($password)<6){$_SESSION['auth_error']='Renseignez un nom, un email valide, un téléphone et un mot de passe de 6 caractères minimum.';$this->redirect('/inscription'.($courseId?'?course='.$courseId:''));}
        if($this->findByIdentifier($email)||$this->findByIdentifier($phone)){$_SESSION['auth_error']='Un compte existe déjà avec cet email ou ce numéro.';$this->redirect('/inscription'.($courseId?'?course='.$courseId:''));}
        $hash=password_hash($password,PASSWORD_DEFAULT);
        $otp=(string)random_int(100000,999999);
        try{$db=Database::connection();$db->beginTransaction();$stmt=$db->prepare("INSERT INTO users (name,email,password,phone,role,status,otp_code,otp_expires_at) VALUES (?,?,?,?,?,'disabled',?,DATE_ADD(NOW(),INTERVAL 15 MINUTE))");$stmt->execute([$name,$email,$hash,$phone,$role,$otp]);$userId=(int)$db->lastInsertId();if($courseId>0){$stmt=$db->prepare("INSERT IGNORE INTO enrollments(user_id,course_id,status,source) SELECT ?,id,'pending','direct_registration' FROM courses WHERE id=? AND status='published'");$stmt->execute([$userId,$courseId]);}$db->commit();}catch(\Throwable){if(isset($db)&&$db->inTransaction())$db->rollBack();$_SESSION['auth_error']='La création du compte a échoué. Vérifiez que le téléphone n’est pas déjà utilisé.';$this->redirect('/inscription'.($courseId?'?course='.$courseId:''));}
        $this->dispatchOtp($email,$phone,$otp);$_SESSION['pending_activation']=$userId;$_SESSION['otp_demo']=$otp;$_SESSION['activation_flash']='Compte créé. Saisissez le code reçu ou demandez une activation à l’administrateur.';$this->redirect('/activation');
    }
    public function login(): void
    {
        $identifier=trim((string)($_POST['identifier']??$_POST['email']??''));$user=$this->findByIdentifier($identifier); $password=(string)($_POST['password']??'');
        if($user&&!empty($user['password'])&&password_verify($password,$user['password'])){
            if(($user['status']??'active')!=='active'){$_SESSION['pending_activation']=(int)$user['id'];$_SESSION['activation_flash']='Votre compte attend sa vérification. Entrez votre OTP ou contactez l’administrateur.';$this->redirect('/activation');}
            session_regenerate_id(true); $_SESSION['user']=$this->safe($user);
            if(($user['role']??'learner')==='admin'){$_SESSION['admin_authenticated']=true;$_SESSION['admin_name']=$user['name'];unset($_SESSION['intended_url']);$this->redirect('/admin');}
            $this->redirect($this->intended('/academie'));
        }
        $_SESSION['auth_error']='Email ou mot de passe incorrect.';$this->redirect('/connexion');
    }
    public function showActivation(): void
    {
        if(empty($_SESSION['pending_activation']))$this->redirect('/connexion');
        View::render('auth/activation',['title'=>'Activer mon compte','error'=>$_SESSION['auth_error']??null,'flash'=>$_SESSION['activation_flash']??null,'demoOtp'=>Env::get('APP_ENV','local')==='local'?($_SESSION['otp_demo']??null):null],'customer-auth');
        unset($_SESSION['auth_error'],$_SESSION['activation_flash']);
    }
    public function activate(): void
    {
        $id=(int)($_SESSION['pending_activation']??0);$otp=trim((string)($_POST['otp']??''));$stmt=Database::connection()->prepare("SELECT * FROM users WHERE id=? AND otp_code=? AND otp_expires_at>=NOW() LIMIT 1");$stmt->execute([$id,$otp]);$user=$stmt->fetch();if(!$user){$_SESSION['auth_error']='Code incorrect ou expiré.';$this->redirect('/activation');}
        Database::connection()->prepare("UPDATE users SET status='active',phone_verified_at=NOW(),otp_code=NULL,otp_expires_at=NULL WHERE id=?")->execute([$id]);Database::connection()->prepare("UPDATE enrollments SET status='active' WHERE user_id=? AND status='pending'")->execute([$id]);unset($_SESSION['pending_activation'],$_SESSION['otp_demo']);session_regenerate_id(true);$_SESSION['user']=$this->safe($user);$_SESSION['user']['status']='active';$this->redirect('/academie');
    }
    public function resendOtp(): void
    {
        $id=(int)($_SESSION['pending_activation']??0);if(!$id)$this->redirect('/connexion');$otp=(string)random_int(100000,999999);$db=Database::connection();$db->prepare("UPDATE users SET otp_code=?,otp_expires_at=DATE_ADD(NOW(),INTERVAL 15 MINUTE) WHERE id=?")->execute([$otp,$id]);$stmt=$db->prepare('SELECT email,phone FROM users WHERE id=?');$stmt->execute([$id]);$contact=$stmt->fetch()?:[];$this->dispatchOtp($contact['email']??'',$contact['phone']??'',$otp);$_SESSION['otp_demo']=$otp;$_SESSION['activation_flash']='Un nouveau code a été généré et envoyé.';$this->redirect('/activation');
    }
    public function logout(): void { unset($_SESSION['user']);session_regenerate_id(true);$_SESSION['auth_flash']='Vous êtes maintenant déconnecté.';$this->redirect('/connexion'); }
    private function findByIdentifier(string $identifier): ?array
    {
        $identifier=trim($identifier);$phone=$this->normalizePhone($identifier);try{$stmt=Database::connection()->prepare('SELECT id,name,email,phone,avatar,password,role,status FROM users WHERE LOWER(email)=LOWER(?) OR phone=? LIMIT 1');$stmt->execute([$identifier,$phone]);return $stmt->fetch()?:null;}catch(\Throwable){return null;}
    }
    private function normalizePhone(string $phone): string { return preg_replace('/[^0-9+]/','',trim($phone)) ?: ''; }
    private function dispatchOtp(string $email,string $phone,string $otp): void
    {
        $message="Votre code d’activation IFMAP est : $otp. Il expire dans 15 minutes.";
        if(filter_var($email,FILTER_VALIDATE_EMAIL))@mail($email,'Code d’activation IFMAP',$message,'From: '.Env::get('MAIL_FROM_ADDRESS','no-reply@ifmap.local'));
        if(Env::get('APP_ENV','local')==='local'){@file_put_contents(dirname(__DIR__,2).'/storage/logs/otp.log','['.date('c')."] $phone $otp\n",FILE_APPEND|LOCK_EX);}
    }
    private function intended(string $fallback): string { $path=$_SESSION['intended_url']??$fallback;unset($_SESSION['intended_url']);return str_starts_with($path,'/')?$path:$fallback; }
    private function safe(array $user): array { unset($user['password'],$user['otp_code'],$user['otp_expires_at']);return $user; }
    private function redirect(string $path): never { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');header('Location: '.$base.$path);exit; }
}
