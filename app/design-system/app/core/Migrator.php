<?php
namespace App\Core;

/**
 * Migration Support Helper
 * Executes database setup scripts and imports SQL schema files securely.
 */
class Migrator {
    /**
     * Reads and executes a target SQL file to update database schemas.
     * 
     * @param string $sqlFilePath Path to the SQL file.
     * @return bool True on success, throws exception on failure.
     */
    public static function run(string $sqlFilePath): bool {
        if (!file_exists($sqlFilePath)) {
            throw new \RuntimeException("Migration SQL script not found at: " . $sqlFilePath);
        }

        $sqlContent = file_get_contents($sqlFilePath);
        $db = Database::getInstance();

        try {
            $pdo = $db->getConnection();
            
            // Temporarily enable multi-query execution for structure imports
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->exec($sqlContent);
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            error_log("Database migration script executed successfully: " . basename($sqlFilePath));
            return true;
        } catch (\PDOException $e) {
            error_log("Database migration failed: " . $e->getMessage());
            throw new DatabaseException("Migration import execution failed.", $e->getSQLState(), (int)$e->getCode(), $e);
        }
    }
}
