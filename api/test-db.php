<?php
// Define safe entry constant
define('ENTRY_SECURE', true);

// Custom PSR-4 Compliant Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    if (count($parts) > 0) {
        $parts[0] = lcfirst($parts[0]);
    }
    $relativeClass = implode('/', $parts);
    $file = $baseDir . $relativeClass . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

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
