<?php
namespace App\Core;

/**
 * Base MVC Controller Class
 */
abstract class Controller {
    /**
     * Renders a view template within the core layout.
     */
    protected function view(string $viewName, array $data = []): void {
        // Extract data keys to variables for the view templates
        extract($data);

        // Path to the child view
        $viewFile = dirname(__DIR__) . "/views/{$viewName}.php";
        
        if (!file_exists($viewFile)) {
            http_response_code(500);
            exit("View file {$viewName}.php not found.");
        }

        // Buffer the child view content
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Load the main app layout
        $layoutFile = dirname(__DIR__) . "/views/layouts/layout.php";
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            // Fallback: output direct child view content if layout is missing
            echo $content;
        }
    }

    /**
     * Sends a secure JSON response.
     */
    protected function json(array $data, int $status = 200): void {
        // Enforce secure JSON headers
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Safely redirects to another page.
     */
    protected function redirect(string $url): void {
        // Validate URL to prevent Open Redirect vulnerabilities
        if (strpos($url, '/') === 0 || strpos($url, APP_URL) === 0) {
            header("Location: " . $url);
            exit;
        }
        header("Location: " . APP_URL);
        exit;
    }

    /**
     * Safely extracts and filters POST inputs.
     */
    protected function getPost(?string $key = null, $default = null) {
        if ($key === null) {
            return filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        }
        
        // Return sanitized parameter
        $val = $_POST[$key] ?? $default;
        if (is_string($val)) {
            return trim($val);
        }
        return $val;
    }

    /**
     * Safely extracts and filters GET inputs.
     */
    protected function getQuery(?string $key = null, $default = null) {
        if ($key === null) {
            return filter_input_array(INPUT_GET, FILTER_DEFAULT) ?: [];
        }
        $val = $_GET[$key] ?? $default;
        if (is_string($val)) {
            return trim($val);
        }
        return $val;
    }
}
