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

    private function ensureSettingsSchema(): void
    {
        $db = Database::connection();
        $db->exec("CREATE TABLE IF NOT EXISTS settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(120) NOT NULL,
            `value` LONGTEXT NULL,
            `group` VARCHAR(80) NOT NULL DEFAULT 'general',
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY settings_group_key_unique (`group`,`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        try {
            $columns = [];
            foreach ($db->query('SHOW COLUMNS FROM settings') as $column) {
                $columns[$column['Field']] = $column;
            }
            if (!isset($columns['value'])) {
                $db->exec("ALTER TABLE settings ADD COLUMN `value` LONGTEXT NULL AFTER `key`");
            } elseif (!str_contains(strtolower((string)$columns['value']['Type']), 'longtext')) {
                $db->exec("ALTER TABLE settings MODIFY COLUMN `value` LONGTEXT NULL");
            }
            if (!isset($columns['group'])) {
                $db->exec("ALTER TABLE settings ADD COLUMN `group` VARCHAR(80) NOT NULL DEFAULT 'general' AFTER `value`");
            }
        } catch (\Throwable $e) {
            error_log('Branding schema check: ' . $e->getMessage());
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
            $this->ensureSettingsSchema();
            foreach (Database::connection()->query("SELECT `key`,`value` FROM settings WHERE `group`='branding'") as $row) {
                if (array_key_exists($row['key'], $brand)) {
                    $brand[$row['key']] = $row['value'];
                }
            }
        } catch (\Throwable $e) {
            error_log('Branding read: ' . $e->getMessage());
        }
        return $brand;
    }

    private function brandingDirectory(): ?string
    {
        $root = dirname(__DIR__, 2);
        $uploads = $root . '/public/uploads';
        $directory = $uploads . '/branding';

        foreach ([$uploads, $directory] as $path) {
            if (!is_dir($path)) @mkdir($path, 0775, true);
            if (is_dir($path) && !is_writable($path)) @chmod($path, 0775);
            if (is_dir($path) && !is_writable($path)) @chmod($path, 0777);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            error_log('Branding storage: public/uploads/branding is not writable, database fallback enabled.');
            return null;
        }

        $probe = $directory . '/.write-test-' . bin2hex(random_bytes(4));
        if (@file_put_contents($probe, 'ok', LOCK_EX) === false) {
            error_log('Branding storage: write test failed, database fallback enabled.');
            return null;
        }
        @unlink($probe);
        return $directory;
    }

    public function edit(): void
    {
        $this->guard();
        $this->brandingDirectory();
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
        $directory = $this->brandingDirectory();

        try {
            $logo = $this->store('logo', [
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            ], 2 * 1024 * 1024, $directory) ?: $current['logo'];
            $favicon = $this->store('favicon', [
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico',
            ], 1024 * 1024, $directory) ?: $current['favicon'];
            $signature = $this->store('signature', [
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
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

        try {
            $this->ensureSettingsSchema();
            $db = Database::connection();
            $update = $db->prepare("UPDATE settings SET `value`=? WHERE `group`='branding' AND `key`=?");
            $insert = $db->prepare("INSERT INTO settings(`key`,`value`,`group`) VALUES(?,?,'branding')");
            foreach ($brand as $key => $value) {
                $update->execute([$value, $key]);
                if ($update->rowCount() === 0) {
                    try {
                        $insert->execute([$key, $value]);
                    } catch (\Throwable) {
                        $update->execute([$value, $key]);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('Branding save database: ' . $e->getMessage());
            $_SESSION['flash'] = 'Le branding n’a pas pu être enregistré dans la base de données.';
            $this->redirect();
        }

        $_SESSION['brand'] = $brand;
        $_SESSION['flash'] = $directory === null
            ? 'Identité visuelle enregistrée et appliquée. Le serveur bloquant le dossier uploads, les images sont stockées automatiquement en base de données.'
            : 'Identité visuelle enregistrée et appliquée à la plateforme.';
        $this->redirect();
    }

    private function store(string $field, array $allowed, int $maxBytes, ?string $directory): ?string
    {
        if (!isset($_FILES[$field])) return null;

        $file = $_FILES[$field];
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) return null;
        if ($error !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'dépasse la taille autorisée par PHP',
                UPLOAD_ERR_FORM_SIZE => 'dépasse la taille autorisée par le formulaire',
                UPLOAD_ERR_PARTIAL => 'n’a été reçu que partiellement',
                UPLOAD_ERR_NO_TMP_DIR => 'ne peut pas être traité car le dossier temporaire PHP manque',
                UPLOAD_ERR_CANT_WRITE => 'ne peut pas être écrit dans le dossier temporaire PHP',
                UPLOAD_ERR_EXTENSION => 'a été bloqué par une extension PHP',
            ];
            throw new \RuntimeException('Le fichier « ' . $field . ' » ' . ($messages[$error] ?? 'n’a pas pu être téléversé') . '.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Le fichier temporaire « ' . $field . ' » est introuvable ou invalide.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new \RuntimeException('Le fichier « ' . $field . ' » est vide ou trop volumineux.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!is_string($mime) || !isset($allowed[$mime])) {
            throw new \RuntimeException('Le format du fichier « ' . $field . ' » n’est pas accepté.');
        }

        if ($directory !== null) {
            $filename = $field . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
            $target = $directory . '/' . $filename;
            if (@move_uploaded_file($tmp, $target)) {
                @chmod($target, 0644);
                if (is_file($target) && filesize($target) > 0) {
                    return '/public/uploads/branding/' . $filename;
                }
                @unlink($target);
            }
        }

        /* Filesystem unavailable: keep the validated image directly in settings.value. */
        $binary = @file_get_contents($tmp);
        if (!is_string($binary) || $binary === '') {
            throw new \RuntimeException('Impossible de lire le fichier « ' . $field . ' » pour le stockage de secours.');
        }
        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function redirect(): never
    {
        header('Location: ' . $this->baseUrl('/admin/branding'));
        exit;
    }
}
