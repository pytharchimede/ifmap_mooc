<?php
return function(PDO $db): void {
    $hasColumn = static function(string $table,string $column) use ($db): bool {
        $st=$db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");$st->execute([$column]);return (bool)$st->fetch();
    };

    if(!$hasColumn('mentor_profiles','commission_rate_override')) {
        $db->exec("ALTER TABLE mentor_profiles ADD commission_rate_override DECIMAL(5,2) NULL AFTER hourly_rate");
    }
    foreach([
        'gross_amount'=>"DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER amount",
        'commission_rate'=>"DECIMAL(5,2) NOT NULL DEFAULT 20 AFTER gross_amount",
        'commission_amount'=>"DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER commission_rate",
        'mentor_net_amount'=>"DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER commission_amount",
        'mentor_payout_status'=>"ENUM('not_due','pending','held','paid') NOT NULL DEFAULT 'not_due' AFTER payment_status",
        'mentor_paid_at'=>"DATETIME NULL AFTER mentor_payout_status"
    ] as $column=>$definition){if(!$hasColumn('mentorship_requests',$column))$db->exec("ALTER TABLE mentorship_requests ADD `$column` $definition");}

    $db->exec("CREATE TABLE IF NOT EXISTS mentor_payouts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mentor_id BIGINT UNSIGNED NOT NULL,
        request_id BIGINT UNSIGNED NOT NULL UNIQUE,
        gross_amount DECIMAL(12,2) NOT NULL,
        commission_rate DECIMAL(5,2) NOT NULL,
        commission_amount DECIMAL(12,2) NOT NULL,
        net_amount DECIMAL(12,2) NOT NULL,
        status ENUM('pending','held','paid','cancelled') NOT NULL DEFAULT 'pending',
        payment_reference VARCHAR(190) NULL,
        payment_method VARCHAR(80) NULL,
        admin_note VARCHAR(500) NULL,
        paid_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_mentor_payout_status(mentor_id,status),
        CONSTRAINT fk_mentor_payout_mentor FOREIGN KEY(mentor_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_mentor_payout_request FOREIGN KEY(request_id) REFERENCES mentorship_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $st=$db->prepare("INSERT IGNORE INTO settings(`key`,`value`,`group`) VALUES('mentor_commission_rate','20','mentorship')");$st->execute();

    $db->exec("UPDATE mentorship_requests r LEFT JOIN mentor_profiles mp ON mp.user_id=r.mentor_id SET r.gross_amount=r.amount,r.commission_rate=COALESCE(mp.commission_rate_override,(SELECT CAST(value AS DECIMAL(5,2)) FROM settings WHERE `key`='mentor_commission_rate' LIMIT 1),20),r.commission_amount=ROUND(r.amount*COALESCE(mp.commission_rate_override,(SELECT CAST(value AS DECIMAL(5,2)) FROM settings WHERE `key`='mentor_commission_rate' LIMIT 1),20)/100,2),r.mentor_net_amount=r.amount-ROUND(r.amount*COALESCE(mp.commission_rate_override,(SELECT CAST(value AS DECIMAL(5,2)) FROM settings WHERE `key`='mentor_commission_rate' LIMIT 1),20)/100,2),r.mentor_payout_status=CASE WHEN r.status='completed' AND r.payment_status='paid' THEN 'pending' ELSE r.mentor_payout_status END WHERE r.gross_amount=0");

    $db->exec("DROP TRIGGER IF EXISTS trg_mentorship_request_finance_insert");
    $db->exec("CREATE TRIGGER trg_mentorship_request_finance_insert BEFORE INSERT ON mentorship_requests FOR EACH ROW BEGIN DECLARE v_rate DECIMAL(5,2); SELECT COALESCE(mp.commission_rate_override,(SELECT CAST(s.value AS DECIMAL(5,2)) FROM settings s WHERE s.`key`='mentor_commission_rate' LIMIT 1),20) INTO v_rate FROM mentor_profiles mp WHERE mp.user_id=NEW.mentor_id LIMIT 1; SET v_rate=LEAST(100,GREATEST(0,COALESCE(v_rate,20))); SET NEW.gross_amount=NEW.amount; SET NEW.commission_rate=v_rate; SET NEW.commission_amount=ROUND(NEW.amount*v_rate/100,2); SET NEW.mentor_net_amount=NEW.amount-NEW.commission_amount; END");

    $db->exec("DROP TRIGGER IF EXISTS trg_mentorship_request_payout_ready");
    $db->exec("CREATE TRIGGER trg_mentorship_request_payout_ready BEFORE UPDATE ON mentorship_requests FOR EACH ROW BEGIN IF NEW.status='completed' AND OLD.status<>'completed' AND NEW.payment_status='paid' THEN SET NEW.mentor_payout_status='pending'; END IF; END");
};
