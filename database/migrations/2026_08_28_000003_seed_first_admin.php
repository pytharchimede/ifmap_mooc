<?php
return function (PDO $db): void {
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin') ON DUPLICATE KEY UPDATE role='admin'");
    $stmt->execute(['Administrateur IFMAP', 'admin@ifmap.ci', password_hash('admin', PASSWORD_DEFAULT)]);
};
