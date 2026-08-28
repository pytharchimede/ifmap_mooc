<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("Ce script doit être exécuté en ligne de commande.\n");
spl_autoload_register(function(string $class): void { $prefix='App\\'; if(!str_starts_with($class,$prefix)) return; $path=__DIR__.'/app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_file($path)) require $path; });
App\Core\Env::load(__DIR__.'/.env');
$database=(string)App\Core\Env::get('DB_DATABASE'); $username=(string)App\Core\Env::get('DB_USERNAME'); $password=(string)App\Core\Env::get('DB_PASSWORD');
if(!preg_match('/^[a-zA-Z0-9_]+$/',$database)||!preg_match('/^[a-zA-Z0-9_]+$/',$username)) exit("Nom de base ou utilisateur invalide dans .env.\n");
try {
    $root=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $root->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    foreach(['localhost','127.0.0.1'] as $host){$account=$root->quote($username).'@'.$root->quote($host);$root->exec("CREATE USER IF NOT EXISTS {$account} IDENTIFIED BY ".$root->quote($password));$root->exec("ALTER USER {$account} IDENTIFIED BY ".$root->quote($password));$root->exec("GRANT ALL PRIVILEGES ON `{$database}`.* TO {$account}");}
    $root->exec('FLUSH PRIVILEGES'); echo "Base et utilisateur MySQL configurés.\n";
    $ran=(new App\Core\Migrator())->run(); echo $ran?"Migrations exécutées : ".implode(', ',$ran)."\n":"Base déjà à jour.\n";
} catch(Throwable $e){fwrite(STDERR,"Échec : {$e->getMessage()}\n");exit(1);}
