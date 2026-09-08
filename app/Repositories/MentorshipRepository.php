<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

final class MentorshipRepository
{
    private PDO $db;
    public function __construct(){ $this->db=Database::connection(); }
    public function mentors(): array { return $this->db->query("SELECT u.id,u.name,u.avatar,u.specialty,mp.headline,mp.bio,mp.years_experience,mp.hourly_rate,mp.rating_avg,mp.rating_count,(SELECT GROUP_CONCAT(skill_name ORDER BY verified DESC,skill_name SEPARATOR ' • ') FROM mentor_skills ms WHERE ms.mentor_id=u.id) skills FROM mentor_profiles mp JOIN users u ON u.id=mp.user_id WHERE mp.dossier_status='approved' ORDER BY mp.rating_avg DESC,mp.rating_count DESC,u.name")->fetchAll(); }
    public function mentor(int $id): ?array { $st=$this->db->prepare("SELECT u.id,u.name,u.email,u.phone,u.avatar,u.specialty,u.bio user_bio,mp.* FROM mentor_profiles mp JOIN users u ON u.id=mp.user_id WHERE u.id=? LIMIT 1");$st->execute([$id]);$r=$st->fetch()?:null;if($r){$s=$this->db->prepare('SELECT * FROM mentor_skills WHERE mentor_id=? ORDER BY verified DESC,skill_name');$s->execute([$id]);$r['skills']=$s->fetchAll();}return $r; }
    public function learnerRequests(int $userId): array { $st=$this->db->prepare("SELECT r.*,u.name mentor_name,u.avatar mentor_avatar,cs.id coaching_id,cs.room_url,cs.status coaching_status FROM mentorship_requests r JOIN users u ON u.id=r.mentor_id LEFT JOIN coaching_sessions cs ON cs.request_id=r.id WHERE r.learner_id=? ORDER BY r.id DESC");$st->execute([$userId]);return $st->fetchAll(); }
    public function mentorRequests(int $mentorId): array { $st=$this->db->prepare("SELECT r.*,u.name learner_name,u.email learner_email,u.avatar learner_avatar,cs.id coaching_id,cs.room_url,cs.status coaching_status FROM mentorship_requests r JOIN users u ON u.id=r.learner_id LEFT JOIN coaching_sessions cs ON cs.request_id=r.id WHERE r.mentor_id=? ORDER BY COALESCE(r.scheduled_at,r.created_at) DESC");$st->execute([$mentorId]);return $st->fetchAll(); }
    public function adminMentors(): array { return $this->db->query("SELECT u.id,u.name,u.email,u.phone,u.avatar,mp.*,(SELECT COUNT(*) FROM mentor_documents d WHERE d.mentor_id=u.id) document_count,(SELECT COUNT(*) FROM mentor_skills s WHERE s.mentor_id=u.id AND s.verified=1) verified_skills FROM mentor_profiles mp JOIN users u ON u.id=mp.user_id ORDER BY FIELD(mp.dossier_status,'submitted','review','interview','draft','approved','rejected','suspended'),mp.updated_at DESC")->fetchAll(); }
    public function adminRequests(): array { return $this->db->query("SELECT r.*,l.name learner_name,m.name mentor_name,cs.provider,cs.room_url FROM mentorship_requests r JOIN users l ON l.id=r.learner_id JOIN users m ON m.id=r.mentor_id LEFT JOIN coaching_sessions cs ON cs.request_id=r.id ORDER BY r.id DESC LIMIT 200")->fetchAll(); }
    public function request(int $id): ?array { $st=$this->db->prepare("SELECT r.*,l.name learner_name,l.email learner_email,l.phone learner_phone,m.name mentor_name,m.email mentor_email,m.phone mentor_phone FROM mentorship_requests r JOIN users l ON l.id=r.learner_id JOIN users m ON m.id=r.mentor_id WHERE r.id=? LIMIT 1");$st->execute([$id]);return $st->fetch()?:null; }
}
