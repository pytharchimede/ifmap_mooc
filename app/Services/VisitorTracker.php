<?php
namespace App\Services;

use App\Core\Database;

final class VisitorTracker
{
    public function track(string $path): void
    {
        if (!$this->shouldTrack($path)) return;
        try {
            $db=Database::connection();
            $visitor=$this->cookieId('ifmap_vid',400*86400);
            $session=$this->cookieId('ifmap_vsid',1800,true);
            $now=date('Y-m-d H:i:s');
            $userId=!empty($_SESSION['user']['id'])?(int)$_SESSION['user']['id']:null;
            $ua=mb_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,1500);
            $ip=$this->clientIp();
            $existing=$db->prepare('SELECT id FROM visitor_sessions WHERE session_uuid=? LIMIT 1');$existing->execute([$session]);
            if($existing->fetchColumn()){
                $db->prepare('UPDATE visitor_sessions SET last_seen=?,user_id=COALESCE(?,user_id) WHERE session_uuid=?')->execute([$now,$userId,$session]);
            } else {
                $known=$db->prepare('SELECT 1 FROM visitor_sessions WHERE visitor_uuid=? LIMIT 1');$known->execute([$visitor]);$returning=(int)(bool)$known->fetchColumn();
                $geo=(new IpGeoService())->resolve($ip);
                $device=$this->device($ua);
                $stmt=$db->prepare('INSERT INTO visitor_sessions(visitor_uuid,session_uuid,user_id,is_returning,first_seen,last_seen,landing_path,referrer,utm_source,utm_medium,utm_campaign,utm_term,utm_content,ip_address,remote_port,country_code,country_name,region_name,city,latitude,longitude,timezone,device_type,browser,os,user_agent) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$visitor,$session,$userId,$returning,$now,$now,mb_substr($path,0,500),$this->referrer(),$this->q('utm_source'),$this->q('utm_medium'),$this->q('utm_campaign'),$this->q('utm_term'),$this->q('utm_content'),$ip,$this->port(),$geo['country_code'],$geo['country_name'],$geo['region_name'],$geo['city'],$geo['latitude'],$geo['longitude'],$geo['timezone'],$device['type'],$device['browser'],$device['os'],$ua]);
            }
            $db->prepare('INSERT INTO visitor_pageviews(visitor_uuid,session_uuid,user_id,path,query_string,referrer,created_at) VALUES(?,?,?,?,?,?,NOW())')->execute([$visitor,$session,$userId,mb_substr($path,0,500),mb_substr((string)($_SERVER['QUERY_STRING']??''),0,1000),$this->referrer()]);
        } catch (\Throwable $e) {
            error_log('IFMAP analytics: '.$e->getMessage());
        }
    }

    private function shouldTrack(string $path): bool
    {
        if (($_SERVER['REQUEST_METHOD']??'GET')!=='GET') return false;
        foreach(['/admin','/public/','/favicon.ico','/paiement/','/academie/temoignage/upload-url'] as $prefix) if(str_starts_with($path,$prefix)) return false;
        $ua=strtolower((string)($_SERVER['HTTP_USER_AGENT']??''));
        return !preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|whatsapp/i',$ua);
    }

    private function cookieId(string $name,int $ttl,bool $refresh=false): string
    {
        $value=preg_match('/^[a-f0-9]{32}$/',(string)($_COOKIE[$name]??''))?(string)$_COOKIE[$name]:bin2hex(random_bytes(16));
        if(!isset($_COOKIE[$name])||$refresh){
            $secure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');
            setcookie($name,$value,['expires'=>time()+$ttl,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
            $_COOKIE[$name]=$value;
        }
        return $value;
    }

    private function clientIp(): ?string
    {
        foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key){
            $raw=trim((string)($_SERVER[$key]??''));if($raw==='')continue;$ip=trim(explode(',',$raw)[0]);if(filter_var($ip,FILTER_VALIDATE_IP))return $ip;
        }
        return null;
    }
    private function port(): ?int { $p=(int)($_SERVER['REMOTE_PORT']??0); return $p>0&&$p<=65535?$p:null; }
    private function referrer(): ?string { $v=trim((string)($_SERVER['HTTP_REFERER']??'')); return $v!==''?mb_substr($v,0,1000):null; }
    private function q(string $key): ?string { $v=trim((string)($_GET[$key]??'')); return $v!==''?mb_substr($v,0,190):null; }

    private function device(string $ua): array
    {
        $u=strtolower($ua);$type='Desktop';
        if(preg_match('/ipad|tablet|kindle|silk/',$u))$type='Tablette';elseif(preg_match('/mobile|iphone|ipod|android/',$u))$type='Mobile';
        $browser='Autre';
        if(str_contains($u,'edg/'))$browser='Edge';elseif(str_contains($u,'opr/')||str_contains($u,'opera'))$browser='Opera';elseif(str_contains($u,'chrome/')&&!str_contains($u,'chromium'))$browser='Chrome';elseif(str_contains($u,'firefox/'))$browser='Firefox';elseif(str_contains($u,'safari/')&&!str_contains($u,'chrome/'))$browser='Safari';
        $os='Autre';
        if(str_contains($u,'windows'))$os='Windows';elseif(str_contains($u,'android'))$os='Android';elseif(str_contains($u,'iphone')||str_contains($u,'ipad'))$os='iOS';elseif(str_contains($u,'mac os'))$os='macOS';elseif(str_contains($u,'linux'))$os='Linux';
        return ['type'=>$type,'browser'=>$browser,'os'=>$os];
    }
}
