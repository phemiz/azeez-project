<?php
define('ENTRY_SECURE', true);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    $conn = $db->getConnection();
    
    // Check tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n\n";
    
    if (in_array('users', $tables)) {
        $stmt = $conn->query("SELECT id, username, email, phone, status FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Users:\n";
        print_r($users);
    } else {
        echo "Users table does not exist!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
