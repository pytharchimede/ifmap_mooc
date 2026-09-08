<?php
namespace App\Services;

final class SmtpMailer
{
    public function configured(): bool
    {
        $s=(new SecureSettings())->group('mail');return trim($s['smtp_host']??'')!==''&&trim($s['smtp_from_address']??'')!=='';
    }
    public function send(string $to,string $subject,string $html): void
    {
        $s=(new SecureSettings())->group('mail');$host=trim($s['smtp_host']??'');$port=(int)($s['smtp_port']??587);$enc=$s['smtp_encryption']??'tls';$user=$s['smtp_username']??'';$pass=$s['smtp_password']??'';$from=$s['smtp_from_address']??'';$fromName=$s['smtp_from_name']??'IFMAP';if($host===''||$from==='')throw new \RuntimeException('Configuration SMTP incomplète.');
        $remote=($enc==='ssl'?'ssl://':'').$host;$fp=stream_socket_client($remote.':'.$port,$errno,$errstr,15,STREAM_CLIENT_CONNECT);if(!$fp)throw new \RuntimeException('Connexion SMTP impossible.');stream_set_timeout($fp,15);$this->expect($fp,[220]);$this->cmd($fp,'EHLO ifmap.ci',[250]);if($enc==='tls'){$this->cmd($fp,'STARTTLS',[220]);if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new \RuntimeException('TLS SMTP impossible.');$this->cmd($fp,'EHLO ifmap.ci',[250]);}
        if($user!==''){$this->cmd($fp,'AUTH LOGIN',[334]);$this->cmd($fp,base64_encode($user),[334]);$this->cmd($fp,base64_encode($pass),[235]);}
        $this->cmd($fp,'MAIL FROM:<'.$from.'>',[250]);$this->cmd($fp,'RCPT TO:<'.$to.'>',[250,251]);$this->cmd($fp,'DATA',[354]);$boundary='b'.bin2hex(random_bytes(8));$headers=['From: '.$fromName.' <'.$from.'>','To: <'.$to.'>','Subject: =?UTF-8?B?'.base64_encode($subject).'?=','MIME-Version: 1.0','Content-Type: text/html; charset=UTF-8','Content-Transfer-Encoding: 8bit'];$payload=implode("\r\n",$headers)."\r\n\r\n".$html."\r\n.";fwrite($fp,$payload."\r\n");$this->expect($fp,[250]);$this->cmd($fp,'QUIT',[221]);fclose($fp);
    }
    private function cmd($fp,string $cmd,array $codes): void { fwrite($fp,$cmd."\r\n");$this->expect($fp,$codes); }
    private function expect($fp,array $codes): void { $line='';do{$part=fgets($fp,515);if($part===false)throw new \RuntimeException('Réponse SMTP absente.');$line=$part;}while(isset($part[3])&&$part[3]==='-');$code=(int)substr($line,0,3);if(!in_array($code,$codes,true))throw new \RuntimeException('Erreur SMTP '.$code.'.'); }
}
