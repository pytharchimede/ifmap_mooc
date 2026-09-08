<?php
namespace App\Services;

use App\Core\Database;

final class I18n
{
    private static array $cache=[];

    public static function locale(): string
    {
        $settings=new SecureSettings();
        $supported=array_filter(array_map('trim',explode(',',$settings->get('supported_locales','fr,en'))));
        $default=$settings->get('default_locale','fr');
        $locale=(string)($_SESSION['locale']??'');
        if($locale===''&&$settings->get('auto_detect_locale','0')==='1'){
            $locale=strtolower(substr((string)($_SERVER['HTTP_ACCEPT_LANGUAGE']??$default),0,2));
        }
        return in_array($locale,$supported,true)?$locale:$default;
    }

    public static function setLocale(string $locale): bool
    {
        $supported=array_filter(array_map('trim',explode(',',(new SecureSettings())->get('supported_locales','fr,en'))));
        if(!in_array($locale,$supported,true))return false;
        $_SESSION['locale']=$locale;
        return true;
    }

    public static function t(string $key,string $fallback=''): string
    {
        $locale=self::locale();$cacheKey=$locale.'|'.$key;
        if(isset(self::$cache[$cacheKey]))return self::$cache[$cacheKey];
        try{$st=Database::connection()->prepare('SELECT value FROM translation_strings WHERE translation_key=? AND locale=? LIMIT 1');$st->execute([$key,$locale]);$value=$st->fetchColumn();}catch(\Throwable){$value=false;}
        return self::$cache[$cacheKey]=$value!==false?(string)$value:($fallback!==''?$fallback:$key);
    }
}
