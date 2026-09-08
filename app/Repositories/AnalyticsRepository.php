<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AnalyticsRepository
{
    private PDO $db;
    public function __construct(){ $this->db=Database::connection(); }

    public function stats(array $f): array
    {
        [$where,$args]=$this->filters($f,'s');
        $q="SELECT COUNT(DISTINCT s.visitor_uuid) unique_visitors,COUNT(*) sessions,SUM(s.is_returning=1) returning_sessions,COUNT(DISTINCT CASE WHEN s.is_returning=1 THEN s.visitor_uuid END) returning_visitors FROM visitor_sessions s WHERE $where";
        $st=$this->db->prepare($q);$st->execute($args);$base=$st->fetch()?:[];
        [$pwhere,$pargs]=$this->pageFilters($f,'p');$st=$this->db->prepare("SELECT COUNT(*) FROM visitor_pageviews p WHERE $pwhere");$st->execute($pargs);$base['pageviews']=(int)$st->fetchColumn();
        $base['unique_visitors']=(int)($base['unique_visitors']??0);$base['sessions']=(int)($base['sessions']??0);$base['returning_sessions']=(int)($base['returning_sessions']??0);$base['returning_visitors']=(int)($base['returning_visitors']??0);
        $base['pages_per_session']=$base['sessions']?round($base['pageviews']/$base['sessions'],2):0;
        return $base;
    }

    public function sessions(array $f,int $limit=100): array
    {
        [$where,$args]=$this->filters($f,'s');
        $sql="SELECT s.*,(SELECT COUNT(*) FROM visitor_pageviews p WHERE p.session_uuid=s.session_uuid) pageviews FROM visitor_sessions s WHERE $where ORDER BY s.last_seen DESC LIMIT ".max(1,min(500,$limit));
        $st=$this->db->prepare($sql);$st->execute($args);return $st->fetchAll();
    }

    public function topPages(array $f): array
    {
        [$where,$args]=$this->pageFilters($f,'p');$st=$this->db->prepare("SELECT p.path,COUNT(*) views,COUNT(DISTINCT p.visitor_uuid) visitors FROM visitor_pageviews p WHERE $where GROUP BY p.path ORDER BY views DESC LIMIT 12");$st->execute($args);return $st->fetchAll();
    }
    public function sources(array $f): array
    {
        [$where,$args]=$this->filters($f,'s');$st=$this->db->prepare("SELECT COALESCE(NULLIF(s.utm_source,''),CASE WHEN s.referrer IS NULL OR s.referrer='' THEN 'Direct' ELSE SUBSTRING_INDEX(REPLACE(REPLACE(s.referrer,'https://',''),'http://',''),'/',1) END) source,COUNT(*) sessions FROM visitor_sessions s WHERE $where GROUP BY source ORDER BY sessions DESC LIMIT 10");$st->execute($args);return $st->fetchAll();
    }
    public function devices(array $f): array
    {
        [$where,$args]=$this->filters($f,'s');$st=$this->db->prepare("SELECT COALESCE(s.device_type,'Inconnu') label,COUNT(*) value FROM visitor_sessions s WHERE $where GROUP BY label ORDER BY value DESC");$st->execute($args);return $st->fetchAll();
    }
    public function browsers(array $f): array
    {
        [$where,$args]=$this->filters($f,'s');$st=$this->db->prepare("SELECT COALESCE(s.browser,'Inconnu') label,COUNT(*) value FROM visitor_sessions s WHERE $where GROUP BY label ORDER BY value DESC LIMIT 8");$st->execute($args);return $st->fetchAll();
    }
    public function countries(array $f): array
    {
        [$where,$args]=$this->filters($f,'s');$st=$this->db->prepare("SELECT COALESCE(s.country_name,'Inconnu') country,MAX(s.country_code) code,COUNT(*) sessions,COUNT(DISTINCT s.visitor_uuid) visitors FROM visitor_sessions s WHERE $where GROUP BY country ORDER BY sessions DESC LIMIT 20");$st->execute($args);return $st->fetchAll();
    }
    public function mapPoints(array $f): array
    {
        [$where,$args]=$this->filters($f,'s');$st=$this->db->prepare("SELECT ROUND(s.latitude,3) latitude,ROUND(s.longitude,3) longitude,COALESCE(s.city,s.region_name,s.country_name,'Inconnu') label,COUNT(*) sessions,COUNT(DISTINCT s.visitor_uuid) visitors FROM visitor_sessions s WHERE $where AND s.latitude IS NOT NULL AND s.longitude IS NOT NULL GROUP BY ROUND(s.latitude,3),ROUND(s.longitude,3),label ORDER BY sessions DESC LIMIT 300");$st->execute($args);return $st->fetchAll();
    }
    public function trend(array $f): array
    {
        [$where,$args]=$this->filters($f,'s');$st=$this->db->prepare("SELECT DATE(s.first_seen) day,COUNT(*) sessions,COUNT(DISTINCT s.visitor_uuid) visitors,SUM(s.is_returning=1) returning_sessions FROM visitor_sessions s WHERE $where GROUP BY DATE(s.first_seen) ORDER BY day");$st->execute($args);return $st->fetchAll();
    }

    private function filters(array $f,string $a): array
    {
        $w=['1=1'];$args=[];
        if(!empty($f['from'])){$w[]="$a.first_seen>=?";$args[]=$f['from'].' 00:00:00';}
        if(!empty($f['to'])){$w[]="$a.first_seen<=?";$args[]=$f['to'].' 23:59:59';}
        if(!empty($f['country'])){$w[]="$a.country_code=?";$args[]=$f['country'];}
        if(!empty($f['device'])){$w[]="$a.device_type=?";$args[]=$f['device'];}
        if(($f['returning']??'')==='1')$w[]="$a.is_returning=1";elseif(($f['returning']??'')==='0')$w[]="$a.is_returning=0";
        if(!empty($f['q'])){$w[]="($a.ip_address LIKE ? OR $a.city LIKE ? OR $a.country_name LIKE ? OR $a.browser LIKE ? OR $a.os LIKE ? OR $a.landing_path LIKE ? OR $a.visitor_uuid LIKE ?)";$needle='%'.$f['q'].'%';for($i=0;$i<7;$i++)$args[]=$needle;}
        return [implode(' AND ',$w),$args];
    }
    private function pageFilters(array $f,string $a): array
    {
        $w=['1=1'];$args=[];
        if(!empty($f['from'])){$w[]="$a.created_at>=?";$args[]=$f['from'].' 00:00:00';}
        if(!empty($f['to'])){$w[]="$a.created_at<=?";$args[]=$f['to'].' 23:59:59';}
        if(!empty($f['q'])){$w[]="($a.path LIKE ? OR $a.referrer LIKE ? OR $a.visitor_uuid LIKE ?)";$needle='%'.$f['q'].'%';for($i=0;$i<3;$i++)$args[]=$needle;}
        return [implode(' AND ',$w),$args];
    }
}
