<?php
namespace App\Core;

use App\Core\Validation\Rule;
use App\Core\Validation\RequiredRule;
use App\Core\Validation\EmailRule;
use App\Core\Validation\PhoneRule;
use App\Core\Validation\PasswordRule;
use App\Core\Validation\UsernameRule;
use App\Core\Validation\LengthRule;
use App\Core\Validation\RegexRule;

/**
 * Enterprise Validation Coordinator Engine
 * Parses validation maps and executes validation rules, consolidating errors into exception payloads
 */
class Validator {
    private array $errors = [];

    /**
     * Validates input values against a validation rules map.
     * 
     * @param array $data Input dataset (typically $_POST or $_GET)
     * @param array $rulesMap Validation schema rules mapping
     * @return bool True if all rules pass, otherwise throws ValidationException
     * @throws ValidationException
     */
    public function validate(array $data, array $rulesMap): bool {
        $this->errors = [];

        foreach ($rulesMap as $attribute => $rules) {
            $value = $data[$attribute] ?? null;

            // Normalize rules array (convert pipes string e.g. "required|email" to array)
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }

            foreach ($rules as $rule) {
                $ruleInstance = null;

                if ($rule instanceof Rule) {
                    $ruleInstance = $rule;
                } elseif (is_string($rule)) {
                    $ruleInstance = $this->resolveRuleString($rule);
                }

                if ($ruleInstance !== null) {
                    if (!$ruleInstance->passes($attribute, $value)) {
                        $this->errors[$attribute][] = $ruleInstance->message($attribute);
                        
                        // If field fails 'required', skip subsequent evaluations for this field
                        if ($ruleInstance instanceof RequiredRule) {
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return true;
    }

    /**
     * Resolves rule strings into class instances.
     */
    private function resolveRuleString(string $rule): ?Rule {
        $parts = explode(':', $rule, 2);
        $ruleName = strtolower(trim($parts[0]));
        $paramStr = $parts[1] ?? '';

        switch ($ruleName) {
            case 'required':
                return new RequiredRule();
            case 'email':
                return new EmailRule();
            case 'phone':
                return new PhoneRule();
            case 'password':
                return new PasswordRule();
            case 'username':
                return new UsernameRule();
            case 'min':
                return new LengthRule((int)$paramStr, 0);
            case 'max':
                return new LengthRule(0, (int)$paramStr);
            case 'between':
                $params = explode(',', $paramStr);
                $min = (int)($params[0] ?? 0);
                $max = (int)($params[1] ?? 0);
                return new LengthRule($min, $max);
        }

        return null;
    }

    /**
     * Get collected validation error reports.
     */
    public function getErrors(): array {
        return $this->errors;
    }
}
