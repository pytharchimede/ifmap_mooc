<?php
return function(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        channel ENUM('email','sms','whatsapp') NOT NULL,
        destination VARCHAR(190) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_reset_user(user_id,expires_at),
        CONSTRAINT fk_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS auth_notification_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        purpose ENUM('activation_otp','password_reset','test') NOT NULL,
        channel ENUM('display','email','sms','whatsapp') NOT NULL,
        destination VARCHAR(190) NULL,
        provider VARCHAR(80) NULL,
        status ENUM('queued','sent','failed','displayed') NOT NULL DEFAULT 'queued',
        provider_response VARCHAR(1000) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_auth_notify_user(user_id,created_at),
        INDEX idx_auth_notify_status(status,created_at),
        CONSTRAINT fk_auth_notify_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach([
        ['otp_delivery_mode','display','auth_notifications'],
        ['otp_forced_channel','email','auth_notifications'],
        ['otp_allow_resend','1','auth_notifications'],
        ['password_reset_enabled','1','auth_notifications'],
        ['password_reset_channels','email,sms,whatsapp','auth_notifications'],
        ['sms_provider','twilio','auth_notifications'],
        ['sms_sender','','auth_notifications'],
        ['twilio_account_sid','','auth_notifications'],
        ['twilio_auth_token','','auth_notifications'],
        ['twilio_sms_from','','auth_notifications'],
        ['twilio_whatsapp_from','','auth_notifications'],
        ['whatsapp_provider','meta','auth_notifications'],
        ['meta_whatsapp_token','','auth_notifications'],
        ['meta_whatsapp_phone_number_id','','auth_notifications'],
        ['meta_whatsapp_api_version','v23.0','auth_notifications'],
        ['meta_whatsapp_otp_template','','auth_notifications'],
        ['meta_whatsapp_reset_template','','auth_notifications']
    ] as $row){
        $st=$db->prepare("INSERT IGNORE INTO settings(`key`,`value`,`group`) VALUES(?,?,?)");
        $st->execute($row);
    }
};
