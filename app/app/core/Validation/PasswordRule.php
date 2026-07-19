<?php
namespace App\Core\Validation;

use App\Core\PasswordManager;

/**
 * Password Strength Validation Rule
 * Delegates validation to PasswordManager to check password entropy metrics
 */
class PasswordRule implements Rule {
    public function passes(string $attribute, $value): bool {
        if (empty($value)) return true;
        return PasswordManager::validateStrength($value);
    }

    public function message(string $attribute): string {
        return "The " . str_replace('_', ' ', $attribute) . " must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }
}
