<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class AdminChatController
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
    public function index(): void
    {
        $this->guard();$db=Database::connection();
        $metrics=[
            'conversations'=>(int)$db->query('SELECT COUNT(*) FROM chat_conversations')->fetchColumn(),
            'messages'=>(int)$db->query('SELECT COUNT(*) FROM chat_messages')->fetchColumn(),
            'today'=>(int)$db->query('SELECT COUNT(*) FROM chat_messages WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
            'users'=>(int)$db->query('SELECT COUNT(DISTINCT user_id) FROM chat_participants')->fetchColumn(),
        ];
        $conversations=$db->query("SELECT c.*,COUNT(DISTINCT p.user_id) participant_count,COUNT(DISTINCT m.id) message_count,MAX(m.created_at) latest_message_at,GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ' · ') participant_names,(SELECT message FROM chat_messages lm WHERE lm.conversation_id=c.id ORDER BY lm.id DESC LIMIT 1) last_message FROM chat_conversations c LEFT JOIN chat_participants p ON p.conversation_id=c.id LEFT JOIN users u ON u.id=p.user_id LEFT JOIN chat_messages m ON m.conversation_id=c.id GROUP BY c.id ORDER BY COALESCE(MAX(m.created_at),c.created_at) DESC LIMIT 200")->fetchAll();
        View::render('admin/chat',['title'=>'Discussion instantanée','active'=>'admin-chat','metrics'=>$metrics,'conversations'=>$conversations],'admin');
    }
    public function conversation(): void
    {
        $this->guard();$id=(int)($_GET['id']??0);$db=Database::connection();$st=$db->prepare('SELECT * FROM chat_conversations WHERE id=? LIMIT 1');$st->execute([$id]);$conversation=$st->fetch();if(!$conversation)$this->redirect('/admin/discussions');$m=$db->prepare("SELECT m.*,u.name sender_name,u.avatar sender_avatar FROM chat_messages m LEFT JOIN users u ON u.id=m.sender_id WHERE m.conversation_id=? ORDER BY m.id DESC LIMIT 150");$m->execute([$id]);$messages=array_reverse($m->fetchAll());View::render('admin/chat-conversation',['title'=>$conversation['title']?:'Conversation #'.$id,'active'=>'admin-chat','conversation'=>$conversation,'messages'=>$messages],'admin');
    }
    public function send(): void
    {
        $this->guard();$id=(int)($_POST['conversation_id']??0);$message=trim((string)($_POST['message']??''));if(!$id||$message==='')$this->redirect('/admin/discussion?id='.$id);$db=Database::connection();$st=$db->prepare('SELECT id FROM chat_conversations WHERE id=? LIMIT 1');$st->execute([$id]);if(!$st->fetchColumn())$this->redirect('/admin/discussions');$sender=(int)($_SESSION['user']['id']??0)?:null;$db->prepare("INSERT INTO chat_messages(conversation_id,sender_id,message,message_type) VALUES(?,?,?,'text')")->execute([$id,$sender,$message]);$db->prepare('UPDATE chat_conversations SET last_message_at=NOW() WHERE id=?')->execute([$id]);$this->redirect('/admin/discussion?id='.$id);
    }
}
