<?php
namespace App\Core;

final class Migrator
{
    public function run(): array
    {
        $db = Database::connection();
        $db->exec('CREATE TABLE IF NOT EXISTS migrations (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) UNIQUE, batch INT UNSIGNED, migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
        $done = $db->query('SELECT migration FROM migrations')->fetchAll(\PDO::FETCH_COLUMN);
        $batch = (int) $db->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations')->fetchColumn();
        $ran = [];
        foreach (glob(dirname(__DIR__, 2) . '/database/migrations/*.php') ?: [] as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) continue;
            $migration = require $file;
            $db->beginTransaction();
            try {
                $migration($db);
                $stmt = $db->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
                $stmt->execute([$name, $batch]);
                $db->commit();
                $ran[] = $name;
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }
        }
        return $ran;
    }
}

