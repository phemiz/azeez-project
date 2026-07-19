<?php
namespace App\Core;

/**
 * Enterprise Error and Exception Handler
 * Intercepts PHP errors, fatals, and uncaught exceptions, auditing them safely
 * without leaking server internals to clients.
 */
class ErrorHandler {
    /**
     * Registers system-wide hooks.
     */
    public static function register(): void {
        error_reporting(E_ALL);

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);

        // Set system default log file
        $logDir = dirname(dirname(__DIR__)) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        ini_set('log_errors', 1);
        ini_set('error_log', $logDir . '/error.log');
    }

    /**
     * Handles non-fatal runtime errors.
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $logMsg = sprintf("PHP Error [%d]: %s in %s on line %d", $severity, $message, $file, $line);
        
        // Write to core Logger
        $logger = \App\Core\Logger::getInstance();
        $logger->error(\App\Core\Logger::CHANNEL_ERROR, $logMsg, [
            'severity' => $severity,
            'file'     => $file,
            'line'     => $line
        ]);

        // Convert warning or notices to exception to enforce clean coding standards
        if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }

        return true;
    }

    /**
     * Handles uncaught exceptions.
     */
    public static function handleException(\Throwable $exception): void {
        $logMsg = sprintf(
            "PHP Uncaught Exception %s: '%s' in %s:%d",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        $logger = \App\Core\Logger::getInstance();
        $logger->critical(\App\Core\Logger::CHANNEL_ERROR, $logMsg, [
            'class' => get_class($exception),
            'trace' => $exception->getTraceAsString()
        ]);

        self::renderErrorResponse($exception);
    }

    /**
     * Catches fatal errors during engine shutdown.
     */
    public static function handleShutdown(): void {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $logMsg = sprintf("PHP Fatal Shutdown Error [%d]: %s in %s on line %d", $error['type'], $error['message'], $error['file'], $error['line']);
            
            $logger = \App\Core\Logger::getInstance();
            $logger->emergency(\App\Core\Logger::CHANNEL_ERROR, $logMsg, [
                'type' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            $exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::renderErrorResponse($exception);
        }
    }

    /**
     * Renders a clean response to the client.
     */
    private static function renderErrorResponse(\Throwable $exception): void {
        // Clear any previous output buffers
        if (ob_get_length()) {
            ob_clean();
        }

        $status = 500;
        http_response_code($status);

        $isJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        $isDev = APP_ENV === 'development';

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            $response = [
                'status' => 'error',
                'message' => $isDev ? $exception->getMessage() : 'An internal system error occurred. Secure logs captured.'
            ];
            if ($isDev) {
                $response['file'] = $exception->getFile();
                $response['line'] = $exception->getLine();
                $response['trace'] = $exception->getTrace();
            }
            echo json_encode($response);
        } else {
            // Render styled HTML page
            ?>
            <!DOCTYPE html>
            <html lang="en" style="background-color: #030712; color: #f3f4f6; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
            <div style="max-width: 600px; padding: 32px; background: rgba(17,24,39,0.7); border: 1px solid rgba(6,182,212,0.15); border-radius: 12px; box-shadow: 0 4px 30px rgba(0,0,0,0.5);">
                <h1 style="color: #ff3333; font-size: 24px; margin-top: 0;">System Security Shield Intercepted</h1>
                <p style="font-size: 14px; line-height: 1.6; color: #9ca3af;">
                    The application execution was suspended. A report has been cataloged in our secure auditing server logs.
                </p>
                <?php if ($isDev): ?>
                    <div style="margin-top: 24px; padding: 16px; background: #000; border-radius: 6px; font-family: monospace; font-size: 12px; color: #00FF41; overflow-x: auto; border: 1px solid #1f1f1f;">
                        <strong>Exception:</strong> <?= htmlspecialchars($exception->getMessage()) ?><br><br>
                        <strong>Source:</strong> <?= htmlspecialchars($exception->getFile()) ?>:<?= $exception->getLine() ?><br><br>
                        <strong>Trace:</strong><pre style="margin: 0; white-space: pre-wrap;"><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 20px; font-size: 11px; font-family: monospace; color: #06b6d4;">
                        GATEWAY_CODE: SEC_SHIELD_E500 &middot; TIMESTEP: <?= date('Y-m-d H:i:s') ?>
                    </div>
                <?php endif; ?>
                <div style="margin-top: 24px;">
                    <a href="<?= APP_URL ?>" style="text-decoration: none; font-size: 13px; font-weight: bold; color: #00ff41; border: 1px solid #00ff41; padding: 8px 16px; border-radius: 6px; display: inline-block;">Return to Dashboard</a>
                </div>
            </div>
            </html>
            <?php
        }
        exit;
    }
}
