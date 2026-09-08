<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Env;

final class SecureSettings
{
    private const SECRET_KEYS=[
        'daily_api_key','smtp_password',
        'twilio_account_sid','twilio_auth_token',
        'meta_whatsapp_token'
    ];

    public function get(string $key,string $default=''): string
    {
        try{$st=Database::connection()->prepare('SELECT value FROM settings WHERE `key`=? LIMIT 1');$st->execute([$key]);$value=$st->fetchColumn();if($value===false)return $default;$value=(string)$value;return in_array($key,self::SECRET_KEYS,true)?$this->decrypt($value):$value;}catch(\Throwable){return $default;}
    }
    public function group(string $group): array
    {
        $out=[];try{$st=Database::connection()->prepare('SELECT `key`,`value` FROM settings WHERE `group`=? ORDER BY `key`');$st->execute([$group]);foreach($st->fetchAll() as $row)$out[$row['key']]=in_array($row['key'],self::SECRET_KEYS,true)?$this->decrypt((string)$row['value']):(string)$row['value'];}catch(\Throwable){}return $out;
    }
    public function set(string $key,string $value,string $group): void
    {
        if(in_array($key,self::SECRET_KEYS,true)&&$value!=='')$value=$this->encrypt($value);
        $st=Database::connection()->prepare('INSERT INTO settings(`key`,`value`,`group`) VALUES(?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value),`group`=VALUES(`group`)');$st->execute([$key,$value,$group]);
    }
    private function encrypt(string $plain): string
    {
        $key=hash('sha256',(string)Env::get('APP_KEY','ifmap'),true);$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)return $plain;return 'enc:v1:'.base64_encode($iv.$tag.$cipher);
    }
    private function decrypt(string $value): string
    {
        if(!str_starts_with($value,'enc:v1:'))return $value;$raw=base64_decode(substr($value,7),true);if($raw===false||strlen($raw)<29)return '';$iv=substr($raw,0,12);$tag=substr($raw,12,16);$cipher=substr($raw,28);$key=hash('sha256',(string)Env::get('APP_KEY','ifmap'),true);$plain=openssl_decrypt($cipher,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);return $plain===false?'':$plain;
    }
}
