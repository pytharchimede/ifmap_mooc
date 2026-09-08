<?php
return function(PDO $db): void {
    $database=(string)$db->query('SELECT DATABASE()')->fetchColumn();

    $hasColumn=function(string $table,string $column) use($db,$database): bool {
        $st=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
        $st->execute([$database,$table,$column]);
        return (int)$st->fetchColumn()>0;
    };

    // Some installations already had chat tables. CREATE TABLE IF NOT EXISTS does not
    // upgrade those tables, so normalize the schema without deleting existing data.
    if($hasColumn('chat_conversations','type')) {
        $db->exec("ALTER TABLE chat_conversations MODIFY type ENUM('direct','support','group') NOT NULL DEFAULT 'direct'");
    } else {
        $db->exec("ALTER TABLE chat_conversations ADD COLUMN type ENUM('direct','support','group') NOT NULL DEFAULT 'direct' AFTER id");
    }
    if(!$hasColumn('chat_conversations','title')) $db->exec("ALTER TABLE chat_conversations ADD COLUMN title VARCHAR(190) NULL AFTER type");
    if(!$hasColumn('chat_conversations','created_by')) $db->exec("ALTER TABLE chat_conversations ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER title");
    if(!$hasColumn('chat_conversations','last_message_at')) $db->exec("ALTER TABLE chat_conversations ADD COLUMN last_message_at DATETIME NULL AFTER created_by");
    if(!$hasColumn('chat_conversations','created_at')) $db->exec("ALTER TABLE chat_conversations ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    if(!$hasColumn('chat_conversations','updated_at')) $db->exec("ALTER TABLE chat_conversations ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    if(!$hasColumn('chat_participants','last_read_message_id')) $db->exec("ALTER TABLE chat_participants ADD COLUMN last_read_message_id BIGINT UNSIGNED NULL AFTER user_id");
    if(!$hasColumn('chat_participants','joined_at')) $db->exec("ALTER TABLE chat_participants ADD COLUMN joined_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");

    if(!$hasColumn('chat_messages','sender_id')) $db->exec("ALTER TABLE chat_messages ADD COLUMN sender_id BIGINT UNSIGNED NULL AFTER conversation_id");
    if(!$hasColumn('chat_messages','message')) $db->exec("ALTER TABLE chat_messages ADD COLUMN message TEXT NOT NULL AFTER sender_id");
    if($hasColumn('chat_messages','message_type')) {
        $db->exec("ALTER TABLE chat_messages MODIFY message_type ENUM('text','file','system') NOT NULL DEFAULT 'text'");
    } else {
        $db->exec("ALTER TABLE chat_messages ADD COLUMN message_type ENUM('text','file','system') NOT NULL DEFAULT 'text' AFTER message");
    }
    if(!$hasColumn('chat_messages','attachment_path')) $db->exec("ALTER TABLE chat_messages ADD COLUMN attachment_path VARCHAR(500) NULL AFTER message_type");
    if(!$hasColumn('chat_messages','created_at')) $db->exec("ALTER TABLE chat_messages ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");

    // Ensure settings entry exists on databases where the original migration was partially applied.
    $st=$db->prepare("INSERT IGNORE INTO settings(`key`,`value`,`group`) VALUES('chat_enabled','1','modules')");
    $st->execute();
};
