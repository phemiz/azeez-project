# GSM Guard: AI-Powered GSM Cyber Security & Data Protection Platform

An enterprise-grade telecommunication security management gateway built to protect, encrypt, and monitor GSM message transmissions against cellular anomalies (like IMSI Catchers and Type 0 silent SMS trackers) and application security threats.

---

## 1. Project Directory Structure

```
├── app/
│   ├── config/          # Application configuration settings
│   │   └── config.php   # System parameter definitions
│   ├── controllers/     # MVC Controller classes
│   │   ├── AuthController.php      # Operator authentication pipeline
│   │   ├── DashboardController.php # Message cryptographic console
│   │   └── AdminController.php     # root administrator controls
│   ├── core/            # System core framework components
│   │   ├── Database.php # Singleton PDO prepared statement wrapper
│   │   ├── Router.php   # Regex route dispatcher
│   │   ├── Controller.php # Base controller helpers
│   │   ├── Model.php    # Base model configuration
│   │   └── Session.php  # Anti-CSRF and session hardening guard
│   ├── middleware/      # Interceptor pipeline classes
│   │   ├── SecurityMiddleware.php   # CSP, X-Frame-Options policies
│   │   ├── CSRFMiddleware.php       # Modifying requests anti-forgery
│   │   ├── RateLimitMiddleware.php  # Sliding window request throttle
│   │   └── AuthMiddleware.php       # MFA and Role access controller
│   ├── models/          # MVC Database Model abstractions
│   │   ├── User.php     # Operator credential mapping
│   │   ├── AuditLog.php # Activity log ledger model
│   │   └── Message.php  # Encrypted messages index model
│   ├── services/        # Cryptographic and analytics engines
│   │   ├── EncryptionService.php   # AES-256-CBC and HMAC signature
│   │   ├── OTPService.php          # 2FA code generation and hashing
│   │   └── AIEngine.php            # Heuristic threat analysis engine
│   └── views/           # UI presentation templates
│       ├── layouts/
│       │   └── layout.php          # Master responsive dark skeleton
│       ├── auth/
│       │   ├── login.php           # operator authentication viewport
│       │   ├── register.php        # operator node enrollment panel
│       │   └── otp.php             # MFA challenge and simulated device
│       ├── user/
│       │   └── dashboard.php       # Encryption and decrypt workspace
│       └── admin/
│           └── admin.php           # Backups, suspensions, and metrics
├── backups/             # Storage for SQL snapshot database backups
├── database/            # Relational migrations and blueprints
│   ├── database_design.md  # Data Dictionary & Normalization proofs
│   └── schema.sql          # 16-table 3NF schema build script
├── logs/                # System execution error and security logs
├── public/              # Document Root (Web Exposed)
│   ├── index.php        # Unified entrance front controller
│   └── .htaccess        # Apache rewrite URL interceptor rules
├── tests/               # Unit testing and diagnostic validations
│   └── security_tests.php  # Standalone cryptographic & heuristics tests
├── design-system/       # UI/UX visual style masters
│   ├── gsm-guard/
│   │   └── MASTER.md    # Master color, text, spacing tokens
│   └── DESIGN_SYSTEM.md # Detailed design system specifications
├── .env                 # Application environment credentials
├── .gitignore           # Version control folder exclusion rules
└── README.md            # Installation & structural overview
```

---

## 2. File and Folder Specifications

### `app/`
Contains the application source code organized under the Model-View-Controller (MVC) layout.
- **`config/`**: Houses central static settings. `config.php` maps all variables.
- **`core/`**: System runtime engine components. Enforces single entry routing, prepared database statements, and encrypted session tracking.
- **`middleware/`**: Defensive interceptors validating rate limits, CSRF state tokens, and security response headers.
- **`controllers/`**: Regulates program flow and resolves interactions between models, security services, and view layers.
- **`models/`**: Integrates the database connector to query and manipulate entity tables (`users`, `encrypted_messages`, `activity_logs`).
- **`services/`**: Holds core security classes: `EncryptionService` (AES/HMAC), `OTPService` (random hashes), and `AIEngine` (anomaly classifiers).
- **`views/`**: Renders structural layouts, user dashboards, administrative panels, and simulated carrier hardware.

### `database/`
Contains the relational schema scripts and 3NF normalization proofs.
- **`schema.sql`**: Generates the 16 tables, constraints, indexes, and test operator seeds.
- **`database_design.md`**: Hosts the complete Data Dictionary, relationship maps, and optimization parameters.

### `public/`
The only web-exposed directory. Serves as the document root.
- **`index.php`**: Registers end-points, initializes the autoloader, and boots the router.
- **`.htaccess`**: Forces Apache to redirect all non-file URLs to `index.php`.

### `tests/`
- **`security_tests.php`**: Automated verification test runner checking cryptography correctness, tamper signatures, SQLi regular expressions, and base station anomaly evaluations.

---

## 3. Technology Stack & Prerequisites

*   **Runtime Environment:** Apache HTTP Server on XAMPP (Windows).
*   **Database:** MySQL / MariaDB 10.4+.
*   **Backend Interpreter:** PHP 8.0+.
*   **Frontend Engine:** HTML5 with Tailwind CSS (Play CDN) and Vanilla JS.
*   **Telemetry Visuals:** Chart.js and Lucide Icons.

---

## 4. Deployment and Installation

1.  **Repository Setup:** Copy the project folder into your XAMPP web root directory (`C:/xampp/htdocs/gsm-security`).
2.  **Run Setup Wizard:** Open the browser-based environment checker and configuration wizard:
    `http://localhost/gsm-security/public/install.php`
    This will automatically verify system diagnostics, prompt for database details, write `.env` configs, import database tables, and activate a locking mechanism.
3.  **Run Automated Tests:** Run the test suite executor:
    ```bash
    php tests/run.php
    ```
4.  **Production Hardening & Deployment Guide**: Refer to [DEPLOYMENT.md](file:///c:/xampp/htdocs/gsm-security/DEPLOYMENT.md) for Nginx configs, OPcache tuning, directory permission commands, and PHP.ini hardening recommendations.
