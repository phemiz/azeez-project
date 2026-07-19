<?php
/**
 * Application Entry Bootstrap
 * Handles PSR-4 Autoloading, Configuration, Global Middleware, Routing, and Output buffering
 */

// Define execution entry constant
define('ENTRY_SECURE', true);

// 1. Custom PSR-4 Compliant Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    if (count($parts) > 0) {
        $parts[0] = lcfirst($parts[0]);
    }
    $relativeClass = implode('/', $parts);
    $file = $baseDir . $relativeClass . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 2. Load Configurations
require_once __DIR__ . '/../app/config/config.php';

// 3. Initialize Session & Security Policies
use App\Core\Session;
use App\Core\Router;
use App\Middleware\SecurityMiddleware;
use App\Middleware\CSRFMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\AuthorizationMiddleware;
use App\Middleware\SessionMiddleware;
use App\Middleware\MaintenanceMiddleware;

Session::start();

// 4. Initialize Router
$router = new Router();

// --- Auth Routes ---
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register', [RateLimitMiddleware::class, CSRFMiddleware::class]);
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login', [RateLimitMiddleware::class, CSRFMiddleware::class]);
$router->get('/admin/login', 'AuthController@showAdminLogin');
$router->post('/admin/login', 'AuthController@adminLogin', [RateLimitMiddleware::class, CSRFMiddleware::class]);
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/forgot-password', 'AuthController@forgotPassword', [RateLimitMiddleware::class, CSRFMiddleware::class]);
$router->get('/reset-password', 'AuthController@showResetPassword');
$router->post('/reset-password', 'AuthController@resetPassword', [RateLimitMiddleware::class, CSRFMiddleware::class]);
$router->get('/otp', 'AuthController@showOTP', [AuthenticationMiddleware::class]);
$router->post('/verify-otp', 'AuthController@verifyOTP', [RateLimitMiddleware::class, CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->post('/resend-otp', 'AuthController@resendOTP', [RateLimitMiddleware::class, CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->get('/logout', 'AuthController@logout');

// --- User Dashboard Routes ---
$router->get('/', 'DashboardController@index', [AuthenticationMiddleware::class]);
$router->get('/dashboard', 'DashboardController@index', [AuthenticationMiddleware::class]);
$router->get('/encrypt-payload', 'DashboardController@showEncrypt', [AuthenticationMiddleware::class]);
$router->get('/decrypt-payload', 'DashboardController@showDecrypt', [AuthenticationMiddleware::class]);
$router->get('/recommendations', 'DashboardController@showRecommendations', [AuthenticationMiddleware::class]);
$router->post('/encrypt', 'DashboardController@encryptMessage', [CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->post('/decrypt', 'DashboardController@decryptMessage', [CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->get('/profile', 'ProfileController@index', [AuthenticationMiddleware::class]);
$router->post('/profile/update', 'ProfileController@update', [CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->post('/profile/password', 'ProfileController@changePassword', [CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->post('/profile/avatar', 'ProfileController@uploadAvatar', [CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->post('/profile/security', 'ProfileController@updateSecuritySettings', [CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->post('/profile/sessions/revoke', 'ProfileController@revokeSession', [CSRFMiddleware::class, AuthenticationMiddleware::class]);

// --- Notification System Routes ---
$router->get('/notifications', 'NotificationController@index', [AuthenticationMiddleware::class]);
$router->get('/api/notifications/unread', 'NotificationController@getUnread', [AuthenticationMiddleware::class]);
$router->post('/api/notifications/read', 'NotificationController@read', [CSRFMiddleware::class, AuthenticationMiddleware::class]);
$router->post('/api/notifications/read-all', 'NotificationController@readAll', [CSRFMiddleware::class, AuthenticationMiddleware::class]);

// --- Reusable Search Engine Routes ---
$router->get('/search', 'SearchController@index', [AuthenticationMiddleware::class]);
$router->get('/api/search/live', 'SearchController@liveSearch', [AuthenticationMiddleware::class]);

// --- Admin Controls ---
$router->get('/admin', 'AdminController@index', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/threats', 'AdminController@threatsDashboard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/behavior', 'AdminController@behaviorDashboard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/users', 'AdminController@usersDashboard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/reports', 'AdminController@reportsDashboard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/reports/export-csv', 'AdminController@exportReportsCsv', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/reports/central', 'ReportsController@index', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/reports/export', 'ReportsController@export', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/settings', 'AdminController@settingsDashboard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/analytics', 'AdminController@analyticsDashboard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/settings/update', 'AdminController@updateSettings', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/users/create', 'AdminController@createUser', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/users/update', 'AdminController@updateUser', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/users/delete', 'AdminController@deleteUser', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/users/reset-password', 'AdminController@resetUserPassword', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/users/toggle-lock', 'AdminController@toggleUserLock', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/backup', 'AdminController@backup', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/restore', 'AdminController@restore', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/suspend', 'AdminController@suspendUser', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/sessions', 'AdminController@sessionsDashboard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/sessions/terminate', 'AdminController@terminateSessionGlobal', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/sessions/terminate-user', 'AdminController@terminateAllSessions', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/backups', 'BackupController@index', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/backups/generate', 'BackupController@generate', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/backups/restore', 'BackupController@restore', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/backups/download', 'BackupController@download', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/backups/verify', 'BackupController@verify', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->post('/admin/backups/delete', 'BackupController@delete', [CSRFMiddleware::class, AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/backups/wizard', 'BackupController@showWizard', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/audit', 'AuditController@index', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/audit/export', 'AuditController@export', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);
$router->get('/admin/widgets', 'AdminController@widgetsShowroom', [AuthenticationMiddleware::class, AuthorizationMiddleware::class]);

// 5. Execute Global Middleware Onion Pipeline and Dispatch Route
$sessionMiddleware = new SessionMiddleware();
$sessionMiddleware->handle(function() use ($router) {
    $maintenanceMiddleware = new MaintenanceMiddleware();
    $maintenanceMiddleware->handle(function() use ($router) {
        $securityMiddleware = new SecurityMiddleware();
        $securityMiddleware->handle(function() use ($router) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

            // Strip query string so the router only sees the path
            if (($qPos = strpos($requestUri, '?')) !== false) {
                $requestUri = substr($requestUri, 0, $qPos);
            }

            // Derive the sub-folder base path from APP_URL and strip it.
            $appUrlPath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
            if ($appUrlPath !== '' && str_starts_with($requestUri, $appUrlPath)) {
                $requestUri = substr($requestUri, strlen($appUrlPath));
            }

            if ($requestUri === '' || $requestUri[0] !== '/') {
                $requestUri = '/' . $requestUri;
            }

            $router->dispatch($requestUri, $_SERVER['REQUEST_METHOD'] ?? 'GET');
        });
    });
});
