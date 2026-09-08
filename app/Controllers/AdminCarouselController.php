<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\SecureSettings;

final class AdminCarouselController
{
    private function guard(): void
    {
        if (empty($_SESSION['admin_authenticated'])) $this->redirect('/admin/connexion');
    }

    public function index(): void
    {
        $this->guard();
        $db = Database::connection();
        $slides = $db->query('SELECT * FROM home_carousel_slides ORDER BY position,id')->fetchAll();
        $settings = (new SecureSettings())->group('homepage');
        View::render('admin/carousel', [
            'title' => 'Carrousel de la page d’accueil',
            'active' => 'admin-carousel',
            'slides' => $slides,
            'settings' => $settings,
            'flash' => $_SESSION['flash'] ?? null,
            'error' => $_SESSION['carousel_error'] ?? null,
        ], 'admin');
        unset($_SESSION['flash'], $_SESSION['carousel_error']);
    }

    public function save(): void
    {
        $this->guard();
        $id = max(0, (int)($_POST['id'] ?? 0));
        $position = max(0, (int)($_POST['position'] ?? 0));
        $eyebrow = mb_substr(trim((string)($_POST['eyebrow'] ?? '')), 0, 190);
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 255);
        $highlight = mb_substr(trim((string)($_POST['highlighted_text'] ?? '')), 0, 190);
        $description = trim((string)($_POST['description'] ?? ''));
        $primaryLabel = mb_substr(trim((string)($_POST['primary_label'] ?? '')), 0, 120);
        $primaryUrl = $this->safeUrl((string)($_POST['primary_url'] ?? ''));
        $secondaryLabel = mb_substr(trim((string)($_POST['secondary_label'] ?? '')), 0, 120);
        $secondaryUrl = $this->safeUrl((string)($_POST['secondary_url'] ?? ''));
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($eyebrow === '' || $title === '' || $primaryLabel === '' || $primaryUrl === '') {
            $_SESSION['carousel_error'] = 'Le surtitre, le titre et le bouton principal avec son lien sont obligatoires.';
            $this->redirect('/admin/carrousel');
        }

        $db = Database::connection();
        $existing = null;
        if ($id) {
            $st = $db->prepare('SELECT * FROM home_carousel_slides WHERE id=? LIMIT 1');
            $st->execute([$id]);
            $existing = $st->fetch() ?: null;
        }

        try {
            $imagePath = $this->storeImage($_FILES['image'] ?? null, $existing['image_path'] ?? null);
            $stats = [];
            for ($i = 1; $i <= 3; $i++) {
                $stats[] = mb_substr(trim((string)($_POST['stat_'.$i.'_value'] ?? '')), 0, 60);
                $stats[] = mb_substr(trim((string)($_POST['stat_'.$i.'_label'] ?? '')), 0, 120);
            }

            $data = [
                $position, $eyebrow, $title, $highlight ?: null, $description ?: null, $imagePath,
                $primaryLabel, $primaryUrl, $secondaryLabel ?: null, $secondaryUrl ?: null,
                ...$stats, $status,
            ];

            if ($id && $existing) {
                $sql = 'UPDATE home_carousel_slides SET position=?,eyebrow=?,title=?,highlighted_text=?,description=?,image_path=?,primary_label=?,primary_url=?,secondary_label=?,secondary_url=?,stat_1_value=?,stat_1_label=?,stat_2_value=?,stat_2_label=?,stat_3_value=?,stat_3_label=?,status=? WHERE id=?';
                $db->prepare($sql)->execute([...$data, $id]);
                $_SESSION['flash'] = 'Slide mise à jour.';
            } else {
                $sql = 'INSERT INTO home_carousel_slides(position,eyebrow,title,highlighted_text,description,image_path,primary_label,primary_url,secondary_label,secondary_url,stat_1_value,stat_1_label,stat_2_value,stat_2_label,stat_3_value,stat_3_label,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                $db->prepare($sql)->execute($data);
                $_SESSION['flash'] = 'Nouvelle slide ajoutée.';
            }
        } catch (\RuntimeException $e) {
            $_SESSION['carousel_error'] = $e->getMessage();
        }
        $this->redirect('/admin/carrousel');
    }

    public function delete(): void
    {
        $this->guard();
        $id = max(0, (int)($_POST['id'] ?? 0));
        if ($id) {
            $db = Database::connection();
            $st = $db->prepare('SELECT image_path FROM home_carousel_slides WHERE id=?');
            $st->execute([$id]);
            $image = $st->fetchColumn();
            $db->prepare('DELETE FROM home_carousel_slides WHERE id=?')->execute([$id]);
            if ($image && str_starts_with((string)$image, '/public/uploads/carousel/')) {
                $file = dirname(__DIR__, 2).(string)$image;
                if (is_file($file)) @unlink($file);
            }
            $_SESSION['flash'] = 'Slide supprimée.';
        }
        $this->redirect('/admin/carrousel');
    }

    public function settings(): void
    {
        $this->guard();
        $settings = new SecureSettings();
        $settings->set('home_carousel_enabled', !empty($_POST['enabled']) ? '1' : '0', 'homepage');
        $delay = max(3000, min(20000, (int)($_POST['autoplay_ms'] ?? 6500)));
        $settings->set('home_carousel_autoplay_ms', (string)$delay, 'homepage');
        $_SESSION['flash'] = 'Paramètres du carrousel enregistrés.';
        $this->redirect('/admin/carrousel');
    }

    private function storeImage(?array $file, ?string $current): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $current;
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new \RuntimeException('Le téléversement de l’image a échoué.');
        if ((int)($file['size'] ?? 0) > 8 * 1024 * 1024) throw new \RuntimeException('L’image ne doit pas dépasser 8 Mo.');
        $tmp = (string)($file['tmp_name'] ?? '');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($allowed[$mime])) throw new \RuntimeException('Format non autorisé. Utilisez JPG, PNG ou WebP.');
        $dir = dirname(__DIR__, 2).'/public/uploads/carousel';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new \RuntimeException('Impossible de préparer le dossier des images.');
        $name = date('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$allowed[$mime];
        if (!move_uploaded_file($tmp, $dir.'/'.$name)) throw new \RuntimeException('Impossible d’enregistrer l’image.');
        if ($current && str_starts_with($current, '/public/uploads/carousel/')) {
            $old = dirname(__DIR__, 2).$current;
            if (is_file($old)) @unlink($old);
        }
        return '/public/uploads/carousel/'.$name;
    }

    private function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (str_starts_with($url, '/')) return $url;
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    private function redirect(string $path): never
    {
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        header('Location: '.$base.$path);
        exit;
    }
}
