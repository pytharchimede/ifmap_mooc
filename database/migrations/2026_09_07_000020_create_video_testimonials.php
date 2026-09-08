<?php
return function(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS video_testimonials (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        course_id BIGINT UNSIGNED NULL,
        video_path VARCHAR(500) NOT NULL,
        duration_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        caption VARCHAR(500) NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        rejection_reason VARCHAR(500) NULL,
        featured TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        approved_by BIGINT UNSIGNED NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_testimonials_status (status, featured, approved_at),
        INDEX idx_testimonials_user (user_id, created_at),
        CONSTRAINT fk_testimonials_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_testimonials_course FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE SET NULL,
        CONSTRAINT fk_testimonials_admin FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
