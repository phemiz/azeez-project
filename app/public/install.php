<?php
/**
 * GSM Guard Secure Systems Installer & Configuration Wizard
 * Sets up database schemas, validates server environments, and configures environment variables.
 */

// Define safe entry constant
define('ENTRY_SECURE', true);

$lockFile = dirname(__DIR__) . '/logs/install.lock';

// If installation is locked, refuse execution
if (file_exists($lockFile)) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en" style="background-color: #030712; color: #f3f4f6; font-family: monospace; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
    <div style="max-width: 500px; padding: 32px; background: rgba(17,24,39,0.7); border: 1px solid rgba(239,68,68,0.25); border-radius: 12px; box-shadow: 0 4px 30px rgba(0,0,0,0.5); text-align: center;">
        <h1 style="color: #ef4444; font-size: 20px; margin-top: 0; text-transform: uppercase;">Wizard Lock Active</h1>
        <p style="font-size: 13px; line-height: 1.6; color: #9ca3af;">
            GSM Guard installation is locked. To re-run the wizard, delete the lock file located at:
        </p>
        <code style="display: block; background: #000; padding: 10px; border-radius: 6px; font-size: 11px; margin: 16px 0; color: #f43f5e; border: 1px solid #1f1f1f;">logs/install.lock</code>
        <a href="index.php" style="text-decoration: none; font-size: 12px; font-weight: bold; color: #06b6d4; border: 1px solid #06b6d4; padding: 8px 16px; border-radius: 6px; display: inline-block; transition: all 0.2s;">Return to Login</a>
    </div>
    </html>
    <?php
    exit;
}

// 1. Run Environment Diagnostics
$phpVersion = PHP_VERSION;
$phpValid = version_compare($phpVersion, '8.0.0', '>=');

$requiredExtensions = [
    'pdo'        => 'Database access interface',
    'pdo_mysql'  => 'MySQL database driver',
    'openssl'    => 'Cryptographic encryption algorithms',
    'session'    => 'Active session state management',
    'mbstring'   => 'Multi-byte string formatting operations'
];

$extensionStatus = [];
$allExtensionsValid = true;
foreach ($requiredExtensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $extensionStatus[$ext] = $loaded;
    if (!$loaded) {
        $allExtensionsValid = false;
    }
}

$logsDir = dirname(__DIR__) . '/logs';
$logsWritable = is_writable($logsDir);
$envWritable = is_writable(dirname(__DIR__)) || is_writable(dirname(__DIR__) . '/.env');

$envValid = $phpValid && $allExtensionsValid && $logsWritable && $envWritable;

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errorMessage = '';
$successMessage = '';

// Handle installer form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        $dbHost = $_POST['db_host'] ?? '127.0.0.1';
        $dbPort = $_POST['db_port'] ?? '3306';
        $dbUser = $_POST['db_user'] ?? 'root';
        $dbPass = $_POST['db_pass'] ?? '';
        $dbName = $_POST['db_name'] ?? 'gsm_security';
        $appUrl = $_POST['app_url'] ?? 'http://localhost/gsm-security/public';
        $appEnv = $_POST['app_env'] ?? 'production';
        $sessionSecure = isset($_POST['session_secure']) ? 'true' : 'false';

        $adminUser = $_POST['admin_username'] ?? 'admin';
        $adminEmail = $_POST['admin_email'] ?? 'admin@gsmguard.org';
        $adminPhone = $_POST['admin_phone'] ?? '+2348030000000';
        $adminPass = $_POST['admin_pass'] ?? 'AdminPass123_##';

        // Validate database connectivity
        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // Execute SQL schema script
            $schemaFile = dirname(__DIR__) . '/database/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("Database schema.sql file not found.");
            }

            $sqlContent = file_get_contents($schemaFile);
            
            // Clean SQL script: strip comments
            $sqlContent = preg_replace('/--.*\n/', '', $sqlContent);
            $sqlQueries = array_filter(array_map('trim', explode(';', $sqlContent)));

            // Execute batch queries
            foreach ($sqlQueries as $query) {
                if (!empty($query)) {
                    $pdo->exec($query);
                }
            }

            // Purge default database seed users to replace with custom admin details
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $pdo->exec("DELETE FROM users;");
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

            // Create configured Custom Administrator
            $adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password_hash, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$adminUser, $adminEmail, $adminPhone, $adminHash]);
            $adminUserId = $pdo->lastInsertId();

            $stmtAdmin = $pdo->prepare("INSERT INTO admins (user_id, access_level) VALUES (?, 'superadmin')");
            $stmtAdmin->execute([$adminUserId]);

            // Generate secure 32-byte secret key
            $secretKey = bin2hex(random_bytes(16)) . '_##@@';

            // Generate environment settings payload
            $envPayload = <<<EOT
# GSM Cyber Security Application Environment Settings
# Generated by Automated Installation Wizard

APP_ENV={$appEnv}
APP_URL={$appUrl}

DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_USER={$dbUser}
DB_PASS={$dbPass}
DB_NAME={$dbName}

APP_SECRET_KEY={$secretKey}

SESSION_LIFETIME=900
SESSION_SECURE={$sessionSecure}

RATE_LIMIT_MAX_ATTEMPTS=5
RATE_LIMIT_WINDOW=60
EOT;

            // Write environment configuration file
            $envFilePath = dirname(__DIR__) . '/.env';
            file_put_contents($envFilePath, $envPayload);

            // Create Wizard Lock file
            if (!is_dir($logsDir)) {
                mkdir($logsDir, 0755, true);
            }
            file_put_contents($lockFile, 'Installation completed on ' . date('Y-m-d H:i:s'));

            // Store configured username in session to display in final step
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['installer_admin_username'] = $adminUser;

            $successMessage = "GSM Guard database instantiated successfully! Config files locked.";
            $step = 3;

        } catch (Exception $e) {
            $errorMessage = "Configuration execution failure: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSM Guard - Setup & Configuration Wizard</title>
    <!-- Import Outfit Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-background: #030712;
            --color-card: rgba(17, 24, 39, 0.7);
            --color-border: rgba(6, 182, 212, 0.15);
            --color-primary: #06b6d4;
            --color-primary-glow: rgba(6, 182, 212, 0.4);
            --color-foreground: #f3f4f6;
            --color-foreground-muted: #9ca3af;
            --color-success: #10b981;
            --color-error: #ef4444;
        }

        body {
            background-color: var(--color-background);
            color: var(--color-foreground);
            font-family: 'Outfit', sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            background-image: radial-gradient(circle at top right, rgba(6, 182, 212, 0.08), transparent 40%);
        }

        .container {
            max-width: 650px;
            width: 100%;
        }

        .cyber-card {
            background: var(--color-card);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-b: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 20px;
        }

        .logo-box {
            display: inline-flex;
            padding: 12px;
            background: rgba(6, 182, 212, 0.08);
            border: 1px solid rgba(6, 182, 212, 0.25);
            border-radius: 12px;
            color: var(--color-primary);
            margin-bottom: 16px;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 0 0 8px 0;
            color: #ffffff;
        }

        .subtitle {
            font-size: 13px;
            color: var(--color-foreground-muted);
            margin: 0;
        }

        /* Step Indicators */
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
            position: relative;
            padding: 0 10px;
        }

        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
            width: 33.33%;
        }

        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #1f2937;
            border: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: bold;
            color: var(--color-foreground-muted);
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .step-item.active .step-dot {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: var(--color-background);
            box-shadow: 0 0 12px var(--color-primary-glow);
        }

        .step-item.completed .step-dot {
            background: var(--color-success);
            border-color: var(--color-success);
            color: var(--color-background);
        }

        .step-label {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--color-foreground-muted);
        }

        .step-item.active .step-label {
            color: var(--color-primary);
            font-weight: 700;
        }

        /* Lists */
        .check-list {
            margin: 20px 0;
            padding: 0;
            list-style: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
        }

        .check-item {
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .check-desc {
            color: var(--color-foreground-muted);
            margin-left: 8px;
            font-size: 11px;
        }

        .badge {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid transparent;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--color-success);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .badge-error {
            background: rgba(239, 68, 68, 0.15);
            color: var(--color-error);
            border-color: rgba(239, 68, 68, 0.3);
        }

        /* Form elements */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .form-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 12px 16px;
            box-sizing: border-box;
            color: #ffffff;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 8px var(--color-primary-glow);
        }

        .form-select {
            width: 100%;
            background: #0d1321;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 12px 16px;
            color: #ffffff;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        /* Alerts */
        .alert {
            padding: 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 24px;
            border: 1px solid transparent;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.2);
        }

        /* Buttons */
        .btn {
            width: 100%;
            background: var(--color-primary);
            color: var(--color-background);
            border: 1px solid var(--color-primary);
            border-radius: 8px;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 0 15px var(--color-primary-glow);
            background: #22d3ee;
        }

        .btn:disabled {
            background: #1f2937;
            border-color: #374151;
            color: var(--color-foreground-muted);
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container animate-fade-in">
        <div class="cyber-card">
            
            <div class="header">
                <div class="logo-box">
                    <svg style="width: 32px; height: 32px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h1>GSM Guard</h1>
                <p class="subtitle">Platform Installer & Environment Configuration Wizard</p>
            </div>

            <!-- Steps Progress Header -->
            <div class="steps">
                <div class="step-item <?= $step === 1 ? 'active' : ($step > 1 ? 'completed' : '') ?>">
                    <div class="step-dot">1</div>
                    <div class="step-label">Diagnostics</div>
                </div>
                <div class="step-item <?= $step === 2 ? 'active' : ($step > 2 ? 'completed' : '') ?>">
                    <div class="step-dot">2</div>
                    <div class="step-label">Database & Env</div>
                </div>
                <div class="step-item <?= $step === 3 ? 'active' : '' ?>">
                    <div class="step-dot">3</div>
                    <div class="step-label">Execution</div>
                </div>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <strong>Success:</strong> <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Step 1: Environment Diagnostics -->
            <?php if ($step === 1): ?>
                <h3 style="margin-top: 0; font-size: 15px; text-transform: uppercase; color: #fff; letter-spacing: 0.5px;">Environment Diagnostics</h3>
                <p style="font-size: 13px; color: var(--color-foreground-muted); line-height: 1.6;">
                    Checking server extensions, access permissions, and PHP versions.
                </p>

                <ul class="check-list">
                    <li class="check-item">
                        <span>PHP Version <span class="check-desc">(>= 8.0.0 required, current: <?= $phpVersion ?>)</span></span>
                        <span class="badge <?= $phpValid ? 'badge-success' : 'badge-error' ?>"><?= $phpValid ? 'PASS' : 'FAIL' ?></span>
                    </li>

                    <?php foreach ($requiredExtensions as $ext => $desc): ?>
                        <li class="check-item">
                            <span>Extension: <?= $ext ?> <span class="check-desc">(<?= $desc ?>)</span></span>
                            <span class="badge <?= $extensionStatus[$ext] ? 'badge-success' : 'badge-error' ?>">
                                <?= $extensionStatus[$ext] ? 'ACTIVE' : 'MISSING' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>

                    <li class="check-item">
                        <span>Directory Write Access: <code style="color: var(--color-primary);">logs/</code></span>
                        <span class="badge <?= $logsWritable ? 'badge-success' : 'badge-error' ?>"><?= $logsWritable ? 'WRITABLE' : 'LOCKED' ?></span>
                    </li>
                    
                    <li class="check-item">
                        <span>Directory Write Access: Root <code style="color: var(--color-primary);">.env</code></span>
                        <span class="badge <?= $envWritable ? 'badge-success' : 'badge-error' ?>"><?= $envWritable ? 'WRITABLE' : 'LOCKED' ?></span>
                    </li>
                </ul>

                <button onclick="window.location.href='?step=2'" class="btn" <?= !$envValid ? 'disabled' : '' ?>>
                    Proceed to Configuration
                </button>
            <?php endif; ?>

            <!-- Step 2: Configuration form -->
            <?php if ($step === 2): ?>
                <form method="POST" style="margin-top: 0;">
                    <h3 style="margin-top: 0; font-size: 15px; text-transform: uppercase; color: #fff; letter-spacing: 0.5px; margin-bottom: 20px;">Configuration Parameters</h3>
                    
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="127.0.0.1" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="db_port">Database Port</label>
                        <input type="text" id="db_port" name="db_port" value="3306" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="db_user">Database Username</label>
                        <input type="text" id="db_user" name="db_user" value="root" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" value="" class="form-input" />
                    </div>

                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="gsm_security" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="app_url">Application Base URL</label>
                        <input type="text" id="app_url" name="app_url" value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . str_replace('/public/install.php', '/public', $_SERVER['SCRIPT_NAME']) ?>" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="app_env">Application Environment</label>
                        <select id="app_env" name="app_env" class="form-select">
                            <option value="production" selected>Production (Locked, safe error handling)</option>
                            <option value="development">Development (Detailed error traces)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 25px; margin-bottom: 30px;">
                        <label class="form-checkbox">
                            <input type="checkbox" name="session_secure" checked style="accent-color: var(--color-primary);" />
                            <span>Enforce Secure SSL Session Cookies (SESSION_SECURE)</span>
                        </label>
                    </div>

                    <h3 style="margin-top: 25px; font-size: 14px; text-transform: uppercase; color: var(--color-primary); letter-spacing: 0.5px; margin-bottom: 15px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">Deploy Root Administrator</h3>
                    
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <input type="text" id="admin_username" name="admin_username" value="admin" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="admin_email">Admin Secure Email</label>
                        <input type="email" id="admin_email" name="admin_email" value="admin@gsmguard.org" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="admin_phone">Admin GSM Number</label>
                        <input type="text" id="admin_phone" name="admin_phone" value="+2348030000000" class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="admin_pass">Admin Passcode</label>
                        <input type="password" id="admin_pass" name="admin_pass" value="AdminPass123_##" class="form-input" required />
                    </div>

                    <button type="submit" class="btn">Execute Installation</button>
                </form>
            <?php endif; ?>

            <!-- Step 3: Installation complete -->
            <?php if ($step === 3): ?>
                <?php
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $configuredAdmin = $_SESSION['installer_admin_username'] ?? 'admin';
                ?>
                <h3 style="margin-top: 0; font-size: 15px; text-transform: uppercase; color: var(--color-success); letter-spacing: 0.5px;">Setup Finalized</h3>
                <p style="font-size: 13px; color: var(--color-foreground-muted); line-height: 1.6; margin-bottom: 24px;">
                    GSM Guard has been successfully provisioned. The wizard lock has been activated to prevent settings overrides.
                </p>

                <div style="background: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 12px; font-size: 12px; line-height: 1.8; margin-bottom: 30px; font-family: 'JetBrains Mono', monospace;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Administrator Node Configured:</span>
                        <strong style="color: var(--color-primary);"><?= htmlspecialchars($configuredAdmin) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding-bottom: 10px; margin-bottom: 10px;">
                        <span>Role Access Privilege Level:</span>
                        <strong style="color: var(--color-success);">superadmin</strong>
                    </div>
                    <p style="margin: 10px 0 0 0; font-size: 11px; color: var(--color-foreground-muted);">
                        Access the portal using your custom passcode set during configuration step. Keep these details secure.
                    </p>
                </div>

                <button onclick="window.location.href='index.php'" class="btn">Go to Login Terminal</button>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
