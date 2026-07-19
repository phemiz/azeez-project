<?php
namespace App\Core;

/**
 * Enterprise Route Dispatcher with Middleware Support
 */
class Router {
    private array $routes = [];

    /**
     * Registers a GET route.
     */
    public function get(string $path, string $handler, array $middlewares = []): void {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Registers a POST route.
     */
    public function post(string $path, string $handler, array $middlewares = []): void {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, string $handler, array $middlewares): void {
        // Convert path to regex if it contains params, e.g. /user/{id} -> /user/(?P<id>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][$pattern] = [
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Dispatches the incoming HTTP request.
     */
    public function dispatch(string $uri, string $method): void {
        // Strip query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Clean URI
        $uri = '/' . trim($uri, '/');

        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                // Filter matches to get named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $this->executeChain($route['handler'], $route['middlewares'], $params);
                return;
            }
        }

        $this->sendNotFound();
    }

    /**
     * Executes the middleware chain and then calls the Controller action.
     */
    private function executeChain(string $handler, array $middlewares, array $params): void {
        $index = 0;

        $next = function() use (&$index, $middlewares, $handler, $params, &$next) {
            if ($index < count($middlewares)) {
                $middlewareClass = $middlewares[$index++];
                
                // Safety check
                if (!class_exists($middlewareClass)) {
                    http_response_code(500);
                    exit("Middleware $middlewareClass not found.");
                }

                $middleware = new $middlewareClass();
                $middleware->handle($next);
            } else {
                $this->invokeHandler($handler, $params);
            }
        };

        $next();
    }

    private function invokeHandler(string $handler, array $params): void {
        list($controllerClass, $method) = explode('@', $handler);
        $fullControllerClass = "App\\Controllers\\" . $controllerClass;

        if (!class_exists($fullControllerClass)) {
            http_response_code(500);
            exit("Controller $fullControllerClass not found.");
        }

        $controller = new $fullControllerClass();
        if (!method_exists($controller, $method)) {
            http_response_code(500);
            exit("Action $method not found in $fullControllerClass.");
        }

        // Invoke Controller action with parameters
        call_user_func_array([$controller, $method], $params);
    }

    private function sendNotFound(): void {
        http_response_code(404);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Endpoint not found']);
        } else {
            // Standard user-facing 404
            echo "<h1>404 Not Found</h1><p>The requested URL was not found on this server.</p>";
        }
        exit;
    }
}
