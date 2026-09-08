<?php
return function(PDO $db): void {
    $db->exec("ALTER TABLE users MODIFY role ENUM('learner','instructor','mentor','admin') DEFAULT 'learner'");

    $db->exec("CREATE TABLE IF NOT EXISTS mentor_profiles (
        user_id BIGINT UNSIGNED PRIMARY KEY,
        headline VARCHAR(190) NULL,
        bio TEXT NULL,
        years_experience SMALLINT UNSIGNED DEFAULT 0,
        hourly_rate DECIMAL(12,2) DEFAULT 0,
        currency CHAR(3) DEFAULT 'XOF',
        dossier_status ENUM('draft','submitted','review','interview','approved','rejected','suspended') DEFAULT 'draft',
        identity_verified TINYINT(1) DEFAULT 0,
        credentials_verified TINYINT(1) DEFAULT 0,
        interview_completed TINYINT(1) DEFAULT 0,
        admin_notes TEXT NULL,
        interview_at DATETIME NULL,
        verified_at DATETIME NULL,
        rating_avg DECIMAL(3,2) DEFAULT 0,
        rating_count INT UNSIGNED DEFAULT 0,
        availability_json LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_mentor_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS mentor_skills (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mentor_id BIGINT UNSIGNED NOT NULL,
        skill_name VARCHAR(120) NOT NULL,
        level ENUM('intermediate','advanced','expert') DEFAULT 'advanced',
        verified TINYINT(1) DEFAULT 0,
        verified_at DATETIME NULL,
        UNIQUE KEY uq_mentor_skill(mentor_id,skill_name),
        CONSTRAINT fk_mentor_skill FOREIGN KEY(mentor_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS mentor_documents (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mentor_id BIGINT UNSIGNED NOT NULL,
        document_type VARCHAR(80) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        note VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_mentor_document FOREIGN KEY(mentor_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_requests (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        learner_id BIGINT UNSIGNED NOT NULL,
        mentor_id BIGINT UNSIGNED NOT NULL,
        subject VARCHAR(190) NOT NULL,
        goals TEXT NULL,
        duration_minutes SMALLINT UNSIGNED DEFAULT 60,
        scheduled_at DATETIME NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        currency CHAR(3) DEFAULT 'XOF',
        status ENUM('draft','awaiting_payment','paid','confirmed','completed','cancelled','refunded') DEFAULT 'draft',
        payment_status ENUM('unpaid','pending','paid','failed','refunded') DEFAULT 'unpaid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_mentorship_learner(learner_id,status),
        INDEX idx_mentorship_mentor(mentor_id,status),
        CONSTRAINT fk_mentorship_learner FOREIGN KEY(learner_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_mentorship_mentor FOREIGN KEY(mentor_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_payments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_id BIGINT UNSIGNED NOT NULL UNIQUE,
        provider VARCHAR(40) NOT NULL DEFAULT 'paiementpro',
        reference VARCHAR(100) NOT NULL UNIQUE,
        provider_reference VARCHAR(190) NULL,
        token_hash CHAR(64) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        status ENUM('initiated','pending','paid','failed','refunded') DEFAULT 'initiated',
        payload LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_mentorship_payment FOREIGN KEY(request_id) REFERENCES mentorship_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS coaching_sessions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_id BIGINT UNSIGNED NOT NULL UNIQUE,
        provider VARCHAR(40) NOT NULL,
        room_name VARCHAR(190) NOT NULL,
        room_url VARCHAR(1000) NULL,
        room_data LONGTEXT NULL,
        status ENUM('scheduled','ready','live','completed','cancelled') DEFAULT 'scheduled',
        starts_at DATETIME NULL,
        ended_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_coaching_request FOREIGN KEY(request_id) REFERENCES mentorship_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS mentor_reviews (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_id BIGINT UNSIGNED NOT NULL UNIQUE,
        learner_id BIGINT UNSIGNED NOT NULL,
        mentor_id BIGINT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED NOT NULL,
        comment TEXT NULL,
        status ENUM('pending','approved','rejected') DEFAULT 'approved',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_review_request FOREIGN KEY(request_id) REFERENCES mentorship_requests(id) ON DELETE CASCADE,
        CONSTRAINT fk_review_learner FOREIGN KEY(learner_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_review_mentor FOREIGN KEY(mentor_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach([
        ['live_provider','jitsi','integrations'],['jitsi_base_url','https://meet.jit.si','integrations'],['daily_api_key','','integrations'],['daily_domain','','integrations'],
        ['smtp_host','','mail'],['smtp_port','587','mail'],['smtp_encryption','tls','mail'],['smtp_username','','mail'],['smtp_password','','mail'],['smtp_from_address','','mail'],['smtp_from_name','IFMAP','mail']
    ] as $s){$st=$db->prepare("INSERT IGNORE INTO settings(`key`,`value`,`group`) VALUES(?,?,?)");$st->execute($s);}
};
