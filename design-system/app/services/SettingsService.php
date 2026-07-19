<?php
namespace App\Services;

use App\Core\Database;

/**
 * Enterprise System Settings Service
 * Manages core configuration parameters (General, Security, Encryption, OTP, Session, AI, Backups, SMTP)
 * caching key-value maps inside the relational DB system.
 */
class SettingsService {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Gets a single settings value by key, inserting default value if missing.
     */
    public function get(string $key, string $default = ''): string {
        $val = $this->db->fetchColumn("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
        if ($val === false) {
            $this->set($key, $default);
            return $default;
        }
        return (string)$val;
    }

    /**
     * Returns all settings parameters as an associative array.
     */
    public function getAll(): array {
        $defaults = [
            'app_name'              => 'GSM Guard',
            'app_logo'              => 'logo_default.png',
            'time_zone'             => 'UTC',
            'theme'                 => 'dark',
            'security_level'        => 'high',
            'mfa_requirement'       => 'forced',
            'session_timeout'       => '900',
            'ai_detection_level'    => 'high',
            'backup_retention_days' => '30',
            'smtp_host'             => 'smtp.gsmsecurity.local',
            'smtp_port'             => '587',
            'smtp_user'             => 'alerts@gsmsecurity.local',
            'smtp_pass'             => '********',
            'encryption_algorithm'   => 'AES-256-CBC'
        ];

        $settings = [];
        foreach ($defaults as $key => $defVal) {
            $settings[$key] = $this->get($key, $defVal);
        }

        return $settings;
    }

    /**
     * Updates or inserts a settings parameter.
     */
    public function set(string $key, string $value): void {
        $exists = $this->db->fetch("SELECT id FROM system_settings WHERE setting_key = ?", [$key]);
        if ($exists) {
            $this->db->query("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        } else {
            $this->db->query("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
        }
    }
}
