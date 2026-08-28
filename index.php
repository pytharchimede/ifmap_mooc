<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Router;

if (PHP_SAPI === 'cli') {
    $sessionPath = __DIR__ . '/storage/sessions';
    if (!is_dir($sessionPath)) mkdir($sessionPath, 0775, true);
    session_save_path($sessionPath);
}
session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require $path;
});

Env::load(__DIR__ . '/.env');
App\Core\Bootstrap::run(__DIR__);

$router = new Router();
require __DIR__ . '/routes/web.php';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');
if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}

ob_start();
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
$html = ob_get_clean();
if (!empty($_SESSION['user']['name'])) {
    $viewerName = htmlspecialchars((string)$_SESSION['user']['name'], ENT_QUOTES, 'UTF-8');
    $firstName = htmlspecialchars(explode(' ', (string)$_SESSION['user']['name'])[0], ENT_QUOTES, 'UTF-8');
    $parts = preg_split('/\s+/', trim((string)$_SESSION['user']['name'])) ?: [];
    $initials = htmlspecialchars(strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[count($parts)-1] ?? '', 0, 1)), ENT_QUOTES, 'UTF-8');
    $html = str_replace(['Assa Kouamé', 'Bonjour Assa', '>AK<'], [$viewerName, 'Bonjour ' . $firstName, '>' . $initials . '<'], $html);
    if (!empty($_SESSION['user']['avatar'])) {
        $avatarUrl = htmlspecialchars((string)$_SESSION['user']['avatar'], ENT_QUOTES, 'UTF-8');
        $html = str_replace('>' . $initials . '</span>', '><img src="' . $avatarUrl . '" alt="Photo de profil" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"></span>', $html);
    }
}
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/functional.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/modern-forms.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/builder-media.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/rich-editor.css"></head>', $html);
$html = str_replace('</body>', '<script src="/public/assets/js/functional.js"></script></body>', $html);
if ($basePath !== '') {
    $html = str_replace(
        ['href="/', 'src="/', 'action="/'],
        ['href="' . $basePath . '/', 'src="' . $basePath . '/', 'action="' . $basePath . '/'],
        $html
    );
}
echo $html;
