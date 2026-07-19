<?php
namespace App\Middleware;

use App\Core\Session;

/**
 * Cross-Site Request Forgery (CSRF) Mitigation Middleware
 */
class CSRFMiddleware {
    public function handle(callable $next): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Apply protection to all write/modifying HTTP verbs
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (empty($token) || !Session::verifyCSRFToken($token)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'CSRF verification failed. Request denied.'
                ]);
                exit;
            }
        }
        
        $next();
    }
}
