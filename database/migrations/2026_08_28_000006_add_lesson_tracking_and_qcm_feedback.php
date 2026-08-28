<?php
return function(PDO $db): void {
    $db->exec("CREATE TABLE lesson_progress (user_id BIGINT UNSIGNED, lesson_id BIGINT UNSIGNED, completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(user_id,lesson_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(lesson_id) REFERENCES lessons(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("ALTER TABLE questions ADD COLUMN IF NOT EXISTS explanation TEXT NULL");
};
