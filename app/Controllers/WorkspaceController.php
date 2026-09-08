<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\I18n;
use App\Services\SecureSettings;

final class WorkspaceController
{
    private function user(): array { if(empty($_SESSION['user']['id']))$this->redirect('/connexion');return $_SESSION['user']; }
    private function admin(): void { if(empty($_SESSION['admin_authenticated']))$this->redirect('/admin/connexion'); }
    private function redirect(string $path): never { $base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');header('Location: '.$base.$path);exit; }

    public function tickets(): void
    {
        $user=$this->user();$db=Database::connection();$st=$db->prepare("SELECT t.*,(SELECT COUNT(*) FROM support_ticket_messages m WHERE m.ticket_id=t.id) message_count FROM support_tickets t WHERE t.user_id=? ORDER BY COALESCE(t.last_message_at,t.created_at) DESC");$st->execute([(int)$user['id']]);
        View::render('workspace/tickets',['title'=>'Assistance IFMAP','active'=>'tickets','tickets'=>$st->fetchAll(),'flash'=>$_SESSION['flash']??null],'app');unset($_SESSION['flash']);
    }

    public function createTicket(): void
    {
        $user=$this->user();$subject=mb_substr(trim((string)($_POST['subject']??'')),0,190);$message=trim((string)($_POST['message']??''));$category=mb_substr(trim((string)($_POST['category']??'Assistance')),0,80);$priority=in_array($_POST['priority']??'normal',['low','normal','high','urgent'],true)?$_POST['priority']:'normal';
        if($subject===''||$message===''){$_SESSION['flash']='Renseignez un objet et décrivez votre demande.';$this->redirect('/academie/tickets');}
        $db=Database::connection();$db->beginTransaction();try{$ref='IFM-'.date('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,6));$st=$db->prepare("INSERT INTO support_tickets(reference,user_id,subject,category,priority,last_message_at) VALUES(?,?,?,?,?,NOW())");$st->execute([$ref,(int)$user['id'],$subject,$category,$priority]);$id=(int)$db->lastInsertId();$db->prepare("INSERT INTO support_ticket_messages(ticket_id,user_id,sender_role,message) VALUES(?,?,'user',?)")->execute([$id,(int)$user['id'],$message]);$db->commit();$_SESSION['flash']='Ticket '.$ref.' créé. Notre équipe peut maintenant suivre votre demande.';}catch(\Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Impossible de créer le ticket.';}$this->redirect('/academie/tickets');
    }

    public function ticket(): void
    {
        $user=$this->user();$id=(int)($_GET['id']??0);$db=Database::connection();$st=$db->prepare('SELECT * FROM support_tickets WHERE id=? AND user_id=? LIMIT 1');$st->execute([$id,(int)$user['id']]);$ticket=$st->fetch();if(!$ticket)$this->redirect('/academie/tickets');$m=$db->prepare("SELECT m.*,u.name FROM support_ticket_messages m LEFT JOIN users u ON u.id=m.user_id WHERE m.ticket_id=? ORDER BY m.id");$m->execute([$id]);View::render('workspace/ticket',['title'=>$ticket['reference'],'active'=>'tickets','ticket'=>$ticket,'messages'=>$m->fetchAll()],'app');
    }

    public function replyTicket(): void
    {
        $user=$this->user();$id=(int)($_POST['ticket_id']??0);$message=trim((string)($_POST['message']??''));if($message==='')$this->redirect('/academie/ticket?id='.$id);$db=Database::connection();$st=$db->prepare('SELECT id FROM support_tickets WHERE id=? AND user_id=? LIMIT 1');$st->execute([$id,(int)$user['id']]);if(!$st->fetchColumn())$this->redirect('/academie/tickets');$db->prepare("INSERT INTO support_ticket_messages(ticket_id,user_id,sender_role,message) VALUES(?,?,'user',?)")->execute([$id,(int)$user['id'],$message]);$db->prepare("UPDATE support_tickets SET status=IF(status IN ('resolved','closed'),'open',status),last_message_at=NOW() WHERE id=?")->execute([$id]);$this->redirect('/academie/ticket?id='.$id);
    }

    public function adminTickets(): void
    {
        $this->admin();$db=Database::connection();$tickets=$db->query("SELECT t.*,u.name user_name,u.email user_email,a.name assignee_name,(SELECT COUNT(*) FROM support_ticket_messages m WHERE m.ticket_id=t.id) message_count FROM support_tickets t LEFT JOIN users u ON u.id=t.user_id LEFT JOIN users a ON a.id=t.assigned_to ORDER BY FIELD(t.status,'open','in_progress','waiting_user','resolved','closed'),FIELD(t.priority,'urgent','high','normal','low'),COALESCE(t.last_message_at,t.created_at) DESC LIMIT 300")->fetchAll();$metrics=['open'=>(int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status='open'")->fetchColumn(),'progress'=>(int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status='in_progress'")->fetchColumn(),'urgent'=>(int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE priority='urgent' AND status NOT IN ('resolved','closed')")->fetchColumn(),'resolved'=>(int)$db->query("SELECT COUNT(*) FROM support_tickets WHERE status='resolved'")->fetchColumn()];View::render('admin/ticketing',['title'=>'Ticketing & assistance','active'=>'admin-ticketing','tickets'=>$tickets,'metrics'=>$metrics],'admin');
    }

    public function adminTicket(): void
    {
        $this->admin();$id=(int)($_GET['id']??0);$db=Database::connection();$st=$db->prepare('SELECT t.*,u.name user_name,u.email user_email,u.phone user_phone FROM support_tickets t LEFT JOIN users u ON u.id=t.user_id WHERE t.id=?');$st->execute([$id]);$ticket=$st->fetch();if(!$ticket)$this->redirect('/admin/tickets');$m=$db->prepare("SELECT m.*,u.name FROM support_ticket_messages m LEFT JOIN users u ON u.id=m.user_id WHERE m.ticket_id=? ORDER BY m.id");$m->execute([$id]);View::render('admin/ticket',['title'=>$ticket['reference'],'active'=>'admin-ticketing','ticket'=>$ticket,'messages'=>$m->fetchAll()],'admin');
    }

    public function adminTicketAction(): void
    {
        $this->admin();$id=(int)($_POST['ticket_id']??0);$status=in_array($_POST['status']??'',['open','in_progress','waiting_user','resolved','closed'],true)?$_POST['status']:null;$message=trim((string)($_POST['message']??''));$db=Database::connection();if($status)$db->prepare('UPDATE support_tickets SET status=? WHERE id=?')->execute([$status,$id]);if($message!==''){$uid=(int)($_SESSION['user']['id']??0);$db->prepare("INSERT INTO support_ticket_messages(ticket_id,user_id,sender_role,message) VALUES(?,?, 'staff',?)")->execute([$id,$uid?:null,$message]);$db->prepare('UPDATE support_tickets SET last_message_at=NOW() WHERE id=?')->execute([$id]);}$this->redirect('/admin/ticket?id='.$id);
    }

    public function crm(): void
    {
        $this->admin();$db=Database::connection();$contacts=$db->query("SELECT c.*,u.name owner_name,(SELECT COUNT(*) FROM crm_opportunities o WHERE o.contact_id=c.id) opportunity_count FROM crm_contacts c LEFT JOIN users u ON u.id=c.owner_id ORDER BY c.updated_at DESC LIMIT 300")->fetchAll();$opportunities=$db->query("SELECT o.*,c.name contact_name,c.company FROM crm_opportunities o JOIN crm_contacts c ON c.id=o.contact_id ORDER BY FIELD(o.stage,'negotiation','proposal','qualification','won','lost'),o.updated_at DESC LIMIT 200")->fetchAll();$pipeline=$db->query("SELECT stage,COUNT(*) qty,COALESCE(SUM(amount),0) total FROM crm_opportunities GROUP BY stage")->fetchAll();View::render('admin/crm',['title'=>'CRM & opportunités','active'=>'admin-crm','contacts'=>$contacts,'opportunities'=>$opportunities,'pipeline'=>$pipeline,'flash'=>$_SESSION['flash']??null],'admin');unset($_SESSION['flash']);
    }

    public function saveContact(): void
    {
        $this->admin();$id=(int)($_POST['id']??0);$name=mb_substr(trim((string)($_POST['name']??'')),0,190);if($name===''){$this->redirect('/admin/crm');}$data=[in_array($_POST['type']??'lead',['lead','prospect','customer','partner'],true)?$_POST['type']:'lead',mb_substr(trim((string)($_POST['company']??'')),0,190),$name,mb_substr(trim((string)($_POST['email']??'')),0,190),mb_substr(trim((string)($_POST['phone']??'')),0,40),mb_substr(trim((string)($_POST['source']??'')),0,100),mb_substr(trim((string)($_POST['status']??'new')),0,80),trim((string)($_POST['notes']??''))];$db=Database::connection();if($id){$db->prepare('UPDATE crm_contacts SET type=?,company=?,name=?,email=?,phone=?,source=?,status=?,notes=? WHERE id=?')->execute([...$data,$id]);}else{$db->prepare('INSERT INTO crm_contacts(type,company,name,email,phone,source,status,notes) VALUES(?,?,?,?,?,?,?,?)')->execute($data);}$_SESSION['flash']='Contact CRM enregistré.';$this->redirect('/admin/crm');
    }

    public function saveOpportunity(): void
    {
        $this->admin();$contactId=(int)($_POST['contact_id']??0);$title=mb_substr(trim((string)($_POST['title']??'')),0,190);if(!$contactId||$title==='')$this->redirect('/admin/crm');$stage=in_array($_POST['stage']??'qualification',['qualification','proposal','negotiation','won','lost'],true)?$_POST['stage']:'qualification';$amount=max(0,(float)($_POST['amount']??0));$prob=max(0,min(100,(int)($_POST['probability']??10)));$date=trim((string)($_POST['expected_close_at']??''))?:null;Database::connection()->prepare('INSERT INTO crm_opportunities(contact_id,title,stage,amount,probability,expected_close_at) VALUES(?,?,?,?,?,?)')->execute([$contactId,$title,$stage,$amount,$prob,$date]);$_SESSION['flash']='Opportunité ajoutée au pipeline.';$this->redirect('/admin/crm');
    }

    public function chat(): void
    {
        $user=$this->user();$db=Database::connection();$st=$db->prepare("SELECT c.*, (SELECT message FROM chat_messages m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) last_message,(SELECT COUNT(*) FROM chat_messages m WHERE m.conversation_id=c.id AND m.id>COALESCE(p.last_read_message_id,0) AND m.sender_id<>?) unread FROM chat_conversations c JOIN chat_participants p ON p.conversation_id=c.id AND p.user_id=? ORDER BY COALESCE(c.last_message_at,c.created_at) DESC");$st->execute([(int)$user['id'],(int)$user['id']]);$conversations=$st->fetchAll();$people=$db->query("SELECT id,name,role,avatar FROM users WHERE status='active' ORDER BY name LIMIT 200")->fetchAll();View::render('workspace/chat',['title'=>'Discussion instantanée','active'=>'chat','conversations'=>$conversations,'people'=>$people],'app');
    }

    public function conversation(): void
    {
        $user=$this->user();$id=(int)($_GET['id']??0);$db=Database::connection();$p=$db->prepare('SELECT 1 FROM chat_participants WHERE conversation_id=? AND user_id=?');$p->execute([$id,(int)$user['id']]);if(!$p->fetchColumn())$this->redirect('/academie/discussions');$m=$db->prepare("SELECT m.*,u.name,u.avatar FROM chat_messages m LEFT JOIN users u ON u.id=m.sender_id WHERE m.conversation_id=? ORDER BY m.id DESC LIMIT 100");$m->execute([$id]);$messages=array_reverse($m->fetchAll());$last=end($messages);if($last)$db->prepare('UPDATE chat_participants SET last_read_message_id=? WHERE conversation_id=? AND user_id=?')->execute([(int)$last['id'],$id,(int)$user['id']]);View::render('workspace/conversation',['title'=>'Discussion','active'=>'chat','conversationId'=>$id,'messages'=>$messages],'app');
    }

    public function startConversation(): void
    {
        $user=$this->user();$other=(int)($_POST['user_id']??0);if(!$other||$other===(int)$user['id'])$this->redirect('/academie/discussions');$db=Database::connection();$st=$db->prepare("SELECT c.id FROM chat_conversations c JOIN chat_participants a ON a.conversation_id=c.id AND a.user_id=? JOIN chat_participants b ON b.conversation_id=c.id AND b.user_id=? WHERE c.type='direct' LIMIT 1");$st->execute([(int)$user['id'],$other]);$id=(int)($st->fetchColumn()?:0);if(!$id){$db->beginTransaction();try{$db->prepare("INSERT INTO chat_conversations(type,created_by) VALUES('direct',?)")->execute([(int)$user['id']]);$id=(int)$db->lastInsertId();$db->prepare('INSERT INTO chat_participants(conversation_id,user_id) VALUES(?,?),(?,?)')->execute([$id,(int)$user['id'],$id,$other]);$db->commit();}catch(\Throwable){if($db->inTransaction())$db->rollBack();$this->redirect('/academie/discussions');}}$this->redirect('/academie/discussion?id='.$id);
    }

    public function sendMessage(): void
    {
        $user=$this->user();$id=(int)($_POST['conversation_id']??0);$message=trim((string)($_POST['message']??''));if($message==='')$this->redirect('/academie/discussion?id='.$id);$db=Database::connection();$p=$db->prepare('SELECT 1 FROM chat_participants WHERE conversation_id=? AND user_id=?');$p->execute([$id,(int)$user['id']]);if(!$p->fetchColumn())$this->redirect('/academie/discussions');$db->prepare('INSERT INTO chat_messages(conversation_id,sender_id,message) VALUES(?,?,?)')->execute([$id,(int)$user['id'],$message]);$db->prepare('UPDATE chat_conversations SET last_message_at=NOW() WHERE id=?')->execute([$id]);$this->redirect('/academie/discussion?id='.$id);
    }

    public function translations(): void
    {
        $this->admin();$db=Database::connection();$rows=$db->query('SELECT * FROM translation_strings ORDER BY scope,translation_key,locale LIMIT 1000')->fetchAll();$s=(new SecureSettings())->group('i18n');View::render('admin/translations',['title'=>'Traductions & langues','active'=>'admin-i18n','translations'=>$rows,'settings'=>$s,'flash'=>$_SESSION['flash']??null],'admin');unset($_SESSION['flash']);
    }

    public function saveTranslation(): void
    {
        $this->admin();$key=preg_replace('/[^a-zA-Z0-9_.-]/','',trim((string)($_POST['translation_key']??'')));$locale=mb_substr(trim((string)($_POST['locale']??'fr')),0,10);$value=trim((string)($_POST['value']??''));$scope=mb_substr(trim((string)($_POST['scope']??'global')),0,80);if($key!==''&&$value!==''){Database::connection()->prepare('INSERT INTO translation_strings(translation_key,locale,value,scope,updated_by) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value),scope=VALUES(scope),updated_by=VALUES(updated_by)')->execute([$key,$locale,$value,$scope,(int)($_SESSION['user']['id']??0)?:null]);}$_SESSION['flash']='Traduction enregistrée.';$this->redirect('/admin/traductions');
    }

    public function saveI18nSettings(): void
    {
        $this->admin();$s=new SecureSettings();$default=mb_substr(trim((string)($_POST['default_locale']??'fr')),0,10);$locales=preg_replace('/[^a-zA-Z0-9,_-]/','',trim((string)($_POST['supported_locales']??'fr,en')));$s->set('default_locale',$default,'i18n');$s->set('supported_locales',$locales?:'fr,en','i18n');$s->set('auto_detect_locale',!empty($_POST['auto_detect_locale'])?'1':'0','i18n');$_SESSION['flash']='Paramètres multilingues enregistrés.';$this->redirect('/admin/traductions');
    }

    public function switchLocale(): void
    {
        $locale=mb_substr(trim((string)($_POST['locale']??'fr')),0,10);I18n::setLocale($locale);$back=(string)($_SERVER['HTTP_REFERER']??'/');header('Location: '.$back);exit;
    }
}
