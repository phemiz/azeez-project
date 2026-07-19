<?php
namespace App\Middleware;

use App\Core\MiddlewareInterface;

/**
 * Enterprise Security Headers Middleware
 * Protects against Clickjacking, XSS injection, MIME-sniffing, and cross-domain data leakage
 */
class SecurityMiddleware implements MiddlewareInterface {
    public function handle(callable $next): void {
        // Enforce strict HTTP security response headers
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
        
        // Content Security Policy (CSP) Configuration
        // Allows Tailwind CDN (v3 script & style), Chart.js CDN, and local assets securely
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com data:; " .
               "img-src 'self' data: https://images.unsplash.com; " .
               "connect-src 'self' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; " .
               "frame-ancestors 'none';";
        header("Content-Security-Policy: " . $csp);

        // Strict-Transport-Security (HSTS)
        if (SESSION_SECURE) {
            header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
        }

        $next();
    }
}
