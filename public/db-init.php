<?php
/**
 * Safe Database Initializer for Vercel Serverless Environments
 * Executes schema.sql using environment variables.
 */

// Define safe entry constant
define('ENTRY_SECURE', true);

// 1. Custom PSR-4 Compliant Autoloader
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

// 2. Load Configurations
require_once __DIR__ . '/../app/config/config.php';

use App\Core\Env;

// 3. Security Check: Require the APP_SECRET_KEY as a GET query parameter
// This prevents unauthorized users from running or resetting the database.
$providedSecret = $_GET['secret'] ?? '';
if (empty($providedSecret) || $providedSecret !== APP_SECRET_KEY) {
    http_response_code(403);
    echo '<!DOCTYPE html>';
    echo '<html lang="en" style="background-color: #030712; color: #f3f4f6; font-family: monospace; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">';
    echo '<div style="max-width: 500px; padding: 32px; background: rgba(17,24,39,0.7); border: 1px solid rgba(239,68,68,0.25); border-radius: 12px; text-align: center;">';
    echo '<h1 style="color: #ef4444; font-size: 20px; margin-top: 0; text-transform: uppercase;">Access Denied</h1>';
    echo '<p style="font-size: 13px; line-height: 1.6; color: #9ca3af;">Please provide your correct APP_SECRET_KEY as a secret parameter to run the database setup.</p>';
    echo '<code style="display: block; background: #000; padding: 10px; border-radius: 6px; font-size: 11px; margin: 16px 0; color: #f43f5e; border: 1px solid #1f1f1f;">URL Format: ?secret=YOUR_SECRET_KEY</code>';
    echo '</div>';
    echo '</html>';
    exit;
}

// 4. Run database setup
try {
    echo "<h2>Connecting to the database...</h2>";
    $host = DB_HOST;
    $port = DB_PORT;
    $user = DB_USER;
    $pass = DB_PASS;
    $dbName = DB_NAME;

    // Connect without database name first to create it if it doesn't exist
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);

    // Create database if not exists (Note: Aiven might not support creating databases via free tier user, so we also select the defaultdb)
    echo "<p>Creating database `{$dbName}` if it does not exist...</p>";
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>Warning: Could not create database directly (likely due to provider permissions). Proceeding using existing database.</p>";
    }
    
    $pdo->exec("USE `{$dbName}`");
    echo "<p style='color: green;'>Successfully connected to database `{$dbName}`!</p>";

    // Execute SQL schema script
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Database schema.sql file not found.");
    }

    echo "<p>Reading schema.sql...</p>";
    $sqlContent = file_get_contents($schemaFile);
    
    // Clean SQL script: strip comments
    $sqlContent = preg_replace('/--.*\n/', '', $sqlContent);
    
    // Split by semicolon, but do it carefully
    $sqlQueries = array_filter(array_map('trim', explode(';', $sqlContent)));

    echo "<p>Executing tables and seeds setup (" . count($sqlQueries) . " queries)...</p>";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    foreach ($sqlQueries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<h2 style='color: green;'>Database Setup Successful!</h2>";
    echo "<p>Your tables have been created and seeded with default data.</p>";
    echo "<p><b>Default credentials:</b></p>";
    echo "<ul>";
    echo "<li>Admin Username: <b>admin</b> | Password: <b>admin123</b></li>";
    echo "<li>User Username: <b>demo_user</b> | Password: <b>user123</b></li>";
    echo "</ul>";
    echo "<p style='color: red;'><b>IMPORTANT SECURITY WARNING:</b> Please delete this file (<code>db-init.php</code>) from your repository after setup is complete so others cannot reset your database.</p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Setup Failed:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
