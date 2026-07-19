<?php
namespace App\Core;

/**
 * Enterprise Database Exception Class
 * Standardizes database errors and hides low-level SQL states from standard UI layers
 */
class DatabaseException extends \Exception {
    protected string $sqlState;
    protected int $driverCode;

    public function __construct(string $message, string $sqlState = 'HY000', int $driverCode = 0, ?\Throwable $previous = null) {
        $this->sqlState = $sqlState;
        $this->driverCode = $driverCode;
        
        $fullMessage = sprintf("Database Error [SQLSTATE %s] (Code %d): %s", $sqlState, $driverCode, $message);
        parent::__construct($fullMessage, 500, $previous);
    }

    public function getSqlState(): string {
        return $this->sqlState;
    }

    public function getDriverCode(): int {
        return $this->driverCode;
    }
}
