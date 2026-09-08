<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Router;
use App\Services\VisitorTracker;
use App\Services\SeoManager;

if (PHP_SAPI === 'cli') {
    $sessionPath = __DIR__ . '/storage/sessions';
    if (!is_dir($sessionPath)) mkdir($sessionPath, 0775, true);
    session_save_path($sessionPath);
}
session_start();

$composerAutoload=__DIR__.'/vendor/autoload.php';
if(is_file($composerAutoload))require_once $composerAutoload;

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
require __DIR__ . '/routes/seo_analytics.php';
require __DIR__ . '/routes/mentorship.php';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');
if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}

(new VisitorTracker())->track($requestPath);

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

if (!str_starts_with($requestPath, '/admin') && !str_starts_with($requestPath, '/academie') && !in_array($requestPath, ['/connexion','/inscription','/activation','/profil','/commande'], true) && str_contains($html, '</head>')) {
    $fallbackTitle='IFMAP Learning';
    if (preg_match('/<title>(.*?)<\/title>/is',$html,$m)) $fallbackTitle=trim(html_entity_decode(strip_tags($m[1]),ENT_QUOTES,'UTF-8')) ?: $fallbackTitle;
    $brandName=(string)($_SESSION['brand']['name']??'IFMAP Learning');
    $seo=SeoManager::current($fallbackTitle,$brandName);
    $html=preg_replace('/<title>.*?<\/title>/is','',$html,1) ?? $html;
    $html=preg_replace('/<meta\s+name=["\']description["\'][^>]*>/i','',$html,1) ?? $html;
    $html=str_replace('</head>',SeoManager::renderHead($seo).'</head>',$html);
}

$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/functional.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/modern-forms.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/profile-manager.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/account-dropdown.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/builder-media.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/rich-editor.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/news.css"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/seo-analytics.css?v=1"></head>', $html);
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/mentorship.css?v=1"></head>', $html);
/* Must stay last so legacy styles cannot reintroduce old green states. */
$html = str_replace('</head>', '<link rel="stylesheet" href="/public/assets/css/branding-polish.css?v=20260907-1"></head>', $html);
$html = str_replace('</body>', '<script src="/public/assets/js/functional.js"></script><script src="/public/assets/js/seo-analytics.js?v=1"></script><script src="/public/assets/js/mentorship.js?v=1"></script></body>', $html);
if ($basePath !== '') {
    $html = str_replace(
        ['href="/', 'src="/', 'action="/'],
        ['href="' . $basePath . '/', 'src="' . $basePath . '/', 'action="' . $basePath . '/'],
        $html
    );
}
echo $html;
