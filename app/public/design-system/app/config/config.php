<?php
/**
 * Master Enterprise Application Configuration
 * Integrates Environment Loader, Security Rules, Cryptographic Keys, Timezone parameters,
 * and Error Reporting systems.
 */

// Safety check: Prevent direct access
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// 1. Initialize Environment Variables
use App\Core\Env;
use App\Core\ErrorHandler;

Env::load();

// 2. Set Default Application Timezone
date_default_timezone_set(Env::get('APP_TIMEZONE', 'UTC'));

// 3. Register System Error & Exception Logging Hook
ErrorHandler::register();

// 4. Define Global Constants
define('APP_ENV', Env::get('APP_ENV', 'development'));
define('APP_URL', rtrim(Env::get('APP_URL', 'http://localhost/gsm-security'), '/'));

// Database parameters
define('DB_HOST', Env::get('DB_HOST', '127.0.0.1'));
define('DB_PORT', Env::get('DB_PORT', '3306'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));
define('DB_NAME', Env::get('DB_NAME', 'gsm_security'));

// Cryptography tokens
define('ENCRYPTION_CIPHER', 'AES-256-CBC');
define('APP_SECRET_KEY', Env::get('APP_SECRET_KEY', 'default_gsm_protect_key_2026_##@@'));

// Session Security Configurations
define('SESSION_LIFETIME', (int)Env::get('SESSION_LIFETIME', 900));
define('SESSION_SECURE', (bool)Env::get('SESSION_SECURE', false));
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Strict');

// Rate limiting thresholds
define('RATE_LIMIT_MAX_ATTEMPTS', (int)Env::get('RATE_LIMIT_MAX_ATTEMPTS', 5));
define('RATE_LIMIT_WINDOW', (int)Env::get('RATE_LIMIT_WINDOW', 60));

// AI Engine Heuristic Weights
define('AI_WEIGHT_IP_ANOMALY', 35);
define('AI_WEIGHT_USER_AGENT_CHANGE', 25);
define('AI_WEIGHT_VELOCITY_VIOLATION', 40);
define('AI_CRITICAL_RISK_THRESHOLD', 70);
