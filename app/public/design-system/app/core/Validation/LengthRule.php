<?php
namespace App\Core\Validation;

/**
 * Text Length Validation Rule
 * Restricts string inputs to defined minimum and maximum characters boundary
 */
class LengthRule implements Rule {
    private int $min;
    private int $max;

    public function __construct(int $min = 0, int $max = 0) {
        $this->min = $min;
        $this->max = $max;
    }

    public function passes(string $attribute, $value): bool {
        if ($value === null) return true;
        
        $length = mb_strlen((string)$value);

        if ($this->min > 0 && $length < $this->min) {
            return false;
        }

        if ($this->max > 0 && $length > $this->max) {
            return false;
        }

        return true;
    }

    public function message(string $attribute): string {
        $name = str_replace('_', ' ', $attribute);
        if ($this->min > 0 && $this->max > 0) {
            return "The {$name} must be between {$this->min} and {$this->max} characters.";
        }
        if ($this->min > 0) {
            return "The {$name} must be at least {$this->min} characters.";
        }
        return "The {$name} may not be greater than {$this->max} characters.";
    }
}
