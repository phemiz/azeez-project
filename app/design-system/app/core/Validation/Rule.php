<?php
namespace App\Core\Validation;

/**
 * Interface representing a validation rule constraints check
 */
interface Rule {
    /**
     * Determines if the validation rule passes.
     * 
     * @param string $attribute Field name
     * @param mixed $value Field value
     * @return bool True if valid, false on failure
     */
    public function passes(string $attribute, $value): bool;

    /**
     * Retrieves the custom validation failure message.
     * 
     * @param string $attribute Field name
     * @return string Error message string
     */
    public function message(string $attribute): string;
}
