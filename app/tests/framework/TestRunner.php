<?php
namespace Tests\Framework;

/**
 * Enterprise Test Runner Engine
 * Scans directories, executes matching tests, tracks resources, and outputs console dashboard.
 */
class TestRunner {
    private array $results = [];
    private int $totalAssertions = 0;
    private float $startTime;

    public function __construct() {
        $this->startTime = microtime(true);
    }

    /**
     * Executes all test files inside a specific folder matching *Test.php
     */
    public function runDirectory(string $dirPath, string $suiteName): void {
        if (!is_dir($dirPath)) {
            return;
        }

        $files = scandir($dirPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $dirPath . '/' . $file;
            if (is_dir($fullPath)) {
                $this->runDirectory($fullPath, $suiteName);
                continue;
            }

            if (str_ends_with($file, 'Test.php')) {
                $this->runTestFile($fullPath, $suiteName);
            }
        }
    }

    /**
     * Runs a single test class file
     */
    private function runTestFile(string $filePath, string $suiteName): void {
        require_once $filePath;

        // Convert path to class name
        // e.g. tests/Unit/EncryptionTest.php -> Tests\Unit\EncryptionTest
        $normalizedPath = str_replace('\\', '/', $filePath);
        $normalizedBase = str_replace('\\', '/', dirname(dirname(__DIR__)));
        
        $relativePath = substr($normalizedPath, strlen($normalizedBase) + 1);
        $relativePath = str_replace('.php', '', $relativePath);
        
        $parts = explode('/', $relativePath);
        $className = '';
        foreach ($parts as $part) {
            $className .= '\\' . ucfirst($part);
        }

        if (!class_exists($className)) {
            return;
        }

        $testInstance = new $className();
        if (!$testInstance instanceof BaseTest) {
            return;
        }

        $classMethods = get_class_methods($className);
        $testMethods = array_filter($classMethods, function (string $method) {
            return str_starts_with($method, 'test');
        });

        foreach ($testMethods as $method) {
            $startMemory = memory_get_usage();
            $methodStartTime = microtime(true);

            $status = 'PASS';
            $errorMsg = '';

            try {
                $testInstance->setUp();
                $testInstance->$method();
                $testInstance->tearDown();
            } catch (\Throwable $t) {
                $status = 'FAIL';
                $errorMsg = $t->getMessage() . " in " . basename($t->getFile()) . ":" . $t->getLine();
            }

            $duration = microtime(true) - $methodStartTime;
            $memoryUsed = memory_get_usage() - $startMemory;

            $this->totalAssertions += $testInstance->getAssertionsCount();

            $this->results[$suiteName][] = [
                'class'       => basename($className),
                'method'      => $method,
                'status'      => $status,
                'error'       => $errorMsg,
                'duration_ms'=> round($duration * 1000, 2),
                'memory_kb'   => round($memoryUsed / 1024, 2)
            ];
        }
    }

    /**
     * Renders a highly detailed console output report
     */
    public function renderReport(): bool {
        $green = "\033[32m";
        $red = "\033[31m";
        $cyan = "\033[36m";
        $yellow = "\033[33m";
        $reset = "\033[0m";

        $totalTests = 0;
        $passed = 0;
        $failed = 0;

        echo "\n" . str_repeat('=', 70) . "\n";
        echo "                    GSM GUARD SECURITY SHIELD - TESTING SUITE\n";
        echo str_repeat('=', 70) . "\n\n";

        foreach ($this->results as $suite => $tests) {
            echo "{$cyan}[SUITE: " . strtoupper($suite) . "]{$reset}\n";
            echo str_repeat('-', 70) . "\n";

            foreach ($tests as $t) {
                $totalTests++;
                if ($t['status'] === 'PASS') {
                    $passed++;
                    $statusStr = "{$green}PASS{$reset}";
                    $metrics = " ({$t['duration_ms']}ms &middot; {$t['memory_kb']}KB)";
                    echo "  [+] {$t['class']} -> {$t['method']} ... {$statusStr}{$metrics}\n";
                } else {
                    $failed++;
                    $statusStr = "{$red}FAIL{$reset}";
                    echo "  [-] {$t['class']} -> {$t['method']} ... {$statusStr}\n";
                    echo "      {$yellow}Reason: {$t['error']}{$reset}\n";
                }
            }
            echo "\n";
        }

        $totalTime = round((microtime(true) - $this->startTime), 4);

        echo str_repeat('=', 70) . "\n";
        echo "TEST EXECUTION SUMMARY:\n";
        echo " - Total tests: {$totalTests}\n";
        echo " - Passed:      {$green}{$passed}{$reset}\n";
        echo " - Failed:      " . ($failed > 0 ? "{$red}{$failed}{$reset}" : "0") . "\n";
        echo " - Assertions:  {$this->totalAssertions}\n";
        echo " - Duration:    {$totalTime} seconds\n";
        echo str_repeat('=', 70) . "\n\n";

        return ($failed === 0);
    }
}
