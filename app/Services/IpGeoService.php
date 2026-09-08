<?php
namespace App\Services;

use App\Core\Database;

final class IpGeoService
{
    public function resolve(?string $ip): array
    {
        $ip = trim((string)$ip);
        $empty = ['country_code'=>null,'country_name'=>null,'region_name'=>null,'city'=>null,'latitude'=>null,'longitude'=>null,'timezone'=>null];
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) return $empty;
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return $empty;

        $db = Database::connection();
        try {
            $stmt = $db->prepare('SELECT country_code,country_name,region_name,city,latitude,longitude,timezone FROM visitor_geo_cache WHERE ip_address=? LIMIT 1');
            $stmt->execute([$ip]);
            if ($row = $stmt->fetch()) return array_merge($empty, $row);
        } catch (\Throwable) {}

        $data = $this->lookup($ip);
        if (!$data) return $empty;
        $row = [
            'country_code'=>substr((string)($data['country_code'] ?? ''),0,2) ?: null,
            'country_name'=>mb_substr((string)($data['country'] ?? ''),0,120) ?: null,
            'region_name'=>mb_substr((string)($data['region'] ?? ''),0,190) ?: null,
            'city'=>mb_substr((string)($data['city'] ?? ''),0,190) ?: null,
            'latitude'=>isset($data['latitude']) && is_numeric($data['latitude']) ? (float)$data['latitude'] : null,
            'longitude'=>isset($data['longitude']) && is_numeric($data['longitude']) ? (float)$data['longitude'] : null,
            'timezone'=>mb_substr((string)($data['timezone']['id'] ?? $data['timezone'] ?? ''),0,100) ?: null,
        ];
        try {
            $stmt=$db->prepare('REPLACE INTO visitor_geo_cache(ip_address,country_code,country_name,region_name,city,latitude,longitude,timezone,resolved_at) VALUES(?,?,?,?,?,?,?,?,NOW())');
            $stmt->execute([$ip,$row['country_code'],$row['country_name'],$row['region_name'],$row['city'],$row['latitude'],$row['longitude'],$row['timezone']]);
        } catch (\Throwable) {}
        return $row;
    }

    private function lookup(string $ip): ?array
    {
        $url='https://ipwho.is/'.rawurlencode($ip);
        $json=false;
        if (function_exists('curl_init')) {
            $ch=curl_init($url);
            curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>1,CURLOPT_TIMEOUT=>2,CURLOPT_USERAGENT=>'IFMAP-Analytics/1.0']);
            $json=curl_exec($ch);curl_close($ch);
        } else {
            $ctx=stream_context_create(['http'=>['timeout'=>2,'header'=>"User-Agent: IFMAP-Analytics/1.0\r\n"]]);
            $json=@file_get_contents($url,false,$ctx);
        }
        if (!is_string($json) || $json==='') return null;
        $data=json_decode($json,true);
        return is_array($data) && ($data['success'] ?? true) ? $data : null;
    }
}
