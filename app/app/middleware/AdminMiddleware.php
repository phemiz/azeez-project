<?php
namespace App\Middleware;

use App\Core\Session;
use App\Core\MiddlewareInterface;

/**
 * Admin Gating Middleware
 * Restricts access to operators with administrative access rights.
 */
class AdminMiddleware implements MiddlewareInterface {
    public function handle(callable $next): void {
        Session::start();
        $user = Session::get('user');

        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            http_response_code(403);
            
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Access denied. Administrative privileges required.'
                ]);
            } else {
                echo "<h1>403 Forbidden</h1><p>You do not have permission to access this administrative resource.</p>";
            }
            exit;
        }

        $next();
    }
}
