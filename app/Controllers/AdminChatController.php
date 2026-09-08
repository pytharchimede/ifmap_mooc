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
        $conversations=$db->query("SELECT c.*,COUNT(DISTINCT p.user_id) participant_count,COUNT(DISTINCT m.id) message_count,MAX(m.created_at) latest_message_at,GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ' · ') participant_names FROM chat_conversations c LEFT JOIN chat_participants p ON p.conversation_id=c.id LEFT JOIN users u ON u.id=p.user_id LEFT JOIN chat_messages m ON m.conversation_id=c.id GROUP BY c.id ORDER BY COALESCE(MAX(m.created_at),c.created_at) DESC LIMIT 200")->fetchAll();
        View::render('admin/chat',['title'=>'Discussion instantanée','active'=>'admin-chat','metrics'=>$metrics,'conversations'=>$conversations],'admin');
    }
}
