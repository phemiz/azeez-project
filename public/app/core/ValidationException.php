<?php
namespace App\Core;

/**
 * Enterprise Validation Exception
 * Houses multi-field validation error messages and stack traces
 */
class ValidationException extends \Exception {
    protected array $errors = [];

    public function __construct(array $errors) {
        $this->errors = $errors;
        
        $message = "Input parameters failed validation rules check. See error log for details.";
        parent::__construct($message, 422);
    }

    /**
     * Get list of collected field error messages.
     */
    public function getErrors(): array {
        return $this->errors;
    }
}
