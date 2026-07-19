<?php
namespace Tests\Framework;

/**
 * Base Class for Custom Test Suites
 * Implements basic assertions and test hook declarations.
 */
abstract class BaseTest {
    protected int $assertionsCount = 0;

    /**
     * Executes before each test method runs.
     */
    public function setUp(): void {}

    /**
     * Executes after each test method runs.
     */
    public function tearDown(): void {}

    public function getAssertionsCount(): int {
        return $this->assertionsCount;
    }

    // --- Assertions Utilities ---

    protected function assertEquals($expected, $actual, string $message = ''): void {
        $this->assertionsCount++;
        if ($expected !== $actual) {
            throw new \Exception($message ?: "Failed asserting that " . json_encode($actual) . " matches expected " . json_encode($expected));
        }
    }

    protected function assertTrue(bool $condition, string $message = ''): void {
        $this->assertionsCount++;
        if ($condition !== true) {
            throw new \Exception($message ?: "Failed asserting that condition is true.");
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void {
        $this->assertionsCount++;
        if ($condition !== false) {
            throw new \Exception($message ?: "Failed asserting that condition is false.");
        }
    }

    protected function assertNotNull($value, string $message = ''): void {
        $this->assertionsCount++;
        if ($value === null) {
            throw new \Exception($message ?: "Failed asserting that value is not null.");
        }
    }

    protected function assertStringContains(string $needle, string $haystack, string $message = ''): void {
        $this->assertionsCount++;
        if (strpos($haystack, $needle) === false) {
            throw new \Exception($message ?: "Failed asserting that '{$haystack}' contains '{$needle}'");
        }
    }
}
