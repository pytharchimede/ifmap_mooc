<?php
namespace App\Controllers;
use App\Core\Database;
use App\Core\View;

final class AcademyController
{
    private function user(): array { if(empty($_SESSION['user']))$this->redirect('/connexion');return $_SESSION['user']; }
    public function course(): void
    {
        $user=$this->user();$courseId=max(1,(int)($_GET['course']??1));$db=Database::connection();
        $course=$db->query('SELECT * FROM courses WHERE id='.$courseId)->fetch();if(!$course){http_response_code(404);return;}
        $stmt=$db->prepare('SELECT * FROM enrollments WHERE user_id=? AND course_id=?');$stmt->execute([$user['id'],$courseId]);$enrollment=$stmt->fetch();
        if(!$enrollment&&($user['role']??'')!=='admin'){$_SESSION['flash']='Vous devez être inscrit à cette formation.';$this->redirect('/formations');}
        if(($user['role']??'')!=='admin'){$required=$db->query("SELECT prerequisite_course_id FROM course_prerequisites WHERE course_id=$courseId AND type='course'")->fetchAll(\PDO::FETCH_COLUMN);foreach($required as $requiredId){$check=$db->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=? AND completed_at IS NOT NULL');$check->execute([$user['id'],$requiredId]);if(!$check->fetchColumn()){$_SESSION['flash']='Une formation préalable doit être terminée avant d’accéder à ce cours.';$this->redirect('/mon-compte');}}}
        $modules=$db->query('SELECT * FROM modules WHERE course_id='.$courseId.' ORDER BY position,id')->fetchAll();
        foreach($modules as &$module){$module['lessons']=$db->query('SELECT * FROM lessons WHERE module_id='.(int)$module['id'].' ORDER BY position,id')->fetchAll();$module['assessment']=$db->query("SELECT * FROM assessments WHERE module_id=".(int)$module['id']." AND status='published' LIMIT 1")->fetch()?:null;}
        $final=$db->query("SELECT * FROM assessments WHERE course_id=$courseId AND type='final_exam' AND status='published' LIMIT 1")->fetch()?:null;
        View::render('academy/course',['title'=>$course['title'],'active'=>'courses','course'=>$course,'modules'=>$modules,'final'=>$final,'enrollment'=>$enrollment],'app');
    }
    public function assessment(): void
    {
        $user=$this->user();$id=(int)($_GET['id']??0);$db=Database::connection();$assessment=$db->query('SELECT a.*,c.title course_title FROM assessments a JOIN courses c ON c.id=a.course_id WHERE a.id='.$id." AND a.status='published'")->fetch();if(!$assessment){http_response_code(404);return;}$questions=$db->query('SELECT id,question,options,points FROM questions WHERE assessment_id='.$id.' ORDER BY position,id')->fetchAll();View::render('academy/assessment',['title'=>$assessment['title'],'active'=>'courses','assessment'=>$assessment,'questions'=>$questions],'app');
    }
    public function lesson(): void
    {
        $user=$this->user();$id=(int)($_GET['id']??0);$db=Database::connection();$stmt=$db->prepare('SELECT l.*,m.course_id,m.title module_title,c.title course_title FROM lessons l JOIN modules m ON m.id=l.module_id JOIN courses c ON c.id=m.course_id WHERE l.id=?');$stmt->execute([$id]);$lesson=$stmt->fetch();if(!$lesson){http_response_code(404);return;}$access=$db->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?');$access->execute([$user['id'],$lesson['course_id']]);if(!$access->fetchColumn()&&($user['role']??'')!=='admin'){http_response_code(403);return;}View::render('academy/lesson',['title'=>$lesson['title'],'active'=>'courses','lesson'=>$lesson],'app');
    }
    public function completeLesson(): void
    {
        $user=$this->user();$id=(int)($_POST['lesson_id']??0);$courseId=(int)($_POST['course_id']??0);$db=Database::connection();$stmt=$db->prepare('INSERT IGNORE INTO lesson_progress(user_id,lesson_id) VALUES(?,?)');$stmt->execute([$user['id'],$id]);$total=(int)$db->query('SELECT COUNT(*) FROM lessons l JOIN modules m ON m.id=l.module_id WHERE m.course_id='.$courseId)->fetchColumn();$done=$db->prepare('SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id=lp.lesson_id JOIN modules m ON m.id=l.module_id WHERE lp.user_id=? AND m.course_id=?');$done->execute([$user['id'],$courseId]);$progress=$total?(int)floor((int)$done->fetchColumn()/$total*90):0;$db->prepare('UPDATE enrollments SET progress=? WHERE user_id=? AND course_id=?')->execute([$progress,$user['id'],$courseId]);$this->redirect('/academie/formation?course='.$courseId);
    }
    public function submitAssessment(): void
    {
        $user=$this->user();$id=(int)($_POST['assessment_id']??0);$db=Database::connection();$assessment=$db->query('SELECT * FROM assessments WHERE id='.$id)->fetch();if(!$assessment)$this->redirect('/academie');$questions=$db->query('SELECT * FROM questions WHERE assessment_id='.$id)->fetchAll();$earned=0;$total=0;$answers=$_POST['answers']??[];foreach($questions as $q){$total+=(int)$q['points'];if((string)($answers[$q['id']]??'')===(string)$q['correct_answer'])$earned+=(int)$q['points'];}$score=$total?round($earned/$total*100):0;$passed=$score>=(int)$assessment['passing_score'];$stmt=$db->prepare('INSERT INTO assessment_attempts(assessment_id,user_id,score,passed,answers,completed_at) VALUES(?,?,?,?,?,NOW())');$stmt->execute([$id,$user['id'],$score,$passed?1:0,json_encode($answers)]);if($passed&&$assessment['type']==='final_exam')$this->issueCertificate((int)$assessment['course_id'],(int)$user['id'],$score);View::render('academy/result',['title'=>'Résultat','active'=>'courses','assessment'=>$assessment,'score'=>$score,'passed'=>$passed],'app');
    }
    public function certificate(): void
    {
        $user=$this->user();$id=(int)($_GET['id']??0);$stmt=Database::connection()->prepare('SELECT ce.*,c.title course_title,u.name user_name FROM certificates ce JOIN courses c ON c.id=ce.course_id JOIN users u ON u.id=ce.user_id WHERE ce.id=? AND (ce.user_id=? OR ?=\'admin\')');$stmt->execute([$id,$user['id'],$user['role']??'']);$certificate=$stmt->fetch();if(!$certificate){http_response_code(403);return;}View::render('academy/certificate',['title'=>'Diplôme IFMAP','certificate'=>$certificate],'certificate');
    }
    private function issueCertificate(int $courseId,int $userId,int $score): void { $db=Database::connection();$code='IFMAP-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));$stmt=$db->prepare('INSERT INTO certificates(user_id,course_id,reference,verification_code,final_score) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE final_score=GREATEST(COALESCE(final_score,0),VALUES(final_score))');$stmt->execute([$userId,$courseId,$code,$code,$score]);$db->prepare('UPDATE enrollments SET progress=100,completed_at=NOW() WHERE user_id=? AND course_id=?')->execute([$userId,$courseId]);}
    private function redirect(string $path): never {$base=rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');header('Location: '.$base.$path);exit;}
}
