<?php
namespace App\Controllers;

use App\Core\Database;

final class SiteChatController
{
    public function bootstrap(): void
    {
        $this->jsonHeaders();
        if(empty($_SESSION['user'])){$this->json(['authenticated'=>false,'login_url'=>'/connexion']);}
        try{
            $conversationId=$this->supportConversationId();
            $this->json([
                'authenticated'=>true,
                'conversation_id'=>$conversationId,
                'user'=>$this->publicUser(),
                'messages'=>$this->messages($conversationId,0),
            ]);
        }catch(\Throwable $e){$this->json(['authenticated'=>true,'error'=>'La discussion IFMAP est momentanément indisponible.'],500);}
    }

    public function messages(): void
    {
        $this->jsonHeaders();
        if(empty($_SESSION['user'])){$this->json(['authenticated'=>false],401);}
        try{
            $conversationId=$this->supportConversationId();
            $after=max(0,(int)($_GET['after']??0));
            $this->json(['authenticated'=>true,'messages'=>$this->messages($conversationId,$after)]);
        }catch(\Throwable){$this->json(['error'=>'Impossible de récupérer les messages.'],500);}
    }

    public function send(): void
    {
        $this->jsonHeaders();
        if(empty($_SESSION['user'])){$this->json(['authenticated'=>false,'error'=>'Connectez-vous pour discuter avec IFMAP.'],401);}
        try{
            $conversationId=$this->supportConversationId();
            $message=trim((string)($_POST['message']??''));
            $attachment=$this->storeAttachment($_FILES['attachment']??null);
            if($message===''&&!$attachment)$this->json(['error'=>'Écrivez un message ou joignez un fichier.'],422);
            if(mb_strlen($message)>4000)$this->json(['error'=>'Le message est trop long.'],422);

            $db=Database::connection();
            $stmt=$db->prepare("INSERT INTO chat_messages(conversation_id,sender_id,message,message_type,attachment_path) VALUES(?,?,?,?,?)");
            $stmt->execute([$conversationId,(int)$_SESSION['user']['id'],$message,$attachment?'file':'text',$attachment]);
            $id=(int)$db->lastInsertId();
            $db->prepare('UPDATE chat_conversations SET last_message_at=NOW() WHERE id=?')->execute([$conversationId]);
            $this->json(['ok'=>true,'messages'=>$this->messages($conversationId,$id-1)]);
        }catch(\RuntimeException $e){$this->json(['error'=>$e->getMessage()],422);}
        catch(\Throwable){$this->json(['error'=>'Le message n’a pas pu être envoyé.'],500);}
    }

    private function supportConversationId(): int
    {
        $userId=(int)($_SESSION['user']['id']??0);
        if(!$userId)throw new \RuntimeException('Utilisateur non connecté.');
        $db=Database::connection();
        $stmt=$db->prepare("SELECT c.id FROM chat_conversations c JOIN chat_participants p ON p.conversation_id=c.id WHERE c.type='support' AND c.created_by=? AND p.user_id=? ORDER BY c.id DESC LIMIT 1");
        $stmt->execute([$userId,$userId]);
        if($id=(int)$stmt->fetchColumn())return $id;

        $db->beginTransaction();
        try{
            $title='Assistance IFMAP · '.trim((string)($_SESSION['user']['name']??('Utilisateur #'.$userId)));
            $stmt=$db->prepare("INSERT INTO chat_conversations(type,title,created_by,last_message_at) VALUES('support',?,?,NOW())");
            $stmt->execute([$title,$userId]);
            $id=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO chat_participants(conversation_id,user_id) VALUES(?,?)')->execute([$id,$userId]);
            $welcome='Bonjour '.trim((string)($_SESSION['user']['name']??'')).', bienvenue sur la discussion IFMAP. Un conseiller peut reprendre cette conversation depuis l’administration.';
            $db->prepare("INSERT INTO chat_messages(conversation_id,sender_id,message,message_type) VALUES(?,NULL,?,'system')")->execute([$id,$welcome]);
            $db->commit();
            return $id;
        }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }

    private function messages(int $conversationId,int $after): array
    {
        $db=Database::connection();
        $stmt=$db->prepare("SELECT m.id,m.sender_id,m.message,m.message_type,m.attachment_path,m.created_at,u.name sender_name,u.avatar sender_avatar FROM chat_messages m LEFT JOIN users u ON u.id=m.sender_id WHERE m.conversation_id=? AND m.id>? ORDER BY m.id ASC LIMIT 100");
        $stmt->execute([$conversationId,$after]);
        $current=(int)($_SESSION['user']['id']??0);
        return array_map(function(array $row)use($current):array{
            $row['id']=(int)$row['id'];
            $row['mine']=(int)($row['sender_id']??0)===$current;
            $row['attachment_url']=$row['attachment_path']?:null;
            $row['attachment_name']=$row['attachment_path']?basename((string)$row['attachment_path']):null;
            unset($row['attachment_path']);
            return $row;
        },$stmt->fetchAll());
    }

    private function storeAttachment(?array $file): ?string
    {
        if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
        if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK)throw new \RuntimeException('Le fichier n’a pas pu être téléversé.');
        if((int)($file['size']??0)>10*1024*1024)throw new \RuntimeException('La pièce jointe ne doit pas dépasser 10 Mo.');
        $tmp=(string)($file['tmp_name']??'');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'';
        $allowed=[
            'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif',
            'application/pdf'=>'pdf','text/plain'=>'txt','application/msword'=>'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',
            'application/vnd.ms-excel'=>'xls','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'=>'xlsx',
            'application/zip'=>'zip'
        ];
        if(!isset($allowed[$mime]))throw new \RuntimeException('Type de fichier non autorisé. Images, PDF, documents Office, texte et ZIP sont acceptés.');
        $root=dirname(__DIR__,2).'/public/uploads/chat';
        if(!is_dir($root)&&!mkdir($root,0775,true)&&!is_dir($root))throw new \RuntimeException('Le dossier de discussion est indisponible.');
        $name=date('YmdHis').'-'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
        if(!move_uploaded_file($tmp,$root.'/'.$name))throw new \RuntimeException('Le fichier n’a pas pu être enregistré.');
        return '/public/uploads/chat/'.$name;
    }

    private function publicUser(): array
    {
        $u=$_SESSION['user']??[];
        return ['id'=>(int)($u['id']??0),'name'=>(string)($u['name']??'Utilisateur'),'avatar'=>$u['avatar']??null];
    }
    private function jsonHeaders(): void { header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store'); }
    private function json(array $data,int $status=200): never { http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit; }
}
