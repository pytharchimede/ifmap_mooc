<?php
return function (PDO $db): void {
    $columns = array_column($db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('category', $columns, true)) $db->exec("ALTER TABLE products ADD category VARCHAR(120) NOT NULL DEFAULT 'Équipements' AFTER reference");
    if (!in_array('promotional_price', $columns, true)) $db->exec('ALTER TABLE products ADD promotional_price DECIMAL(12,2) NULL AFTER price');
    if (!in_array('gallery', $columns, true)) $db->exec('ALTER TABLE products ADD gallery JSON NULL AFTER image');

    $orderColumns = array_column($db->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('coupon_code', $orderColumns, true)) $db->exec('ALTER TABLE orders ADD coupon_code VARCHAR(80) NULL AFTER delivery_zone');
    if (!in_array('discount', $orderColumns, true)) $db->exec('ALTER TABLE orders ADD discount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER subtotal');
    if (!in_array('updated_at', $orderColumns, true)) $db->exec('ALTER TABLE orders ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

    $db->exec("CREATE TABLE IF NOT EXISTS coupons (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(80) UNIQUE, type ENUM('percent','fixed') DEFAULT 'percent', value DECIMAL(12,2) NOT NULL, minimum_amount DECIMAL(12,2) NOT NULL DEFAULT 0, usage_limit INT UNSIGNED NULL, used_count INT UNSIGNED NOT NULL DEFAULT 0, starts_at DATETIME NULL, expires_at DATETIME NULL, status ENUM('active','inactive') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS order_status_history (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, status VARCHAR(40) NOT NULL, note VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
