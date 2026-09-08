<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Core\Env;
use App\Services\AuthNotificationService;
use App\Services\SecureSettings;

final class AuthCommunicationController
{
    private function base(string $path=''): string { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');return $base.$path; }
    private function redirect(string $path): never { header('Location: '.$this->base($path));exit; }
    private function admin(): void { if(empty($_SESSION['admin_authenticated']))$this->redirect('/admin/connexion'); }

    public function forgot(): void
    {
        if(!empty($_SESSION['user']))$this->redirect('/academie');
        $service=new AuthNotificationService();
        View::render('auth/forgot-password',[
            'title'=>'Mot de passe oublié',
            'channels'=>$service->resetChannels(),
            'configured'=>array_combine(['email','sms','whatsapp'],array_map(fn($c)=>$service->channelConfigured($c),['email','sms','whatsapp'])),
            'flash'=>$_SESSION['reset_flash']??null,'error'=>$_SESSION['reset_error']??null,
            'old'=>$_SESSION['reset_old']??[]
        ],'customer-auth');
        unset($_SESSION['reset_flash'],$_SESSION['reset_error'],$_SESSION['reset_old']);
    }

    public function requestReset(): void
    {
        $identifier=trim((string)($_POST['identifier']??''));$channel=(string)($_POST['channel']??'email');$_SESSION['reset_old']=['identifier'=>$identifier,'channel'=>$channel];
        $service=new AuthNotificationService();
        if(!in_array($channel,$service->resetChannels(),true)){$_SESSION['reset_error']='Ce canal de récupération n’est pas autorisé.';$this->redirect('/mot-de-passe-oublie');}
        if(!$service->channelConfigured($channel)){$_SESSION['reset_error']='Ce canal n’est pas encore configuré par IFMAP. Choisissez un autre moyen de récupération.';$this->redirect('/mot-de-passe-oublie');}
        $user=$this->findUser($identifier);
        if($user){
            $destination=$channel==='email'?(string)$user['email']:(string)$user['phone'];
            if(($channel==='email'&&!filter_var($destination,FILTER_VALIDATE_EMAIL))||($channel!=='email'&&strlen(preg_replace('/\D/','',$destination))<8)){
                $_SESSION['reset_error']='Ce compte ne possède pas de coordonnées compatibles avec le canal choisi.';$this->redirect('/mot-de-passe-oublie');
            }
            $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);$db=Database::connection();
            $db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$user['id']]);
            $db->prepare("INSERT INTO password_reset_tokens(user_id,token_hash,channel,destination,expires_at) VALUES(?,?,?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))")->execute([(int)$user['id'],$hash,$channel,$destination]);
            $url=$this->publicUrl('/reinitialiser-mot-de-passe?token='.rawurlencode($token));
            if(!$service->sendReset((int)$user['id'],$channel,(string)$user['email'],(string)$user['phone'],$url)){
                $_SESSION['reset_error']='La notification n’a pas pu être envoyée. Réessayez ou choisissez un autre canal.';$this->redirect('/mot-de-passe-oublie');
            }
        }
        unset($_SESSION['reset_old']);
        $_SESSION['reset_flash']='Si un compte correspond à ces informations, un lien sécurisé de réinitialisation a été envoyé. Il expire dans 30 minutes.';
        $this->redirect('/mot-de-passe-oublie');
    }

    public function reset(): void
    {
        $token=(string)($_GET['token']??'');$row=$this->validToken($token);
        View::render('auth/reset-password',['title'=>'Nouveau mot de passe','token'=>$token,'valid'=>(bool)$row,'error'=>$_SESSION['reset_error']??null,'flash'=>$_SESSION['reset_flash']??null],'customer-auth');
        unset($_SESSION['reset_error'],$_SESSION['reset_flash']);
    }

    public function saveReset(): void
    {
        $token=(string)($_POST['token']??'');$row=$this->validToken($token);$password=(string)($_POST['password']??'');$confirm=(string)($_POST['password_confirmation']??'');
        if(!$row){$_SESSION['reset_error']='Ce lien est invalide, expiré ou déjà utilisé.';$this->redirect('/reinitialiser-mot-de-passe?token='.rawurlencode($token));}
        if(strlen($password)<8||$password!==$confirm){$_SESSION['reset_error']='Le mot de passe doit contenir au moins 8 caractères et les deux saisies doivent être identiques.';$this->redirect('/reinitialiser-mot-de-passe?token='.rawurlencode($token));}
        $db=Database::connection();$db->beginTransaction();
        try{
            $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),(int)$row['user_id']]);
            $db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([(int)$row['user_id']]);
            $db->commit();
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['reset_error']='La réinitialisation a échoué. Réessayez.';$this->redirect('/reinitialiser-mot-de-passe?token='.rawurlencode($token));}
        $_SESSION['auth_flash']='Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.';$this->redirect('/connexion');
    }

    public function settings(): void
    {
        $this->admin();$s=new SecureSettings();$g=$s->group('auth_notifications');$service=new AuthNotificationService();
        View::render('admin/auth-notifications',[
            'title'=>'OTP, SMS & WhatsApp','active'=>'admin-auth-notifications','settings'=>$g,
            'configured'=>['email'=>$service->channelConfigured('email'),'sms'=>$service->channelConfigured('sms'),'whatsapp'=>$service->channelConfigured('whatsapp')],
            'flash'=>$_SESSION['flash']??null
        ],'admin');unset($_SESSION['flash']);
    }

    public function saveSettings(): void
    {
        $this->admin();$s=new SecureSettings();$mode=in_array($_POST['otp_delivery_mode']??'display',['display','notify'],true)?$_POST['otp_delivery_mode']:'display';$channel=in_array($_POST['otp_forced_channel']??'email',['email','sms','whatsapp'],true)?$_POST['otp_forced_channel']:'email';
        $s->set('otp_delivery_mode',$mode,'auth_notifications');$s->set('otp_forced_channel',$channel,'auth_notifications');$s->set('otp_allow_resend',!empty($_POST['otp_allow_resend'])?'1':'0','auth_notifications');$s->set('password_reset_enabled',!empty($_POST['password_reset_enabled'])?'1':'0','auth_notifications');
        $channels=[];foreach(['email','sms','whatsapp'] as $c)if(!empty($_POST['reset_channel_'.$c]))$channels[]=$c;$s->set('password_reset_channels',implode(',',$channels?:['email']),'auth_notifications');
        foreach(['sms_provider','sms_sender','twilio_sms_from','twilio_whatsapp_from','whatsapp_provider','meta_whatsapp_phone_number_id','meta_whatsapp_api_version','meta_whatsapp_otp_template','meta_whatsapp_reset_template'] as $key)$s->set($key,trim((string)($_POST[$key]??'')),'auth_notifications');
        foreach(['twilio_account_sid','twilio_auth_token','meta_whatsapp_token'] as $key)if(trim((string)($_POST[$key]??''))!=='')$s->set($key,trim((string)$_POST[$key]),'auth_notifications');
        $_SESSION['flash']='Paramètres OTP et notifications enregistrés.';$this->redirect('/admin/auth-notifications');
    }

    public function testChannel(): void
    {
        $this->admin();$channel=(string)($_POST['channel']??'');$destination=trim((string)($_POST['destination']??''));
        try{$ok=(new AuthNotificationService())->test($channel,$destination);$_SESSION['flash']=$ok?'Notification de test envoyée avec succès.':'Échec du test. Vérifiez les identifiants API et la destination.';}catch(\Throwable $e){$_SESSION['flash']='Échec du test : '.$e->getMessage();}
        $this->redirect('/admin/auth-notifications');
    }

    private function validToken(string $token): ?array
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token))return null;$st=Database::connection()->prepare('SELECT * FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>=NOW() LIMIT 1');$st->execute([hash('sha256',$token)]);return $st->fetch()?:null;
    }
    private function findUser(string $identifier): ?array
    {
        $phone=preg_replace('/[^0-9+]/','',$identifier);$st=Database::connection()->prepare('SELECT id,name,email,phone,role,status FROM users WHERE LOWER(email)=LOWER(?) OR phone=? LIMIT 1');$st->execute([$identifier,$phone]);return $st->fetch()?:null;
    }
    private function publicUrl(string $path): string
    {
        $base=rtrim((string)Env::get('APP_URL',''),'/');if($base!=='')return $base.$path;$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';return $scheme.'://'.($_SERVER['HTTP_HOST']??'localhost').$this->base($path);
    }
}
