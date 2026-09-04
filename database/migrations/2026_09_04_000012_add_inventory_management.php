<?php
return function (PDO $db): void {
    $columns=array_column($db->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_ASSOC),'Field');
    if(!in_array('low_stock_threshold',$columns,true))$db->exec('ALTER TABLE products ADD low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 5 AFTER stock_quantity');
    $db->exec("CREATE TABLE IF NOT EXISTS stock_movements (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id BIGINT UNSIGNED NOT NULL, order_id BIGINT UNSIGNED NULL, movement_type ENUM('in','out','adjustment','sale','return') NOT NULL, quantity INT NOT NULL, stock_before INT UNSIGNED NOT NULL, stock_after INT UNSIGNED NOT NULL, note VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE, FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE SET NULL, INDEX idx_stock_product_created(product_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
