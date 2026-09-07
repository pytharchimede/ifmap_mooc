<?php
return function (PDO $db): void {
    $db->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL, ADD COLUMN IF NOT EXISTS returned_at DATETIME NULL, ADD COLUMN IF NOT EXISTS terms_version VARCHAR(30) NULL, ADD COLUMN IF NOT EXISTS terms_accepted_at DATETIME NULL");
    $db->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS transaction_reference VARCHAR(190) NULL, ADD COLUMN IF NOT EXISTS payer_phone VARCHAR(40) NULL, ADD COLUMN IF NOT EXISTS payment_channel VARCHAR(60) NULL");
    $db->exec("ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS order_id BIGINT UNSIGNED NULL, ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid','paid','free','refunded') DEFAULT 'unpaid'");
    $db->exec("CREATE TABLE IF NOT EXISTS order_refunds (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL UNIQUE,
        amount DECIMAL(12,2) NOT NULL, status ENUM('requested','completed','rejected') NOT NULL DEFAULT 'requested',
        reason TEXT NOT NULL, reference VARCHAR(190) NULL, method VARCHAR(80) NULL,
        requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, completed_at DATETIME NULL,
        admin_note TEXT NULL, FOREIGN KEY(order_id) REFERENCES orders(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Link historical purchases, without treating account activation as payment.
    $db->exec("UPDATE enrollments e JOIN (SELECT o.user_id,oi.item_id,MAX(o.id) order_id FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE oi.item_type='course' AND o.payment_status='paid' AND o.status<>'cancelled' GROUP BY o.user_id,oi.item_id) x ON x.user_id=e.user_id AND x.item_id=e.course_id SET e.order_id=x.order_id,e.payment_status='paid' WHERE e.order_id IS NULL");
    $db->exec("UPDATE enrollments e JOIN courses c ON c.id=e.course_id SET e.payment_status='free' WHERE COALESCE(c.price,0)=0 AND e.payment_status='unpaid'");
    $db->exec("UPDATE enrollments e JOIN courses c ON c.id=e.course_id SET e.status='pending' WHERE c.price>0 AND e.payment_status='unpaid' AND e.source IN ('catalog','direct_registration')");
};
