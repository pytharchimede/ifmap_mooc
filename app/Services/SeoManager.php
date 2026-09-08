<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Env;

final class SeoManager
{
    public static function current(string $fallbackTitle,string $brandName,string $fallbackDescription=''): array
    {
        $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
        $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');
        if($base!==''&&str_starts_with($path,$base))$path=substr($path,strlen($base))?:'/';
        $row=null;$settings=[];
        try{
            $db=Database::connection();
            $stmt=$db->prepare('SELECT * FROM seo_pages WHERE path=? LIMIT 1');$stmt->execute([$path]);$row=$stmt->fetch()?:null;
            $stmt=$db->query("SELECT `key`,`value` FROM settings WHERE `group`='seo'");foreach($stmt->fetchAll() as $r)$settings[$r['key']]=$r['value'];
        }catch(\Throwable){}
        $site=rtrim((string)Env::get('APP_URL',''),'\/');
        if($site===''){$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$site=$scheme.'://'.($_SERVER['HTTP_HOST']??'localhost');}
        $organization=trim((string)($settings['organization_name']??''))?:$brandName;
        $title=trim((string)($row['title']??''))?:trim($fallbackTitle);
        if(!str_contains(mb_strtolower($title),mb_strtolower($organization)))$title.=' — '.$organization;
        $defaultDesc=trim((string)($settings['default_description']??''));
        $desc=trim((string)($row['description']??''))?:($fallbackDescription!==''?$fallbackDescription:($defaultDesc!==''?$defaultDesc:$organization.' — Formation, accompagnement et équipements professionnels en Côte d’Ivoire'));
        $canonical=trim((string)($row['canonical_url']??''))?:$site.$path;
        $ogTitle=trim((string)($row['og_title']??''))?:$title;$ogDescription=trim((string)($row['og_description']??''))?:$desc;
        $image=trim((string)($row['og_image']??''))?:trim((string)($settings['default_og_image']??''));if($image!==''&&str_starts_with($image,'/'))$image=$site.$image;
        $schema=self::schema($organization,$site,$path,$row['schema_json']??null);
        return ['title'=>$title,'description'=>$desc,'keywords'=>trim((string)($row['keywords']??'')),'canonical'=>$canonical,'robots'=>(($row['robots_index']??1)?'index':'noindex').','.(($row['robots_follow']??1)?'follow':'nofollow'),'og_title'=>$ogTitle,'og_description'=>$ogDescription,'og_image'=>$image,'twitter_card'=>$row['twitter_card']??'summary_large_image','schema'=>$schema,'google_verification'=>trim((string)($settings['google_site_verification']??'')),'bing_verification'=>trim((string)($settings['bing_site_verification']??''))];
    }

    public static function renderHead(array $seo): string
    {
        $e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        $html='<title>'.$e($seo['title']).'</title><meta name="description" content="'.$e($seo['description']).'"><meta name="robots" content="'.$e($seo['robots']).'"><link rel="canonical" href="'.$e($seo['canonical']).'">';
        if($seo['keywords']!=='')$html.='<meta name="keywords" content="'.$e($seo['keywords']).'">';
        if(!empty($seo['google_verification']))$html.='<meta name="google-site-verification" content="'.$e($seo['google_verification']).'">';
        if(!empty($seo['bing_verification']))$html.='<meta name="msvalidate.01" content="'.$e($seo['bing_verification']).'">';
        $html.='<meta property="og:type" content="website"><meta property="og:title" content="'.$e($seo['og_title']).'"><meta property="og:description" content="'.$e($seo['og_description']).'"><meta property="og:url" content="'.$e($seo['canonical']).'">';
        if($seo['og_image']!=='')$html.='<meta property="og:image" content="'.$e($seo['og_image']).'">';
        $html.='<meta name="twitter:card" content="'.$e($seo['twitter_card']).'"><meta name="twitter:title" content="'.$e($seo['og_title']).'"><meta name="twitter:description" content="'.$e($seo['og_description']).'">';
        if($seo['og_image']!=='')$html.='<meta name="twitter:image" content="'.$e($seo['og_image']).'">';
        if($seo['schema'])$html.='<script type="application/ld+json">'.json_encode($seo['schema'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>';
        return $html;
    }

    private static function schema(string $brand,string $site,string $path,?string $custom): array
    {
        if($custom){$decoded=json_decode($custom,true);if(is_array($decoded))return $decoded;}
        return ['@context'=>'https://schema.org','@graph'=>[
            ['@type'=>'Organization','@id'=>$site.'/#organization','name'=>$brand,'url'=>$site],
            ['@type'=>'WebSite','@id'=>$site.'/#website','url'=>$site,'name'=>$brand,'publisher'=>['@id'=>$site.'/#organization']],
            ['@type'=>'WebPage','@id'=>$site.$path.'#webpage','url'=>$site.$path,'isPartOf'=>['@id'=>$site.'/#website']]
        ]];
    }
}
