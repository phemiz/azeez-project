<?php
namespace App\Core;

/**
 * Interface MiddlewareInterface
 * Standardizes middleware handlers across the application
 */
interface MiddlewareInterface {
    /**
     * Handles the request middleware logic
     * 
     * @param callable $next The next middleware handler
     */
    public function handle(callable $next): void;
}
