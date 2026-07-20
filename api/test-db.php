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
    
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE username = 'super'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $hash = $user['password_hash'];
        echo "DB Hash: " . $hash . "\n";
        echo "Verify super123: " . (password_verify('super123', $hash) ? 'yes' : 'no') . "\n";
        echo "Verify admin123: " . (password_verify('admin123', $hash) ? 'yes' : 'no') . "\n";
    } else {
        echo "Super user not found!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
