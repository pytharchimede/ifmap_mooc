<?php
namespace App\Controllers;

use App\Core\Database;
use App\Services\SecureSettings;

final class MentorFinanceController
{
    private function guard(): void
    {
        if(empty($_SESSION['admin_authenticated'])){$this->redirect('/admin/connexion');}
    }

    private function redirect(string $path): never
    {
        $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');
        header('Location: '.$base.$path);exit;
    }

    public function saveCommission(): void
    {
        $this->guard();
        $rate=max(0,min(100,(float)($_POST['commission_rate']??20)));
        (new SecureSettings())->set('mentor_commission_rate',number_format($rate,2,'.',''),'mentorship');
        $_SESSION['flash']='Commission IFMAP mise à jour à '.rtrim(rtrim(number_format($rate,2,'.',''),'0'),'.').' %.';
        $this->redirect('/admin/mentorat');
    }

    public function saveMentorOverride(): void
    {
        $this->guard();
        $mentorId=(int)($_POST['mentor_id']??0);
        $raw=trim((string)($_POST['commission_rate_override']??''));
        $value=$raw===''?null:max(0,min(100,(float)$raw));
        $st=Database::connection()->prepare('UPDATE mentor_profiles SET commission_rate_override=? WHERE user_id=?');
        $st->execute([$value,$mentorId]);
        $_SESSION['flash']=$value===null?'Commission spécifique supprimée : le taux global IFMAP sera appliqué.':'Commission spécifique enregistrée.';
        $this->redirect('/admin/mentorat');
    }

    public function payoutAction(): void
    {
        $this->guard();
        $requestId=(int)($_POST['request_id']??0);
        $action=(string)($_POST['action']??'');
        $db=Database::connection();
        $st=$db->prepare("SELECT r.*,u.name mentor_name FROM mentorship_requests r JOIN users u ON u.id=r.mentor_id WHERE r.id=? LIMIT 1");
        $st->execute([$requestId]);$r=$st->fetch();
        if(!$r||$r['status']!=='completed'||$r['payment_status']!=='paid'){
            $_SESSION['flash']='Le règlement mentor n’est disponible qu’après une séance payée et terminée.';
            $this->redirect('/admin/mentorat');
        }
        if($action==='hold'){
            $db->prepare("UPDATE mentorship_requests SET mentor_payout_status='held' WHERE id=?")->execute([$requestId]);
            $db->prepare("INSERT INTO mentor_payouts(mentor_id,request_id,gross_amount,commission_rate,commission_amount,net_amount,status,admin_note) VALUES(?,?,?,?,?,?,'held',?) ON DUPLICATE KEY UPDATE status='held',admin_note=VALUES(admin_note),updated_at=CURRENT_TIMESTAMP")->execute([(int)$r['mentor_id'],$requestId,$r['gross_amount'],$r['commission_rate'],$r['commission_amount'],$r['mentor_net_amount'],mb_substr(trim((string)($_POST['note']??'')),0,500)]);
            $_SESSION['flash']='Règlement mentor placé en attente.';
        }elseif($action==='paid'){
            $reference=mb_substr(trim((string)($_POST['payment_reference']??'')),0,190);
            $method=mb_substr(trim((string)($_POST['payment_method']??'virement')),0,80);
            if($reference===''){$reference='IFMAP-PAYOUT-'.date('YmdHis').'-'.$requestId;}
            $db->beginTransaction();
            try{
                $db->prepare("UPDATE mentorship_requests SET mentor_payout_status='paid',mentor_paid_at=NOW() WHERE id=?")->execute([$requestId]);
                $db->prepare("INSERT INTO mentor_payouts(mentor_id,request_id,gross_amount,commission_rate,commission_amount,net_amount,status,payment_reference,payment_method,admin_note,paid_at) VALUES(?,?,?,?,?,?,'paid',?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status='paid',payment_reference=VALUES(payment_reference),payment_method=VALUES(payment_method),admin_note=VALUES(admin_note),paid_at=NOW(),updated_at=CURRENT_TIMESTAMP")->execute([(int)$r['mentor_id'],$requestId,$r['gross_amount'],$r['commission_rate'],$r['commission_amount'],$r['mentor_net_amount'],$reference,$method,mb_substr(trim((string)($_POST['note']??'')),0,500)]);
                $db->commit();
                $_SESSION['flash']='Règlement de '.number_format((float)$r['mentor_net_amount'],0,',',' ').' FCFA marqué payé à '.$r['mentor_name'].'.';
            }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Impossible d’enregistrer le règlement mentor.';}
        }
        $this->redirect('/admin/mentorat');
    }
}
