<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Core Database Wrapper using PDO
 * Implements Singleton Pattern and enforces Prepared Statements
 */
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() {
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Enforce real prepared statements
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Auto-heal: Ensure activity_logs has the required columns if they are missing
            try {
                $check = $this->connection->query("SHOW COLUMNS FROM `activity_logs` LIKE 'risk_score'");
                if ($check && $check->rowCount() === 0) {
                    $this->connection->exec("
                        ALTER TABLE `activity_logs` 
                        ADD COLUMN `risk_score` INT NOT NULL DEFAULT 0,
                        ADD COLUMN `threat_classification` VARCHAR(100) NOT NULL DEFAULT 'Normal',
                        ADD COLUMN `severity` VARCHAR(15) NOT NULL DEFAULT 'low',
                        ADD COLUMN `threat_details` TEXT DEFAULT NULL
                    ");
                }
            } catch (\Exception $ex) {
                // Silently ignore if table doesn't exist yet
            }

            // Auto-heal: Ensure 'super' user exists
            try {
                $checkSuper = $this->connection->query("SELECT id, password_hash FROM users WHERE username = 'super'");
                $superUser = $checkSuper ? $checkSuper->fetch(PDO::FETCH_ASSOC) : null;
                
                if (!$superUser) {
                    $this->connection->exec('
                        INSERT INTO users (username, email, phone, password_hash, status) 
                        VALUES (\'super\', \'super@gsmsecurity.local\', \'+12345678903\', \'$2y$10$nJDIIvtHNOt.jXjZhenRoepyWNwCLV9anPuAfd1GnbzKxflzrAE/m\', \'active\')
                    ');
                    $newId = $this->connection->lastInsertId();
                    $this->connection->exec("
                        INSERT INTO admins (user_id, access_level) 
                        VALUES ({$newId}, 'root')
                    ");
                } elseif (strpos($superUser['password_hash'], '$2y$10$nJDII') !== 0) {
                    // Update corrupted hash to correct hash
                    $this->connection->exec('
                        UPDATE users 
                        SET password_hash = \'$2y$10$nJDIIvtHNOt.jXjZhenRoepyWNwCLV9anPuAfd1GnbzKxflzrAE/m\' 
                        WHERE username = \'super\'
                    ');
                }
            } catch (\Exception $ex) {
                error_log("Auto-heal super user creation failed: " . $ex->getMessage());
            }
        } catch (PDOException $e) {
            // Log connection failure securely and fail gracefully without disclosing credentials
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            exit("Database service unavailable. Details: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Executes a query with prepared statement parameters.
     */
    public function query(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Helper to fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Helper to fetch a single row
     */
    public function fetch(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Helper to fetch a single column
     */
    public function fetchColumn(string $sql, array $params = [], int $columnIndex = 0) {
        return $this->query($sql, $params)->fetchColumn($columnIndex);
    }

    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool {
        return $this->connection->commit();
    }

    public function rollBack(): bool {
        return $this->connection->rollBack();
    }
}
