<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\EncryptionService;
use App\Services\AIEngine;

/**
 * Controller managing User Operations: Message Encryption, Decryption, Logs, and Heuristic profiling
 */
class DashboardController extends Controller {
    private Database $db;
    private EncryptionService $crypto;
    private AIEngine $aiEngine;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->crypto = new EncryptionService();
        $this->aiEngine = new AIEngine();
    }

    public function index(): void {
        $user = Session::get('user');
        
        // Redirect admins to their customized control portal
        if ($user['role'] === 'admin') {
            $this->redirect(APP_URL . '/admin');
        }

        // Fetch user activity logs
        $logs = $this->db->fetchAll(
            "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$user['id']]
        );

        // Fetch user encrypted messages (both sent and received)
        $userId = $user['id'];
        $username = $user['username'] ?? '';
        $phone = $user['phone'] ?? '';
        
        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        $shortPhone = $numericPhone;
        if (str_starts_with($numericPhone, '234') && strlen($numericPhone) > 3) {
            $shortPhone = substr($numericPhone, 3);
        }
        $localPhone = '0' . $shortPhone;

        $messages = $this->db->fetchAll(
            "SELECT e.*, u.username AS sender_username 
             FROM encrypted_messages e
             LEFT JOIN users u ON e.sender_id = u.id
             WHERE e.sender_id = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR (e.recipient LIKE ? AND LENGTH(?) > 5)
             ORDER BY e.created_at DESC LIMIT 10",
            [
                $userId,
                $username,
                $phone,
                $numericPhone,
                $shortPhone,
                $localPhone,
                '%' . $shortPhone,
                $shortPhone
            ]
        );

        // Calculate User Security Metrics
        $totalEncrypted = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM encrypted_messages WHERE sender_id = ?",
            [$user['id']]
        );

        $maxRiskScore = (int)$this->db->fetchColumn(
            "SELECT MAX(score) FROM risk_scores WHERE user_id = ?",
            [$user['id']]
        );

        $alertCount = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM security_alerts WHERE user_id = ? AND severity IN ('high', 'critical')",
            [$user['id']]
        );

        // Fetch last login log (previous to current)
        $lastLogin = $this->db->fetch(
            "SELECT created_at, ip_address FROM activity_logs 
             WHERE user_id = ? AND action LIKE 'login_success%' 
             ORDER BY created_at DESC LIMIT 1 OFFSET 1",
            [$user['id']]
        );

        // Fetch recent security alerts
        $alerts = $this->db->fetchAll(
            "SELECT * FROM security_alerts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
            [$user['id']]
        );

        // Run AI Risk Engine Evaluation on current user session
        $riskEngine = new \App\Services\RiskEngine();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $riskAssessment = $riskEngine->evaluateRisk($user['id'], $ip, $ua);

        $this->view('user/dashboard', [
            'title'          => 'GSM Encryptor Portal',
            'user'           => $user,
            'logs'           => $logs,
            'messages'       => $messages,
            'totalEncrypted' => $totalEncrypted,
            'maxRiskScore'   => $maxRiskScore,
            'alertCount'     => $alertCount,
            'lastLogin'      => $lastLogin,
            'alerts'         => $alerts,
            'riskAssessment' => $riskAssessment
        ]);
    }

    public function showEncrypt(): void {
        $user = Session::get('user');
        if ($user['role'] === 'admin') {
            $this->redirect(APP_URL . '/admin');
        }

        $this->view('user/encrypt', [
            'title' => 'GSM Payload Enveloping Terminal',
            'user'  => $user
        ]);
    }

    public function showDecrypt(): void {
        $user = Session::get('user');
        if ($user['role'] === 'admin') {
            $this->redirect(APP_URL . '/admin');
        }

        // Fetch user message envelope history (both sent and received) for parameter populating
        $userId = $user['id'];
        $username = $user['username'] ?? '';
        $phone = $user['phone'] ?? '';
        
        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        $shortPhone = $numericPhone;
        if (str_starts_with($numericPhone, '234') && strlen($numericPhone) > 3) {
            $shortPhone = substr($numericPhone, 3);
        }
        $localPhone = '0' . $shortPhone;

        $messages = $this->db->fetchAll(
            "SELECT e.*, u.username AS sender_username 
             FROM encrypted_messages e
             LEFT JOIN users u ON e.sender_id = u.id
             WHERE e.sender_id = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR e.recipient = ? 
                OR (e.recipient LIKE ? AND LENGTH(?) > 5)
             ORDER BY e.created_at DESC LIMIT 10",
            [
                $userId,
                $username,
                $phone,
                $numericPhone,
                $shortPhone,
                $localPhone,
                '%' . $shortPhone,
                $shortPhone
            ]
        );

        $this->view('user/decrypt', [
            'title'    => 'GSM Payload Decapsulation Terminal',
            'user'     => $user,
            'messages' => $messages
        ]);
    }

    public function showRecommendations(): void {
        $user = Session::get('user');
        if ($user['role'] === 'admin') {
            $this->redirect(APP_URL . '/admin');
        }

        $recommendationEngine = new \App\Services\RecommendationEngine();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $recommendations = $recommendationEngine->generateRecommendations($user['id'], $ip, $ua);

        $this->view('user/recommendations', [
            'title'           => 'AI Security Recommendations',
            'user'            => $user,
            'recommendations' => $recommendations
        ]);
    }

    /**
     * API endpoint to encrypt GSM messages and audit the action using AI threat grading
     */
    public function encryptMessage(): void {
        $user = Session::get('user');
        $recipient = $this->getPost('recipient');
        $message = $this->getPost('message');
        $passphrase = $this->getPost('passphrase', '');

        // Capture simulated GSM meta fields if supplied, or instantiate default values
        $smsc = $this->getPost('smsc', '1000000000'); // Default SMSC
        $protocolId = $this->getPost('protocol_id', '0'); // Default SMS

        if (empty($recipient) || empty($message)) {
            $this->json(['status' => 'error', 'message' => 'Recipient and message body are required.'], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // 1. AI Threat Profiling on transaction payload & metadata (checking for IMSI catchers / script threats)
        $payloadToAnalyze = [
            'message' => $message,
            'recipient' => $recipient,
            'gsm_metadata' => [
                'smsc' => $smsc,
                'protocol_id' => $protocolId
            ]
        ];

        $aiProfile = $this->aiEngine->analyzeSecurityEvent($user['id'], 'encrypt_message', $ip, $ua, $payloadToAnalyze);

        // Log the encryption activity under the evaluated AI risk parameters
        $this->db->query(
            "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
             VALUES (?, 'encrypt_message', ?, ?, ?, ?, ?, ?)",
            [
                $user['id'], 
                $ip, 
                $ua, 
                $aiProfile['risk_score'], 
                $aiProfile['threat_classification'], 
                $aiProfile['severity'], 
                $aiProfile['threat_details']
            ]
        );

        // Reject request if risk profile is critical (prevent data exfiltration or transmission via spoofed towers)
        if ($aiProfile['risk_score'] >= AI_CRITICAL_RISK_THRESHOLD) {
            $this->json([
                'status' => 'error', 
                'message' => 'Encryption request blocked by AI Engine: ' . $aiProfile['threat_classification'] . '. Security recommendation: ' . $aiProfile['recommendations']
            ], 403);
        }

        // 2. Perform AES-256-CBC Encryption
        try {
            $encrypted = $this->crypto->encrypt($message, $passphrase);

            // Store message footprint in db (never store raw key/passphrase)
            $this->db->query(
                "INSERT INTO encrypted_messages (sender_id, recipient, encrypted_payload, iv, salt, signature) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $user['id'],
                    $recipient,
                    $encrypted['ciphertext'],
                    $encrypted['iv'],
                    $encrypted['salt'],
                    $encrypted['signature']
                ]
            );

            $this->json([
                'status'       => 'success',
                'message'      => 'Message encrypted and queued successfully.',
                'ciphertext'   => $encrypted['ciphertext'],
                'iv'           => $encrypted['iv'],
                'salt'         => $encrypted['salt'],
                'signature'    => $encrypted['signature'],
                'ai_profile'   => $aiProfile
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => 'Cryptographic error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to decrypt secure messages and analyze integrity metrics
     */
    public function decryptMessage(): void {
        $user = Session::get('user');
        $ciphertext = $this->getPost('ciphertext');
        $iv = $this->getPost('iv');
        $salt = $this->getPost('salt');
        $signature = $this->getPost('signature');
        $passphrase = $this->getPost('passphrase', '');

        if (empty($ciphertext) || empty($iv) || empty($salt) || empty($signature)) {
            $this->json(['status' => 'error', 'message' => 'Cryptographic values (ciphertext, IV, salt, signature) are required.'], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // AI Threat Profiling on decryption inputs
        $aiProfile = $this->aiEngine->analyzeSecurityEvent($user['id'], 'decrypt_message', $ip, $ua, ['ciphertext' => $ciphertext]);

        // Attempt decryption
        try {
            $plaintext = $this->crypto->decrypt($ciphertext, $iv, $salt, $signature, $passphrase);

            // Log successful decryption
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'decrypt_success', ?, ?, 0, 'Normal', 'low', 'Message decrypted successfully')",
                [$user['id'], $ip, $ua]
            );

            $this->json([
                'status'    => 'success',
                'message'   => 'Message decrypted successfully.',
                'plaintext' => $plaintext
            ]);
        } catch (\SecurityException $se) {
            // HMAC signature verification failed: Possible packet tampering or cipher manipulation
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'decrypt_failed_tampered', ?, ?, 75, 'Ciphertext Tampering Attempt', 'high', 'HMAC integrity verification failed. Potential MITM attack')",
                [$user['id'], $ip, $ua]
            );

            $this->json(['status' => 'error', 'message' => 'Integrity verification failed. Ciphertext has been tampered with or corrupted.'], 400);
        } catch (\Exception $e) {
            // Standard decryption failure (wrong passphrase / bad key)
            $this->db->query(
                "INSERT INTO activity_logs (user_id, action, ip_address, user_agent, risk_score, threat_classification, severity, threat_details) 
                 VALUES (?, 'decrypt_failed', ?, ?, 25, 'Incorrect Key Guess', 'medium', 'Decryption failed (bad key or passphrase)')",
                [$user['id'], $ip, $ua]
            );

            $this->json(['status' => 'error', 'message' => 'Decryption failed. Ensure the passphrase is correct.'], 400);
        }
    }
}
