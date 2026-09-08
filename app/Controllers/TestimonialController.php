<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\R2Storage;

final class TestimonialController
{
    private const MAX_DURATION = 60;
    private const MAX_BYTES = 104857600;

    private function baseUrl(string $path): string
    {
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        return $base . $path;
    }

    private function learnerGuard(): array
    {
        if (empty($_SESSION['user']['id'])) {
            $_SESSION['intended_url'] = '/academie/temoignage';
            header('Location: ' . $this->baseUrl('/connexion'));
            exit;
        }
        if (($_SESSION['user']['role'] ?? '') === 'admin') {
            header('Location: ' . $this->baseUrl('/admin/temoignages'));
            exit;
        }
        $db = Database::connection();
        $stmt = $db->prepare("SELECT c.id,c.title FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.user_id=? AND e.status IN ('active','completed') ORDER BY e.enrolled_at DESC");
        $stmt->execute([(int) $_SESSION['user']['id']]);
        $courses = $stmt->fetchAll();
        if (!$courses) {
            $_SESSION['flash'] = 'Vous devez être inscrit à au moins une formation pour publier un témoignage.';
            header('Location: ' . $this->baseUrl('/academie'));
            exit;
        }
        return $courses;
    }

    private function adminGuard(): void
    {
        if (!($_SESSION['admin_authenticated'] ?? false)) {
            header('Location: ' . $this->baseUrl('/admin/connexion'));
            exit;
        }
    }

    public function create(): void
    {
        $courses = $this->learnerGuard();
        $db = Database::connection();
        $stmt = $db->prepare("SELECT vt.*,c.title course_title FROM video_testimonials vt LEFT JOIN courses c ON c.id=vt.course_id WHERE vt.user_id=? ORDER BY vt.id DESC LIMIT 1");
        $stmt->execute([(int) $_SESSION['user']['id']]);
        $latest = $stmt->fetch() ?: null;
        if ($latest && str_starts_with((string)$latest['video_path'], 'r2://')) {
            try { $latest['preview_url'] = (new R2Storage())->playbackUrl($latest['video_path']); } catch (\Throwable) { $latest['preview_url'] = null; }
        }
        View::render('testimonials/create', [
            'title' => 'Mon témoignage vidéo',
            'active' => 'testimonial',
            'courses' => $courses,
            'latest' => $latest,
            'maxDuration' => self::MAX_DURATION,
            'maxBytes' => self::MAX_BYTES,
        ]);
    }

    public function uploadUrl(): void
    {
        $this->learnerGuard();
        header('Content-Type: application/json; charset=utf-8');
        $payload = json_decode((string)file_get_contents('php://input'), true) ?: [];
        try {
            $size = (int)($payload['size'] ?? 0);
            if ($size < 1 || $size > self::MAX_BYTES) throw new \InvalidArgumentException('La vidéo ne doit pas dépasser 100 Mo.');
            $result = (new R2Storage())->createTestimonialUpload(
                (int)$_SESSION['user']['id'],
                (string)($payload['name'] ?? 'temoignage.webm'),
                (string)($payload['type'] ?? ''),
                $size
            );
            echo json_encode(['ok'=>true] + $result, JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function store(): void
    {
        $courses = $this->learnerGuard();
        $allowedCourseIds = array_map(fn($row)=>(int)$row['id'], $courses);
        $userId = (int)$_SESSION['user']['id'];
        $courseId = (int)($_POST['course_id'] ?? 0);
        $duration = (int)($_POST['duration_seconds'] ?? 0);
        $video = trim((string)($_POST['video_path'] ?? ''));
        $caption = mb_substr(trim((string)($_POST['caption'] ?? '')), 0, 500);
        if (!in_array($courseId, $allowedCourseIds, true) || $duration < 3 || $duration > self::MAX_DURATION || !str_starts_with($video, 'r2://testimonials/'.$userId.'/')) {
            $_SESSION['testimonial_error'] = 'Le témoignage est invalide. Enregistrez une vidéo de 3 à '.self::MAX_DURATION.' secondes.';
            header('Location: ' . $this->baseUrl('/academie/temoignage'));
            exit;
        }
        $db = Database::connection();
        $pending = $db->prepare("SELECT COUNT(*) FROM video_testimonials WHERE user_id=? AND status='pending'");
        $pending->execute([$userId]);
        if ((int)$pending->fetchColumn() > 0) {
            $_SESSION['testimonial_error'] = 'Vous avez déjà un témoignage en attente de validation.';
            header('Location: ' . $this->baseUrl('/academie/temoignage'));
            exit;
        }
        $stmt = $db->prepare("INSERT INTO video_testimonials(user_id,course_id,video_path,duration_seconds,caption,status) VALUES(?,?,?,?,?,'pending')");
        $stmt->execute([$userId,$courseId,$video,$duration,$caption ?: null]);
        $_SESSION['testimonial_flash'] = 'Votre témoignage a bien été envoyé. Il sera publié après validation par un administrateur.';
        header('Location: ' . $this->baseUrl('/academie/temoignage'));
        exit;
    }

    public function adminIndex(): void
    {
        $this->adminGuard();
        $rows = Database::connection()->query("SELECT vt.*,u.name,u.email,u.avatar,c.title course_title FROM video_testimonials vt JOIN users u ON u.id=vt.user_id LEFT JOIN courses c ON c.id=vt.course_id ORDER BY (vt.status='pending') DESC,vt.featured DESC,vt.id DESC")->fetchAll();
        $storage = new R2Storage();
        foreach ($rows as &$row) {
            $row['preview_url'] = null;
            if (str_starts_with((string)$row['video_path'], 'r2://')) {
                try { $row['preview_url'] = $storage->playbackUrl($row['video_path']); } catch (\Throwable) {}
            }
        }
        unset($row);
        View::render('admin/testimonials', ['title'=>'Témoignages vidéo','active'=>'admin-testimonials','testimonials'=>$rows], 'admin');
    }

    public function moderate(): void
    {
        $this->adminGuard();
        $id = (int)($_POST['id'] ?? 0);
        $action = (string)($_POST['action'] ?? '');
        $db = Database::connection();
        $adminId = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE video_testimonials SET status='approved',rejection_reason=NULL,approved_by=?,approved_at=NOW() WHERE id=?");
            $stmt->execute([$adminId,$id]);
            $_SESSION['flash'] = 'Témoignage publié sur le site.';
        } elseif ($action === 'reject') {
            $reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 500);
            $stmt = $db->prepare("UPDATE video_testimonials SET status='rejected',featured=0,rejection_reason=?,approved_by=NULL,approved_at=NULL WHERE id=?");
            $stmt->execute([$reason ?: 'Non retenu par la modération.',$id]);
            $_SESSION['flash'] = 'Témoignage rejeté.';
        } elseif ($action === 'unpublish') {
            $db->prepare("UPDATE video_testimonials SET status='pending',featured=0,approved_by=NULL,approved_at=NULL WHERE id=?")->execute([$id]);
            $_SESSION['flash'] = 'Témoignage retiré du site et replacé en attente.';
        } elseif ($action === 'feature') {
            $db->prepare("UPDATE video_testimonials SET featured=1 WHERE id=? AND status='approved'")->execute([$id]);
            $_SESSION['flash'] = 'Témoignage mis en avant.';
        } elseif ($action === 'unfeature') {
            $db->prepare("UPDATE video_testimonials SET featured=0 WHERE id=?")->execute([$id]);
            $_SESSION['flash'] = 'Mise en avant retirée.';
        }
        header('Location: ' . $this->baseUrl('/admin/temoignages'));
        exit;
    }
}
