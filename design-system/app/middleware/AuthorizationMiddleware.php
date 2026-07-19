<?php
namespace App\Middleware;

use App\Core\Session;
use App\Services\RoleManager;
use App\Services\PermissionManager;

/**
 * Authorization Middleware (RBAC)
 * Intercepts requests to check user roles and permissions permissions
 */
class AuthorizationMiddleware {
    private PermissionManager $permissionManager;

    public function __construct() {
        $roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager($roleManager);
    }

    public function handle(callable $next): void {
        Session::start();

        if (!Session::has('user')) {
            $this->deny();
        }

        $user = Session::get('user');
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = '/' . trim($uri, '/');

        // Resolve required permission based on matched endpoint paths
        $requiredPermission = null;

        if (strpos($uri, '/admin/backup') === 0 || strpos($uri, '/admin/restore') === 0) {
            $requiredPermission = 'manage_backups';
        } elseif (strpos($uri, '/admin/suspend') === 0) {
            $requiredPermission = 'override_user_status';
        } elseif (strpos($uri, '/admin') === 0) {
            $requiredPermission = 'view_audit_logs';
        }

        // If the route has a defined permission constraint, evaluate access
        if ($requiredPermission !== null) {
            if (!$this->permissionManager->can($user, $requiredPermission)) {
                $this->deny();
            }
        }

        $next();
    }

    private function deny(): void {
        http_response_code(403);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Forbidden. Required access credentials missing.'
            ]);
        } else {
            echo "<h1>403 Forbidden</h1><p>You are not authorized to access this resource.</p>";
        }
        exit;
    }
}
