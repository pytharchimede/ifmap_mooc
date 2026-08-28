<?php
return function (PDO $db): void {
    $db->exec("ALTER TABLE users
        ADD COLUMN IF NOT EXISTS otp_code VARCHAR(10) NULL,
        ADD COLUMN IF NOT EXISTS otp_expires_at DATETIME NULL,
        ADD COLUMN IF NOT EXISTS phone_verified_at DATETIME NULL");
    try { $db->exec("ALTER TABLE users ADD UNIQUE INDEX users_phone_unique (phone)"); } catch (Throwable) {}
    $db->exec("ALTER TABLE enrollments
        ADD COLUMN IF NOT EXISTS status ENUM('pending','active','completed','cancelled') DEFAULT 'active',
        ADD COLUMN IF NOT EXISTS source VARCHAR(40) DEFAULT 'platform'");
};
