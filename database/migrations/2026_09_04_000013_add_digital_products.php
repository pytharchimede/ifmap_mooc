<?php
return function (PDO $db): void {
    $columns=array_column($db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field');
    if(!in_array('product_type',$columns,true))$db->exec("ALTER TABLE products ADD product_type ENUM('physical','digital') NOT NULL DEFAULT 'physical' AFTER category");
    if(!in_array('digital_file',$columns,true))$db->exec('ALTER TABLE products ADD digital_file VARCHAR(255) NULL AFTER gallery');
    if(!in_array('download_limit',$columns,true))$db->exec('ALTER TABLE products ADD download_limit INT UNSIGNED NOT NULL DEFAULT 5 AFTER digital_file');
    $db->exec("CREATE TABLE IF NOT EXISTS digital_downloads (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, order_item_id BIGINT UNSIGNED NOT NULL, product_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) UNIQUE NOT NULL, download_count INT UNSIGNED NOT NULL DEFAULT 0, download_limit INT UNSIGNED NOT NULL DEFAULT 5, expires_at DATETIME NULL, last_downloaded_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY(order_item_id) REFERENCES order_items(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
