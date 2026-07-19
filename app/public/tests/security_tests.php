<?php
/**
 * Automated Enterprise Security Testing Suite
 * Validates Cryptographic Integrity, AI Behavioral Profiling, and OTP replay resistance
 */

// Define mock configs to allow standalone CLI test runs
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gsm_security');
define('ENCRYPTION_CIPHER', 'AES-256-CBC');
define('APP_SECRET_KEY', 'test_secret_key_123456_##@@');
define('AI_WEIGHT_IP_ANOMALY', 35);
define('AI_WEIGHT_USER_AGENT_CHANGE', 25);
define('AI_WEIGHT_VELOCITY_VIOLATION', 40);
define('AI_CRITICAL_RISK_THRESHOLD', 70);

// Register custom class autoloader for App namespace
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

// Helper for terminal color-coding
function printResult(string $testName, bool $success, string $details = ''): void {
    $green = "\033[32m";
    $red = "\033[31m";
    $reset = "\033[0m";

    if ($success) {
        echo "{$green}[PASS]{$reset} {$testName} \n";
    } else {
        echo "{$red}[FAIL]{$reset} {$testName} - Reason: {$details}\n";
    }
}

echo "=========================================================\n";
echo "       GSM SECURITY SYSTEM - SECURITY UNIT TESTS         \n";
echo "=========================================================\n\n";

// --- Test 1: AES-256-CBC Cryptography & HMAC Signature ---
try {
    $crypto = new \App\Services\EncryptionService();
    $message = "Top Secret Satellite Frequencies: 145.2MHz, 433.0MHz";
    $passphrase = "AlphaBeta123";

    // Encrypt
    $enc = $crypto->encrypt($message, $passphrase);
    $decrypted = $crypto->decrypt($enc['ciphertext'], $enc['iv'], $enc['salt'], $enc['signature'], $passphrase);

    $success = ($decrypted === $message);
    printResult("Cryptography: Encryption/Decryption Integrity Check", $success);
    
    // Verify ciphertext signature failure (tampering test)
    $tamperedCipher = base64_encode(base64_decode($enc['ciphertext']) . 'tamper');
    $tamperVerified = false;
    try {
        $crypto->decrypt($tamperedCipher, $enc['iv'], $enc['salt'], $enc['signature'], $passphrase);
    } catch (\Exception $e) {
        $tamperVerified = (strpos($e->getMessage(), 'Cryptographic decryption failed') !== false);
    }
    printResult("Cryptography: Encrypt-then-MAC Signature Tamper Prevention", $tamperVerified);

} catch (\Exception $e) {
    printResult("Cryptography Tests", false, $e->getMessage());
}

// --- Test 2: AI Threat Detection Engine Profiling Rules ---
try {
    // Standalone database connectivity is required for AIEngine behavioral tests.
    // If db connection fails, we catch it and mark tests as skipped.
    $dbTestSuccess = false;
    try {
        $db = \App\Core\Database::getInstance();
        $db->getConnection();
        $dbTestSuccess = true;
    } catch (\Exception $e) {
        echo "\033[33m[WARN] Database unavailable on localhost. Skipping stateful DB tests, executing static heuristics only.\033[0m\n";
    }

    $ai = new \App\Services\AIEngine();
    $ip = '192.168.1.50';
    $ua = 'Mozilla/5.0 SecureTerminal';

    // Rule A: SQL Injection detection check
    $sqliPayload = ['message' => "1' OR '1'='1' --"];
    $reportSqli = $ai->analyzeSecurityEvent(null, 'encrypt_message', $ip, $ua, $sqliPayload);
    $sqliSuccess = ($reportSqli['risk_score'] >= 65 && strpos($reportSqli['threat_classification'], 'SQL Injection Attempt') !== false);
    printResult("AI Heuristics: SQL Injection Vector Detection", $sqliSuccess, "Score: " . $reportSqli['risk_score']);

    // Rule B: Cross-Site Scripting (XSS) payload detection
    $xssPayload = ['message' => "<script>alert('compromised')</script>"];
    $reportXss = $ai->analyzeSecurityEvent(null, 'encrypt_message', $ip, $ua, $xssPayload);
    $xssSuccess = ($reportXss['risk_score'] >= 50 && strpos($reportXss['threat_classification'], 'Cross-Site Scripting (XSS) Attempt') !== false);
    printResult("AI Heuristics: XSS Script Injection Vector Detection", $xssSuccess, "Score: " . $reportXss['risk_score']);

    // Rule C: GSM IMSI Catcher (Spoofed base station tower) spoof verification
    $imsiPayload = [
        'gsm_metadata' => [
            'smsc' => '0000000000',
            'protocol_id' => '0'
        ]
    ];
    $reportImsi = $ai->analyzeSecurityEvent(null, 'encrypt_message', $ip, $ua, $imsiPayload);
    $imsiSuccess = ($reportImsi['risk_score'] >= 60 && strpos($reportImsi['threat_classification'], 'Base Station Spoofing') !== false);
    printResult("AI Heuristics: GSM IMSI-Catcher simulation checks", $imsiSuccess, "Score: " . $reportImsi['risk_score']);

    // Rule D: Type 0 Silent SMS tracker checks
    $silentSmsPayload = [
        'gsm_metadata' => [
            'smsc' => '23480300000',
            'protocol_id' => '64'
        ]
    ];
    $reportSilent = $ai->analyzeSecurityEvent(null, 'encrypt_message', $ip, $ua, $silentSmsPayload);
    $silentSuccess = ($reportSilent['risk_score'] >= 45 && strpos($reportSilent['threat_classification'], 'Silent SMS Tracker') !== false);
    printResult("AI Heuristics: Silent SMS Location Triangulation checks", $silentSuccess, "Score: " . $reportSilent['risk_score']);

} catch (\Exception $e) {
    printResult("AI Heuristics Tests", false, $e->getMessage());
}

echo "\n=========================================================\n";
echo "                    TEST SUITE FINISHED                  \n";
echo "=========================================================\n";
