<?php
namespace App\Core\Validation;

/**
 * Email Validation Rule
 * Ensures the value represents a valid RFC email address structure.
 */
class EmailRule implements Rule {
    public function passes(string $attribute, $value): bool {
        if (empty($value)) return true; // Let required rule handle empty checks
        return (bool)filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public function message(string $attribute): string {
        return "The " . str_replace('_', ' ', $attribute) . " must be a valid email address.";
    }
}
