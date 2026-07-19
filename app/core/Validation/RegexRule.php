<?php
namespace App\Core\Validation;

/**
 * Regular Expression Validation Rule
 * Evaluates values against custom regular expression patterns
 */
class RegexRule implements Rule {
    private string $pattern;
    private string $customMessage;

    public function __construct(string $pattern, string $customMessage = '') {
        $this->pattern = $pattern;
        $this->customMessage = $customMessage;
    }

    public function passes(string $attribute, $value): bool {
        if (empty($value)) return true;
        return (bool)preg_match($this->pattern, (string)$value);
    }

    public function message(string $attribute): string {
        if (!empty($this->customMessage)) {
            return $this->customMessage;
        }
        return "The " . str_replace('_', ' ', $attribute) . " format is invalid.";
    }
}
