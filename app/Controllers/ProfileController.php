<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class ProfileController
{
    public function show(): void
    {
        $this->guard();
        $this->redirect('/academie#profil');
    }

    public function save(): void
    {
        $this->guard();
        $userId = (int) $_SESSION['user']['id'];
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $currentRole = (string) ($_SESSION['user']['role'] ?? 'learner');
        if (in_array($currentRole, ['admin','mentor'], true)) {
            $role = $currentRole;
        } else {
            $role = (($_POST['role'] ?? '') === 'instructor') ? 'instructor' : 'learner';
        }

        if ($name === '') {
            $_SESSION['profile_error'] = 'Le nom est obligatoire.';
            $this->redirect('/academie#profil');
        }

        $db = Database::connection();
        $stmt = $db->prepare('SELECT avatar,cv_path FROM users WHERE id=?');
        $stmt->execute([$userId]);
        $current = $stmt->fetch() ?: [];
        $avatar = $this->upload($userId, 'avatar', ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'], 5, 'avatars', $current['avatar'] ?? null);
        $cv = $this->upload($userId, 'cv', ['application/pdf' => 'pdf', 'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'], 10, 'cvs', $current['cv_path'] ?? null);

        $stmt = $db->prepare('UPDATE users SET name=?,phone=?,specialty=?,bio=?,avatar=?,cv_path=?,role=? WHERE id=?');
        $stmt->execute([$name, $phone, $specialty, $bio, $avatar, $cv, $role, $userId]);
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['avatar'] = $avatar;
        $_SESSION['user']['role'] = $role;
        $_SESSION['profile_flash'] = 'Profil mis à jour.';
        $this->redirect('/academie#profil');
    }

    private function upload(int $userId, string $field, array $allowed, int $maxSize, string $directoryName, ?string $current): ?string
    {
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return $current;
        }

        $file = $_FILES[$field];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowed[$mime]) || (int) $file['size'] > $maxSize * 1024 * 1024) {
            $_SESSION['profile_error'] = $field === 'cv'
                ? 'CV invalide. Utilisez un fichier PDF, DOC ou DOCX de 10 Mo maximum.'
                : 'Photo invalide. Utilisez JPG, PNG ou WebP, 5 Mo maximum.';
            $this->redirect('/academie#profil');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/' . $directoryName;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $_SESSION['profile_error'] = 'Impossible de créer le dossier du fichier.';
            $this->redirect('/academie#profil');
        }

        $filename = $field . '-' . $userId . '-' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            $_SESSION['profile_error'] = 'Impossible d’enregistrer le fichier.';
            $this->redirect('/academie#profil');
        }

        return '/public/uploads/' . $directoryName . '/' . $filename;
    }

    private function guard(): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect('/connexion');
        }
    }

    private function redirect(string $path): never
    {
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        header('Location: ' . $base . $path);
        exit;
    }
}
