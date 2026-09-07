<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class BrandingController
{
    private function baseUrl(string $path): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(str_replace('/index.php', '', $script), '/');
        return $base . '/' . ltrim($path, '/');
    }

    private function guard(): void
    {
        if (!($_SESSION['admin_authenticated'] ?? false)) {
            header('Location: ' . $this->baseUrl('/admin/connexion'));
            exit;
        }
    }

    private function brand(): array
    {
        $brand = [
            'name' => 'IFMAP Learning',
            'primary' => '#5547e8',
            'accent' => '#f59e0b',
            'logo' => null,
            'favicon' => null,
            'signature' => null,
        ];
        try {
            foreach (Database::connection()->query("SELECT `key`,`value` FROM settings WHERE `group`='branding'") as $row) {
                if (array_key_exists($row['key'], $brand)) {
                    $brand[$row['key']] = $row['value'];
                }
            }
        } catch (\Throwable) {
        }
        return $brand;
    }

    public function edit(): void
    {
        $this->guard();
        $brand = $this->brand();
        $_SESSION['brand'] = $brand;
        View::render('admin/branding', [
            'title' => 'Identité visuelle',
            'active' => 'admin-branding',
            'brand' => $brand,
        ], 'admin');
    }

    public function save(): void
    {
        $this->guard();
        $current = $this->brand();
        $directory = dirname(__DIR__, 2) . '/public/uploads/branding';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $_SESSION['flash'] = 'Impossible de créer le dossier du branding.';
            $this->redirect();
        }

        try {
            $logo = $this->store('logo', [
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            ], 2 * 1024 * 1024, $directory) ?: $current['logo'];
            $favicon = $this->store('favicon', [
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico',
            ], 1024 * 1024, $directory) ?: $current['favicon'];
            $signature = $this->store('signature', [
                'image/jpeg' => 'jpg', 'image/png' => 'png',
            ], 2 * 1024 * 1024, $directory) ?: $current['signature'];
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
            $this->redirect();
        }

        $brand = [
            'name' => trim((string)($_POST['name'] ?? 'IFMAP Learning')) ?: 'IFMAP Learning',
            'primary' => preg_match('/^#[0-9a-f]{6}$/i', (string)($_POST['primary'] ?? '')) ? $_POST['primary'] : '#5547e8',
            'accent' => preg_match('/^#[0-9a-f]{6}$/i', (string)($_POST['accent'] ?? '')) ? $_POST['accent'] : '#f59e0b',
            'logo' => $logo,
            'favicon' => $favicon,
            'signature' => $signature,
        ];

        $stmt = Database::connection()->prepare("INSERT INTO settings(`key`,`value`,`group`) VALUES(?,?,'branding') ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        foreach ($brand as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        $_SESSION['brand'] = $brand;
        $_SESSION['flash'] = 'Identité visuelle enregistrée et appliquée à la plateforme.';
        $this->redirect();
    }

    private function store(string $field, array $allowed, int $maxBytes, string $directory): ?string
    {
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return null;
        }
        $file = $_FILES[$field];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowed[$mime]) || (int)$file['size'] > $maxBytes) {
            throw new \RuntimeException('Le fichier « '.$field.' » est invalide ou trop volumineux.');
        }
        $filename = $field . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            throw new \RuntimeException('Impossible d’enregistrer le fichier « '.$field.' ».');
        }
        return '/public/uploads/branding/' . $filename;
    }

    private function redirect(): never
    {
        header('Location: ' . $this->baseUrl('/admin/branding'));
        exit;
    }
}
