<?php
namespace App\Core\Validation;

/**
 * Username Formatting Validation Rule
 * Restricts usernames to alphanumeric codes starting with a letter and containing no spaces
 */
class UsernameRule implements Rule {
    public function passes(string $attribute, $value): bool {
        if (empty($value)) return true;
        
        // Starts with a letter, allowed characters: alphanumeric, underscore, hyphen. Length: 3-20
        return (bool)preg_match('/^[a-zA-Z][a-zA-Z0-9_]{2,19}$/', $value);
    }

    public function message(string $attribute): string {
        return "The " . str_replace('_', ' ', $attribute) . " must start with a letter and contain only letters, numbers, and underscores (3-20 characters).";
    }
}
