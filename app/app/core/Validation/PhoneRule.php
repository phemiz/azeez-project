<?php
namespace App\Core;

namespace App\Core\Validation;

/**
 * Phone Number Validation Rule
 * Ensures the value is formatted as a valid GSM telephone number (ITU-T E.164 compliance)
 */
class PhoneRule implements Rule {
    public function passes(string $attribute, $value): bool {
        if (empty($value)) return true; // Defer empty checks to RequiredRule
        
        // E.164 standard: Starts with an optional '+' followed by 7 to 15 digits
        return (bool)preg_match('/^\+?[1-9]\d{6,14}$/', $value);
    }

    public function message(string $attribute): string {
        return "The " . str_replace('_', ' ', $attribute) . " must be a valid GSM number (e.g. +2348030000000).";
    }
}
