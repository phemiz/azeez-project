<?php
namespace App\Core\Validation;

/**
 * Custom Callback Validation Rule
 * Evaluates inputs using user-defined PHP callback functions
 */
class CustomRule implements Rule {
    /** @var callable */
    private $callback;
    private string $message;

    public function __construct(callable $callback, string $message) {
        $this->callback = $callback;
        $this->message = $message;
    }

    public function passes(string $attribute, $value): bool {
        return (bool)call_user_func($this->callback, $value, $attribute);
    }

    public function message(string $attribute): string {
        return str_replace(':attribute', str_replace('_', ' ', $attribute), $this->message);
    }
}
