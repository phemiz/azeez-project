<?php
/**
 * Test Suite Entry Bootstrap
 * Sets up environment configurations and autoloader for standalone execution of test suites.
 */

// Enable strict errors reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ENTRY_SECURE', true);

// Configure core app constants for stand-alone running
if (!defined('APP_ENV')) {
    define('APP_ENV', 'testing');
}

// Load App autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Load framework configuration parameters
require_once dirname(__DIR__) . '/app/config/config.php';
