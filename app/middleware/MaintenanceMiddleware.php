<?php
namespace App\Middleware;

use App\Core\Session;
use App\Core\MiddlewareInterface;
use App\Services\SettingsService;

/**
 * Maintenance Mode Gating Middleware
 * Blocks access for non-admin operators when system maintenance mode is active.
 */
class MaintenanceMiddleware implements MiddlewareInterface {
    public function handle(callable $next): void {
        Session::start();
        $settings = new SettingsService();
        $isMaintenance = $settings->get('maintenance_mode', 'off') === 'on';

        if ($isMaintenance) {
            $user = Session::get('user');
            $isAdmin = $user && ($user['role'] === 'admin');

            // Allow administrators to bypass maintenance block to edit systems configs
            if (!$isAdmin) {
                http_response_code(503);
                
                $isJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
                if ($isJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'System is currently undergoing scheduled security maintenance. Please try again shortly.'
                    ]);
                    exit;
                }

                // Render high-fidelity premium maintenance splash dashboard
                ?>
                <!DOCTYPE html>
                <html lang="en" style="background-color: #030712; color: #f3f4f6; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; overflow: hidden;">
                <div style="max-width: 600px; padding: 40px; background: rgba(17,24,39,0.7); border: 1px solid rgba(6,182,212,0.15); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); text-align: center; backdrop-filter: blur(12px);">
                    <div style="background: rgba(6,182,212,0.08); border: 1px solid rgba(6,182,212,0.25); display: inline-flex; padding: 16px; border-radius: 50%; margin-bottom: 24px; animation: pulse 2s infinite;">
                        <svg style="width: 36px; height: 36px; color: #06b6d4;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h1 style="color: #ffffff; font-size: 26px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 12px 0; font-family: monospace;">System Maintenance</h1>
                    <p style="font-size: 14px; line-height: 1.6; color: #9ca3af; margin-bottom: 24px;">
                        The GSM Guard secure data platform is undergoing scheduled systems upgrades. Core channels are offline for optimization. Please return shortly.
                    </p>
                    <div style="margin-bottom: 20px; font-size: 11px; font-family: monospace; color: #06b6d4; letter-spacing: 0.5px;">
                        GATEWAY_STATUS: MAINTENANCE_ACTIVE &middot; TIMESTEP: <?= date('Y-m-d H:i:s') ?>
                    </div>
                    <style>
                        @keyframes pulse {
                            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(6,182,212,0.4); }
                            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(6,182,212,0); }
                            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(6,182,212,0); }
                        }
                    </style>
                </div>
                </html>
                <?php
                exit;
            }
        }

        $next();
    }
}
