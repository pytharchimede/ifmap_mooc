<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Core\View;
use App\Repositories\AnalyticsRepository;

final class SeoAnalyticsController
{
    private function guard(): void
    {
        if (!($_SESSION['admin_authenticated'] ?? false)) {
            header('Location: '.$this->base('/admin/connexion'));exit;
        }
    }
    private function base(string $path): string { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');return $base.$path; }

    public function visitors(): void
    {
        $this->guard();
        $filters=[
            'from'=>$this->date($_GET['from']??date('Y-m-d',strtotime('-29 days'))),
            'to'=>$this->date($_GET['to']??date('Y-m-d')),
            'country'=>strtoupper(substr(trim((string)($_GET['country']??'')),0,2)),
            'device'=>trim((string)($_GET['device']??'')),
            'returning'=>in_array((string)($_GET['returning']??''),['0','1'],true)?(string)$_GET['returning']:'',
            'q'=>mb_substr(trim((string)($_GET['q']??'')),0,190),
        ];
        $repo=new AnalyticsRepository();
        View::render('admin/visitors',[
            'title'=>'Visiteurs & audience','active'=>'admin-visitors','filters'=>$filters,
            'stats'=>$repo->stats($filters),'sessions'=>$repo->sessions($filters),'topPages'=>$repo->topPages($filters),
            'sources'=>$repo->sources($filters),'devices'=>$repo->devices($filters),'browsers'=>$repo->browsers($filters),
            'countries'=>$repo->countries($filters),'mapPoints'=>$repo->mapPoints($filters),'trend'=>$repo->trend($filters),
        ],'admin');
    }

    public function seo(): void
    {
        $this->guard();$db=Database::connection();
        $pages=$db->query('SELECT * FROM seo_pages ORDER BY path')->fetchAll();
        foreach($pages as &$p)$p['score']=$this->score($p);unset($p);
        $settings=[];try{$stmt=$db->query("SELECT `key`,`value` FROM settings WHERE `group`='seo'");foreach($stmt->fetchAll() as $r)$settings[$r['key']]=$r['value'];}catch(\Throwable){}
        View::render('admin/seo',['title'=>'Référencement SEO','active'=>'admin-seo','pages'=>$pages,'settings'=>$settings],'admin');
    }

    public function saveSeo(): void
    {
        $this->guard();$db=Database::connection();$action=(string)($_POST['action']??'page');
        if($action==='settings'){
            $allowed=['default_description','default_og_image','google_site_verification','bing_site_verification','organization_name'];
            $stmt=$db->prepare("INSERT INTO settings(`group`,`key`,`value`) VALUES('seo',?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
            foreach($allowed as $key)$stmt->execute([$key,mb_substr(trim((string)($_POST[$key]??'')),0,$key==='default_description'?500:1000)]);
            $_SESSION['flash']='Paramètres SEO globaux enregistrés.';$this->redirect('/admin/referencement');
        }
        $path='/'.ltrim(trim((string)($_POST['path']??'')),'/');if($path==='/'||preg_match('/\s/',$path)){$path=$path==='/'?'/':preg_replace('/\s+/','-',$path);}
        $schema=trim((string)($_POST['schema_json']??''));if($schema!==''&&json_decode($schema,true)===null){$_SESSION['flash']='Le JSON-LD est invalide.';$this->redirect('/admin/referencement');}
        $sql="INSERT INTO seo_pages(path,title,description,keywords,canonical_url,robots_index,robots_follow,og_title,og_description,og_image,twitter_card,schema_json,sitemap_priority,sitemap_changefreq) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),keywords=VALUES(keywords),canonical_url=VALUES(canonical_url),robots_index=VALUES(robots_index),robots_follow=VALUES(robots_follow),og_title=VALUES(og_title),og_description=VALUES(og_description),og_image=VALUES(og_image),twitter_card=VALUES(twitter_card),schema_json=VALUES(schema_json),sitemap_priority=VALUES(sitemap_priority),sitemap_changefreq=VALUES(sitemap_changefreq)";
        $db->prepare($sql)->execute([$path,$this->v('title',190),$this->v('description',320),$this->v('keywords',500),$this->v('canonical_url',500),isset($_POST['robots_index'])?1:0,isset($_POST['robots_follow'])?1:0,$this->v('og_title',190),$this->v('og_description',320),$this->v('og_image',500),in_array($_POST['twitter_card']??'', ['summary','summary_large_image'],true)?$_POST['twitter_card']:'summary_large_image',$schema!==''?$schema:null,max(0.1,min(1.0,(float)($_POST['sitemap_priority']??0.5))),in_array($_POST['sitemap_changefreq']??'', ['always','hourly','daily','weekly','monthly','yearly','never'],true)?$_POST['sitemap_changefreq']:'weekly']);
        $_SESSION['flash']='Référencement de '.$path.' enregistré.';$this->redirect('/admin/referencement');
    }

    public function deleteSeo(): void
    {
        $this->guard();$id=(int)($_POST['id']??0);if($id>0)Database::connection()->prepare('DELETE FROM seo_pages WHERE id=?')->execute([$id]);$_SESSION['flash']='Configuration SEO supprimée.';$this->redirect('/admin/referencement');
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');$site=rtrim((string)Env::get('APP_URL',''),'\/');
        echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /commande\nDisallow: /profil\nDisallow: /academie/\nSitemap: {$site}/sitemap.xml\n";
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');$db=Database::connection();$site=rtrim((string)Env::get('APP_URL',''),'\/');$urls=['/'=>['1.0','daily'],'/formations'=>['0.9','daily'],'/boutique'=>['0.8','daily'],'/entreprise'=>['0.7','monthly'],'/actualites'=>['0.7','daily'],'/contact'=>['0.5','monthly']];
        try{foreach($db->query("SELECT slug FROM courses WHERE status='published'")->fetchAll() as $r)$urls['/formations/'.rawurlencode($r['slug'])]=['0.8','weekly'];foreach($db->query("SELECT slug FROM products WHERE status='published'")->fetchAll() as $r)$urls['/boutique/'.rawurlencode($r['slug'])]=['0.7','weekly'];foreach($db->query("SELECT slug FROM posts WHERE status='published'")->fetchAll() as $r)$urls['/actualites/'.rawurlencode($r['slug'])]=['0.7','weekly'];foreach($db->query('SELECT path,sitemap_priority,sitemap_changefreq FROM seo_pages WHERE robots_index=1')->fetchAll() as $r)$urls[$r['path']]=[(string)$r['sitemap_priority'],$r['sitemap_changefreq']];}catch(\Throwable){}
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";foreach($urls as $path=>$meta)echo '<url><loc>'.htmlspecialchars($site.$path,ENT_XML1).'</loc><changefreq>'.$meta[1].'</changefreq><priority>'.$meta[0].'</priority></url>' . "\n";echo '</urlset>';
    }

    private function score(array $p): int { $s=0;if(mb_strlen((string)$p['title'])>=30&&mb_strlen((string)$p['title'])<=65)$s+=25;if(mb_strlen((string)$p['description'])>=120&&mb_strlen((string)$p['description'])<=170)$s+=25;if(!empty($p['canonical_url']))$s+=15;if(!empty($p['og_title'])&&!empty($p['og_description']))$s+=20;if(!empty($p['og_image']))$s+=10;if(!empty($p['schema_json']))$s+=5;return $s; }
    private function date(string $v): string { return preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)?$v:date('Y-m-d'); }
    private function v(string $key,int $max): ?string { $v=mb_substr(trim((string)($_POST[$key]??'')),0,$max);return $v!==''?$v:null; }
    private function redirect(string $path): never { header('Location: '.$this->base($path));exit; }
}
