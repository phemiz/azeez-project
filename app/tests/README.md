# GSM Guard Security System - Test Suite Guide

Welcome to the GSM Guard automated testing framework directory. This suite contains unit, integration, system, performance, security, UAT, and regression tests. It is built to run standalone via the CLI on production, staging, or local development environments.

---

## Directory Architecture

- **`framework/`**: Custom testing harness engine.
  - `BaseTest.php`: Base class containing assertions (`assertEquals`, `assertTrue`, etc.) and lifecycle hook hooks.
  - `TestRunner.php`: Dispatches tests, records duration/memory metrics, and renders the CLI dashboard.
- **`Unit/`**: Tests logic in isolated helper structures.
  - `EncryptionTest.php`: Verifies encryption, decryption, and integrity signatures.
  - `LoggerTest.php`: Verifies channel routing format.
- **`Integration/`**: Tests interaction with live interfaces.
  - `DatabaseConnectionTest.php`: Checks MySQL driver queries.
  - `SessionDatabaseTest.php`: Tests session lifecycle synchronization inside DB.
- **`System/`**: Tests full transactional flows.
  - `AuthenticationFlowTest.php`: Simulates registration, password hash checking, and user cleanup.
- **`Performance/`**: Tests resource usage constraints.
  - `CryptoThroughputTest.php`: Validates average roundtrip time for encryption is under 15ms.
- **`Security/`**: Simulates attack vectors.
  - `SQLiDetectionTest.php`: Tests AI detection of SQL injections.
  - `RateLimiterBlockTest.php`: Asserts rate limiting boundaries are stored correctly.
- **`UAT/`**: Acceptance scenario walkthroughs.
  - `OperatorSettingsUatTest.php`: Verifies maintenance mode setting toggling.
- **`Regression/`**: Preventative regression checkers.
  - `PasswordChangeRegressionTest.php`: Assures BCRYPT hash consistency.

---

## Executing the Test Suite

Run the following command from the root directory to execute all suites:

```bash
php tests/run.php
```

### CI/CD Integration
The runner returns exit code `0` on success and `1` on failure, allowing direct integration with deployment staging tasks or GitHub actions.
