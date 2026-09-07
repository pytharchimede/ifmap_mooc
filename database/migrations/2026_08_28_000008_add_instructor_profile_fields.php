<?php
return function (PDO $db): void {
    $db->exec("ALTER TABLE users
        ADD COLUMN IF NOT EXISTS specialty VARCHAR(190) NULL,
        ADD COLUMN IF NOT EXISTS bio TEXT NULL,
        ADD COLUMN IF NOT EXISTS cv_path VARCHAR(255) NULL");
};
