<?php
namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;
    public static function connection(): PDO
    {
        if (self::$pdo) return self::$pdo;
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', Env::get('DB_HOST'), Env::get('DB_PORT', '3306'), Env::get('DB_DATABASE'));
        return self::$pdo = new PDO($dsn, (string) Env::get('DB_USERNAME'), (string) Env::get('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}

