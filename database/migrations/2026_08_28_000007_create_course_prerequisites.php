<?php
return function(PDO $db): void {
    $db->exec("CREATE TABLE course_prerequisites (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, course_id BIGINT UNSIGNED, type ENUM('skill','course') NOT NULL, skill_name VARCHAR(190) NULL, prerequisite_course_id BIGINT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE, FOREIGN KEY(prerequisite_course_id) REFERENCES courses(id) ON DELETE CASCADE, INDEX(course_id,type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
