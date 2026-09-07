<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $path = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) require $path;
});

App\Core\Env::load(__DIR__ . '/.env');
try {
    $ran = (new App\Core\Migrator())->run();
    echo $ran ? "Migrations exécutées :\n- " . implode("\n- ", $ran) . "\n" : "Base de données déjà à jour.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration impossible : {$e->getMessage()}\n");
    exit(1);
}

