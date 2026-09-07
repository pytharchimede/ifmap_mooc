<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || function_exists('posix_geteuid') && posix_geteuid() !== 0) {
    exit("Exécutez : sudo php repair-phpmyadmin-local.php\n");
}
$config='/etc/phpmyadmin/config-db.php';
if(!is_readable($config)) exit("Configuration phpMyAdmin introuvable.\n");
require $config;
if(empty($dbuser)||!isset($dbpass)||empty($dbname)) exit("Configuration dbconfig-common incomplète.\n");
if(!preg_match('/^[a-zA-Z0-9_]+$/',(string)$dbname)||!preg_match('/^[a-zA-Z0-9_]+$/',(string)$dbuser)) exit("Identifiants phpMyAdmin invalides.\n");
try {
    $pdo=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $account=$pdo->quote((string)$dbuser).'@'.$pdo->quote('localhost');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("CREATE USER IF NOT EXISTS {$account} IDENTIFIED BY ".$pdo->quote((string)$dbpass));
    $pdo->exec("ALTER USER {$account} IDENTIFIED BY ".$pdo->quote((string)$dbpass));
    $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbname}`.* TO {$account}");
    $pdo->exec('FLUSH PRIVILEGES');
    $ini='/etc/php/8.5/apache2/conf.d/99-pcre-jit.ini';
    file_put_contents($ini,"; Local phpMyAdmin compatibility\npcre.jit=0\n");
    echo "Compte technique phpMyAdmin réparé et PCRE JIT désactivé pour Apache.\nRedémarrez Apache : sudo systemctl restart apache2\n";
} catch(Throwable $e){fwrite(STDERR,"Échec : {$e->getMessage()}\n");exit(1);}
