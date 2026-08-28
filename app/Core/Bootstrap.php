<?php
namespace App\Core;

final class Bootstrap
{
    public static function run(string $root): void
    {
        umask(0002);
        foreach (['storage','storage/cache','storage/logs','public/uploads','public/uploads/courses','public/uploads/avatars'] as $relative) {
            $path=$root.'/'.$relative;
            if(!is_dir($path)) @mkdir($path,0775,true);
            if(is_dir($path)) @chmod($path,0775);
        }
        self::protectUploads($root.'/public/uploads');
        if(self::bool(Env::get('APP_AUTO_MIGRATE','true'))) self::migrate($root);
    }
    public static function writable(string $path): bool
    {
        return is_dir($path)&&is_writable($path);
    }
    private static function migrate(string $root): void
    {
        try {
            $lockPath=$root.'/storage/cache/migrations.lock';
            $lock=@fopen($lockPath,'c+');
            if($lock&&flock($lock,LOCK_EX|LOCK_NB)){(new Migrator())->run();flock($lock,LOCK_UN);}
            elseif(!$lock){(new Migrator())->run();}
            if($lock)fclose($lock);
        } catch(\Throwable $e) { self::log($root,'Migration automatique: '.$e->getMessage()); }
    }
    private static function protectUploads(string $path): void
    {
        if(!is_dir($path))return;
        $file=$path.'/.htaccess';
        if(!is_file($file)&&is_writable($path))@file_put_contents($file,"Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|py|sh)$\">\nRequire all denied\n</FilesMatch>\n");
    }
    private static function log(string $root,string $message): void
    {
        $file=$root.'/storage/logs/application.log';if(is_dir(dirname($file))&&is_writable(dirname($file)))@file_put_contents($file,'['.date('c').'] '.$message.PHP_EOL,FILE_APPEND|LOCK_EX);
    }
    private static function bool(mixed $value): bool { return in_array(strtolower((string)$value),['1','true','yes','on'],true); }
}
