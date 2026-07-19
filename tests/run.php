<?php
/**
 * Test Execution Core Gateway
 * Runs the TestRunner across all test categories and returns an exit code matching suite success status.
 */

// Load bootstrapping environment
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/framework/BaseTest.php';
require_once __DIR__ . '/framework/TestRunner.php';

use Tests\Framework\TestRunner;

$runner = new TestRunner();

// Run all directories recursively
$testDir = __DIR__;
$runner->runDirectory($testDir . '/Unit', 'Unit Tests');
$runner->runDirectory($testDir . '/Integration', 'Integration Tests');
$runner->runDirectory($testDir . '/System', 'System Tests');
$runner->runDirectory($testDir . '/Performance', 'Performance Tests');
$runner->runDirectory($testDir . '/Security', 'Security Tests');
$runner->runDirectory($testDir . '/UAT', 'User Acceptance Tests');
$runner->runDirectory($testDir . '/Regression', 'Regression Tests');

// Render final colored dashboard report
$success = $runner->renderReport();

// Return clean exit code for CLI automation pipelines (CI/CD check)
exit($success ? 0 : 1);
