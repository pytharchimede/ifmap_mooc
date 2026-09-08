<?php
namespace App\Services;

use App\Core\Database;

final class AuthNotificationService
{
    private SecureSettings $settings;
    public function __construct(){ $this->settings=new SecureSettings(); }
    public function otpMode(): string { $mode=$this->settings->get('otp_delivery_mode','display');return in_array($mode,['display','notify'],true)?$mode:'display'; }
    public function forcedOtpChannel(): string { $channel=$this->settings->get('otp_forced_channel','email');return in_array($channel,['email','sms','whatsapp'],true)?$channel:'email'; }
    public function resetChannels(): array { if($this->settings->get('password_reset_enabled','1')!=='1')return [];$raw=$this->settings->get('password_reset_channels','email,sms,whatsapp');return array_values(array_intersect(['email','sms','whatsapp'],array_filter(array_map('trim',explode(',',$raw))))); }
    public function channelConfigured(string $channel): bool
    {
        if($channel==='email'){try{return (new SmtpMailer())->configured();}catch(\Throwable){return false;}}
        if($channel==='sms')return $this->settings->get('sms_provider','twilio')==='twilio'&&$this->settings->get('twilio_account_sid')!==''&&$this->settings->get('twilio_auth_token')!==''&&$this->settings->get('twilio_sms_from')!=='';
        if($channel==='whatsapp'){$provider=$this->settings->get('whatsapp_provider','meta');if($provider==='twilio')return $this->settings->get('twilio_account_sid')!==''&&$this->settings->get('twilio_auth_token')!==''&&$this->settings->get('twilio_whatsapp_from')!=='';return $this->settings->get('meta_whatsapp_token')!==''&&$this->settings->get('meta_whatsapp_phone_number_id')!=='';}
        return false;
    }
    public function sendOtp(int $userId,string $email,string $phone,string $otp): array
    {
        if($this->otpMode()==='display'){$this->log($userId,'activation_otp','display',$phone,'local','displayed','OTP affiché par configuration administrateur.');return ['channel'=>'display','sent'=>true,'display'=>$otp];}
        $channel=$this->forcedOtpChannel();$message="Votre code d’activation IFMAP est : {$otp}. Il expire dans 15 minutes. Ne partagez jamais ce code.";$sent=$this->send($userId,'activation_otp',$channel,$email,$phone,$message,$otp);return ['channel'=>$channel,'sent'=>$sent,'display'=>null];
    }
    public function sendReset(int $userId,string $channel,string $email,string $phone,string $url): bool
    {
        if(!in_array($channel,$this->resetChannels(),true))return false;$message="Réinitialisation IFMAP : utilisez ce lien sécurisé pour définir un nouveau mot de passe : {$url}. Ce lien expire dans 30 minutes.";return $this->send($userId,'password_reset',$channel,$email,$phone,$message,null,$url);
    }
    public function test(string $channel,string $destination): bool { $email=$channel==='email'?$destination:'';$phone=$channel==='email'?'':$destination;return $this->send(null,'test',$channel,$email,$phone,'Test de notification IFMAP : le canal est opérationnel.'); }
    private function send(?int $userId,string $purpose,string $channel,string $email,string $phone,string $message,?string $otp=null,?string $url=null): bool
    {
        try{
            if($channel==='email'){
                if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Adresse email invalide.');$mailer=new SmtpMailer();if(!$mailer->configured())throw new \RuntimeException('SMTP non configuré.');$title=$purpose==='password_reset'?'Réinitialisation de votre mot de passe IFMAP':($purpose==='activation_otp'?'Code d’activation IFMAP':'Test notification IFMAP');$body=$purpose==='password_reset'?'<h2>Réinitialiser votre mot de passe</h2><p>Une demande de récupération de compte a été effectuée.</p><p><a href="'.htmlspecialchars((string)$url,ENT_QUOTES,'UTF-8').'" style="display:inline-block;padding:12px 18px;background:#143f3b;color:#fff;border-radius:10px;text-decoration:none">Définir un nouveau mot de passe</a></p><p>Ce lien expire dans 30 minutes. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.</p>':'<h2>'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'</h2><p>'.nl2br(htmlspecialchars($message,ENT_QUOTES,'UTF-8')).'</p>';$mailer->send($email,$title,$body);$this->log($userId,$purpose,'email',$email,'smtp','sent','OK');return true;
            }
            if($channel==='sms')return $this->sendTwilio($userId,$purpose,$phone,$message,false);
            if($channel==='whatsapp')return $this->settings->get('whatsapp_provider','meta')==='twilio'?$this->sendTwilio($userId,$purpose,$phone,$message,true):$this->sendMetaWhatsApp($userId,$purpose,$phone,$message,$otp,$url);
        }catch(\Throwable $e){$this->log($userId,$purpose,$channel,$channel==='email'?$email:$phone,'','failed',mb_substr($e->getMessage(),0,900));return false;}
        return false;
    }
    private function sendTwilio(?int $userId,string $purpose,string $phone,string $message,bool $whatsapp): bool
    {
        $sid=$this->settings->get('twilio_account_sid');$token=$this->settings->get('twilio_auth_token');$from=$whatsapp?$this->settings->get('twilio_whatsapp_from'):$this->settings->get('twilio_sms_from');if($sid===''||$token===''||$from===''||$phone==='')throw new \RuntimeException('Configuration Twilio incomplète.');$to=$whatsapp?'whatsapp:'.$this->e164($phone):$this->e164($phone);$fromValue=$whatsapp&&!str_starts_with($from,'whatsapp:')?'whatsapp:'.$from:$from;$ch=curl_init('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($sid).'/Messages.json');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_USERPWD=>$sid.':'.$token,CURLOPT_POSTFIELDS=>http_build_query(['To'=>$to,'From'=>$fromValue,'Body'=>$message]),CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true]);$body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);if($body===false||$status<200||$status>=300)throw new \RuntimeException('Twilio : '.($error?:mb_substr((string)$body,0,500)));$data=json_decode((string)$body,true)?:[];$this->log($userId,$purpose,$whatsapp?'whatsapp':'sms',$phone,'twilio','sent',(string)($data['sid']??'OK'));return true;
    }
    private function sendMetaWhatsApp(?int $userId,string $purpose,string $phone,string $message,?string $otp,?string $url): bool
    {
        $token=$this->settings->get('meta_whatsapp_token');$phoneId=$this->settings->get('meta_whatsapp_phone_number_id');$version=$this->settings->get('meta_whatsapp_api_version','v23.0');if($token===''||$phoneId===''||$phone==='')throw new \RuntimeException('Configuration WhatsApp Cloud API incomplète.');$template=$purpose==='activation_otp'?$this->settings->get('meta_whatsapp_otp_template'):($purpose==='password_reset'?$this->settings->get('meta_whatsapp_reset_template'):'');if($template!==''){$value=$purpose==='activation_otp'?(string)$otp:(string)$url;$payload=['messaging_product'=>'whatsapp','to'=>$this->digits($phone),'type'=>'template','template'=>['name'=>$template,'language'=>['code'=>'fr'],'components'=>[['type'=>'body','parameters'=>[['type'=>'text','text'=>$value]]]]]];}else{$payload=['messaging_product'=>'whatsapp','to'=>$this->digits($phone),'type'=>'text','text'=>['preview_url'=>false,'body'=>$message]];}$ch=curl_init('https://graph.facebook.com/'.rawurlencode($version).'/'.rawurlencode($phoneId).'/messages');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true]);$body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);if($body===false||$status<200||$status>=300)throw new \RuntimeException('WhatsApp Cloud API : '.($error?:mb_substr((string)$body,0,500)));$data=json_decode((string)$body,true)?:[];$id=(string)($data['messages'][0]['id']??'OK');$this->log($userId,$purpose,'whatsapp',$phone,'meta','sent',$id);return true;
    }
    private function log(?int $userId,string $purpose,string $channel,string $destination,string $provider,string $status,string $response): void { try{$st=Database::connection()->prepare('INSERT INTO auth_notification_logs(user_id,purpose,channel,destination,provider,status,provider_response) VALUES(?,?,?,?,?,?,?)');$st->execute([$userId,$purpose,$channel,$this->mask($destination),$provider,$status,mb_substr($response,0,1000)]);}catch(\Throwable){} }
    private function e164(string $phone): string { $digits=$this->digits($phone);if(str_starts_with($digits,'225'))return '+'.$digits;if(str_starts_with($digits,'0'))$digits=substr($digits,1);return '+225'.$digits; }
    private function digits(string $phone): string { return preg_replace('/\D+/','',$phone)?:''; }
    private function mask(string $value): string { if(str_contains($value,'@')){$parts=explode('@',$value,2);return substr($parts[0],0,2).'***@'.$parts[1];}$d=$this->digits($value);return strlen($d)>4?str_repeat('*',strlen($d)-4).substr($d,-4):$value; }
}
