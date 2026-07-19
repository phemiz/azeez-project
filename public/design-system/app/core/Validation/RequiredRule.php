<?php
namespace App\Core\Validation;

/**
 * Required Validation Rule
 * Checks if the value is set, not empty, and not null.
 */
class RequiredRule implements Rule {
    public function passes(string $attribute, $value): bool {
        if ($value === null) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        if (is_array($value) && count($value) === 0) {
            return false;
        }
        return true;
    }

    public function message(string $attribute): string {
        return "The " . str_replace('_', ' ', $attribute) . " field is required.";
    }
}
