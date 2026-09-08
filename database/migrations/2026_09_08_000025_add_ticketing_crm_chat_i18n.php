<?php
return function(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS support_tickets (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(32) NOT NULL UNIQUE,
        user_id BIGINT UNSIGNED NULL,
        assigned_to BIGINT UNSIGNED NULL,
        subject VARCHAR(190) NOT NULL,
        category VARCHAR(80) NULL,
        priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
        status ENUM('open','in_progress','waiting_user','resolved','closed') DEFAULT 'open',
        last_message_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ticket_user(user_id,status),
        INDEX idx_ticket_assignee(assigned_to,status),
        CONSTRAINT fk_ticket_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_ticket_assignee FOREIGN KEY(assigned_to) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS support_ticket_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        sender_role ENUM('user','staff','system') DEFAULT 'user',
        message TEXT NOT NULL,
        attachment_path VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket_message(ticket_id,id),
        CONSTRAINT fk_ticket_message_ticket FOREIGN KEY(ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
        CONSTRAINT fk_ticket_message_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS crm_contacts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        type ENUM('lead','prospect','customer','partner') DEFAULT 'lead',
        company VARCHAR(190) NULL,
        name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NULL,
        phone VARCHAR(40) NULL,
        source VARCHAR(100) NULL,
        status VARCHAR(80) DEFAULT 'new',
        owner_id BIGINT UNSIGNED NULL,
        notes TEXT NULL,
        last_contact_at DATETIME NULL,
        next_action_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_crm_status(type,status),
        CONSTRAINT fk_crm_contact_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
        CONSTRAINT fk_crm_owner FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS crm_opportunities (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        contact_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        stage ENUM('qualification','proposal','negotiation','won','lost') DEFAULT 'qualification',
        amount DECIMAL(14,2) DEFAULT 0,
        currency CHAR(3) DEFAULT 'XOF',
        probability TINYINT UNSIGNED DEFAULT 10,
        expected_close_at DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_crm_stage(stage),
        CONSTRAINT fk_crm_opportunity_contact FOREIGN KEY(contact_id) REFERENCES crm_contacts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS chat_conversations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type ENUM('direct','support','group') DEFAULT 'direct',
        title VARCHAR(190) NULL,
        created_by BIGINT UNSIGNED NULL,
        last_message_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_chat_creator FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS chat_participants (
        conversation_id BIGINT UNSIGNED NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        last_read_message_id BIGINT UNSIGNED NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(conversation_id,user_id),
        CONSTRAINT fk_chat_participant_conversation FOREIGN KEY(conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
        CONSTRAINT fk_chat_participant_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id BIGINT UNSIGNED NOT NULL,
        sender_id BIGINT UNSIGNED NULL,
        message TEXT NOT NULL,
        message_type ENUM('text','file','system') DEFAULT 'text',
        attachment_path VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chat_messages(conversation_id,id),
        CONSTRAINT fk_chat_message_conversation FOREIGN KEY(conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
        CONSTRAINT fk_chat_message_sender FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS translation_strings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        translation_key VARCHAR(190) NOT NULL,
        locale VARCHAR(10) NOT NULL,
        value TEXT NOT NULL,
        scope VARCHAR(80) DEFAULT 'global',
        updated_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_translation_key_locale(translation_key,locale),
        CONSTRAINT fk_translation_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach([
        ['ticketing_enabled','1','modules'],['crm_enabled','1','modules'],['chat_enabled','1','modules'],['i18n_enabled','1','modules'],
        ['default_locale','fr','i18n'],['supported_locales','fr,en','i18n'],['auto_detect_locale','0','i18n']
    ] as $s){$st=$db->prepare("INSERT IGNORE INTO settings(`key`,`value`,`group`) VALUES(?,?,?)");$st->execute($s);}

    $translations=[
        ['admin.nav.dashboard','fr','Vue d’ensemble'],['admin.nav.dashboard','en','Overview'],
        ['admin.nav.pilotage','fr','Pilotage'],['admin.nav.pilotage','en','Management'],
        ['admin.nav.operations','fr','Opérations'],['admin.nav.operations','en','Operations'],
        ['admin.nav.content','fr','Contenus & visibilité'],['admin.nav.content','en','Content & visibility'],
        ['admin.nav.communication','fr','Communication'],['admin.nav.communication','en','Communication'],
        ['admin.nav.configuration','fr','Configuration'],['admin.nav.configuration','en','Configuration'],
        ['admin.nav.ticketing','fr','Ticketing & assistance'],['admin.nav.ticketing','en','Ticketing & support'],
        ['admin.nav.crm','fr','CRM & opportunités'],['admin.nav.crm','en','CRM & opportunities'],
        ['admin.nav.chat','fr','Discussion instantanée'],['admin.nav.chat','en','Instant messaging'],
        ['admin.nav.i18n','fr','Traductions & langues'],['admin.nav.i18n','en','Translations & languages'],
        ['academy.nav.chat','fr','Discussions'],['academy.nav.chat','en','Messages'],
        ['academy.nav.tickets','fr','Assistance & tickets'],['academy.nav.tickets','en','Support & tickets']
    ];
    $st=$db->prepare("INSERT IGNORE INTO translation_strings(translation_key,locale,value,scope) VALUES(?,?,?,'navigation')");
    foreach($translations as $row)$st->execute($row);
};
