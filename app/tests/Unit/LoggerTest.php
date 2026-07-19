<?php
namespace Tests\Unit;

use Tests\Framework\BaseTest;
use App\Core\Logger;

/**
 * Unit Test for Logging Framework multi-channel files
 */
class LoggerTest extends BaseTest {
    private string $logDir;

    public function setUp(): void {
        $this->logDir = dirname(dirname(__DIR__)) . '/logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function testLoggerChannelsAndLevels(): void {
        $logger = Logger::getInstance();
        
        $testMessage = "Authentication testing event trigger.";
        $logger->info(Logger::CHANNEL_AUTH, $testMessage, ['test' => true]);

        $logFile = $this->logDir . '/auth.log';
        $this->assertTrue(file_exists($logFile), "Auth channel file must be created.");

        $content = file_get_contents($logFile);
        $this->assertStringContains('[AUTH] [INFO]', $content);
        $this->assertStringContains($testMessage, $content);
        $this->assertStringContains('"test":true', $content);
    }
}
