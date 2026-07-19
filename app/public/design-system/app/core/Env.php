<?php
namespace App\Core;

/**
 * Enterprise Environment Variable Loader
 * Parses .env files and loads them into superglobals with structural type casting
 */
class Env {
    private static bool $loaded = false;

    /**
     * Locates and parses the .env file in the application root directory.
     * 
     * @param string|null $filePath Path to the env file. Defaults to project root.
     */
    public static function load(?string $filePath = null): void {
        if (self::$loaded) {
            return;
        }

        if ($filePath === null) {
            $filePath = dirname(dirname(__DIR__)) . '/.env';
        }

        if (!file_exists($filePath)) {
            return; // Fail silently or log error depending on environment
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and invalid lines
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            // Split into key and value
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding quotes if present
            if (preg_match('/^"(.+)"$/', $value, $matches) || preg_match('/^\'(.+)\'$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Load into environment superglobals
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Retrieves an environment variable with fallback default and type casting.
     * 
     * @param string $key Target key
     * @param mixed $default Fallback value
     * @return mixed Casted environment value
     */
    public static function get(string $key, $default = null) {
        // Enforce load check
        if (!self::$loaded) {
            self::load();
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        // Apply semantic type casting
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}
