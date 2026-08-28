<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Router;

$sessionPath = __DIR__ . '/storage/sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0775, true);
session_save_path($sessionPath);
session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require $path;
});

Env::load(__DIR__ . '/.env');

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
if ($basePath !== '') {
    $html = str_replace(
        ['href="/', 'src="/', 'action="/'],
        ['href="' . $basePath . '/', 'src="' . $basePath . '/', 'action="' . $basePath . '/'],
        $html
    );
}
echo $html;
