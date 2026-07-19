# GSM Guard: Simple Project Documentation Updates (Grade 8 Reading Level)
This document explains how **GSM Guard** works in a simple and easy-to-understand way.

---

## CHAPTER THREE: SYSTEM ANALYSIS, DESIGN, AND METHODOLOGY (UPDATES)

### 3.6.1 How the System is Built (System Architecture)
GSM Guard is built using a structured software design style called **MVC (Model-View-Controller)**. Think of it like a restaurant:
*   **The View (The Waiter)**: This is what the user sees on their screen (like the login box or the dashboard).
*   **The Controller (The Kitchen Manager)**: This takes the user's clicks and inputs, decides what to do, and tells other parts of the program what to do.
*   **The Model (The Pantry)**: This is where the database data is stored and managed.

```
                  +----------------------------------------------+
                  |                  User Screen                 |
                  +----------------------+-----------------------+
                                         |
                                         v
                  +----------------------+-----------------------+
                  |             Main Gatekeeper                  |
                  |                (public/index.php)            |
                  +----------------------+-----------------------+
                                         |
                                         v
                  +----------------------+-----------------------+
                  |               Traffic Director               |
                  |                 (app/core/Router.php)        |
                  +----------------------+-----------------------+
                                         |
                                         v
                  +----------------------+-----------------------+
                  |            Security Guard Middleware         |
                  |  - Limit how fast users can click/try logs   |
                  |  - Block hacker code using header rules      |
                  |  - Check anti-forgery keys                   |
                  |  - Check if logged in & passed 2FA (OTP)     |
                  +----------------------+-----------------------+
                                         |
                                         v
                  +----------------------+-----------------------+
                  |            Controller (Manager)              |
                  |     (AuthController, DashboardController)    |
                  +-------------------+--+-----------------------+
                                      |  |
                  +-------------------+  +-----------------------+
                  v                                              v
      +-----------+-----------+                     +------------+-----------+
      |      Model (Data)     |                     |   Security Services    |
      | (User, Message, Log)  |                     |  - Encrypter           |
      +-----------+-----------+                     |  - 2FA Code Generator  |
                  |                                 |  - Threat Scanner (AI) |
                  v                                 +------------------------+
      +-----------+-----------+
      |  Database (Storage)   |
      +-----------------------+
```

Here is how a request travels through the system:
1.  **Main Gatekeeper (`public/index.php`)**: When you open the website, you start here. This file loads all settings and makes sure the system is ready.
2.  **Traffic Director (`app/core/Router.php`)**: This looks at the page you requested and sends you to the correct manager.
3.  **Security Guard Middleware (`app/middleware/`)**: Before you get to the page, security guards check your access:
    *   **Rate Limiter**: Blocks computers that click too fast or try to guess passwords too many times.
    *   **Headers**: Keeps hackers from tricking your browser.
    *   **Anti-Forgery**: Makes sure malicious websites cannot send fake commands.
    *   **2FA check**: Checks if you completed the login code (OTP).
4.  **Controllers**: They manage the page actions, tell the encrypter to lock messages, and fetch saved data.
5.  **Security Services**: These do the heavy security work, like encrypting messages, sending OTP codes, and scanning for threats.
6.  **Database Wrapper (`app/core/Database.php`)**: Securely stores all your data in tables using templates that block SQL injection hacking.

---

### 3.10 Security Design

#### 1. Protecting Messages (AES-256 and HMAC)
GSM Guard protects your text messages using a secure method called **Encrypt-then-MAC**. It is like putting a letter in a locked steel box (Encryption) and then pasting a security seal over the keyhole (MAC):
*   **Creating the Key**: Instead of using a simple password, the system mixes your password with a random string (Salt) and runs it through a hash calculator 10,000 times to create a strong key.
*   **Locking the Message (AES-256)**: The system encrypts the message using AES-256, which is the same security level banks and governments use to protect secrets.
*   **Security Seal (HMAC)**: A digital signature is created from the locked box. If anyone tries to alter the encrypted message, the seal breaks, and the system refuses to decrypt it.

#### 2. One-Time Passwords (OTP)
*   **How it Works**: When you log in, the system generates a random 6-digit number (like `482915`).
*   **Secure Storage**: The system does not save the actual number in the database. Instead, it hashes it using a one-way password calculator so that if hackers steal the database, they cannot read your codes.
*   **Rules**: Old codes are instantly canceled so they cannot be reused. Each code is only valid for 5 minutes.

#### 3. Smart Threat Scanner (Risk Calculation)
The system has a smart scanner (`AIEngine`) that checks for threats and calculates a Risk Score from $0$ (completely safe) to $100$ (extremely dangerous):

| Threat Event | Risk Added | How the System Detects It |
| :--- | :---: | :--- |
| **Failed Logins (Medium)** | 45 | Detected 3 failed logins in 5 minutes from the same IP address. |
| **Failed Logins (Critical)** | 80 | Detected 5 failed logins in 5 minutes from the same IP address. |
| **Impossible Travel** | 40 | A user logs in from two distant locations too fast (e.g., USA and then Europe 10 minutes later). |
| **Session Hijacking** | 25 | A user's browser details change suddenly while they are logged in. |
| **SQL Injection** | 65 | Hacker input containing database commands (like `UNION SELECT`). |
| **Cross-Site Scripting (XSS)**| 50 | Hacker input containing browser code commands (like `<script>`). |
| **Fake Cell Towers** | 60 | A cell signal that does not include a valid routing center address (IMSI Catcher). |
| **Silent SMS Tracker** | 45 | A silent SMS message designed to track your location without your knowledge. |

---

## CHAPTER FOUR: SYSTEM IMPLEMENTATION, TESTING, AND RESULTS

### 4.2 Computers and Programs Used (Development Environment)
*   **Server Software**: XAMPP (runs Apache web server and PHP on your computer).
*   **Database**: MariaDB/MySQL (stores all system data).
*   **Frontend Design**: Tailwind CSS (makes pages look clean), Lucide (icons), and Chart.js (shows charts on the admin dashboard).

---

### 4.3 Database Storage Structure
The database is structured cleanly so that data is not repeated and stays organized. It has 17 tables:
1.  `users`: Stores usernames, emails, phone numbers, and hashed passwords.
2.  `admins`: Stores which users are administrators.
3.  `otp_codes`: Stores the temporary login codes.
4.  `encrypted_messages`: Stores the locked messages and security seals.
5.  `activity_logs`: Records what users do on the system.
6.  `security_alerts`: Records dangerous events detected by the system.
7.  `sessions`: Keeps users logged in securely.
8.  `password_resets`: Handles reset links if a user forgets their password.
9.  `login_attempts`: Counts successful and failed login attempts to block hackers.
10. `threat_reports`: Logs security incidents and risk scores.
11. `ai_recommendations`: Logs steps the system recommends to stay safe.
12. `risk_scores`: Keeps a history of risk levels.
13. `behavior_profiles`: Saves normal user habits to detect unusual changes.
14. `system_settings`: Stores settings like session timeouts.
15. `backup_history`: Records database backups.
16. `audit_trail`: Tracks changes to database tables.
17. `notifications`: Stores alerts to show to users.

#### Simple Data Dictionary

##### Table: `users` (User Accounts)
*   `id`: Unique user number.
*   `username`: Account name.
*   `email`: Email address.
*   `phone`: Mobile number.
*   `password_hash`: Locked password.
*   `status`: Account status (can be `active` or `suspended`).
*   `created_at`: Date the account was made.

##### Table: `otp_codes` (Verification Codes)
*   `id`: Code number.
*   `user_id`: Link to the user.
*   `code_hash`: Hashed login code.
*   `expires_at`: Expiry time.
*   `verified`: Whether it was used (`0 = unused`, `1 = used`).

##### Table: `encrypted_messages` (Encrypted Payloads)
*   `id`: Message number.
*   `sender_id`: User who sent it.
*   `recipient`: Phone number receiving the message.
*   `encrypted_payload`: The locked text.
*   `iv`, `salt`, `signature`: Security metadata and seals.

---

### 4.5 System Testing
We ran an automated testing script (`tests/security_tests.php`) to verify that the security features work correctly. The test results show **PASS** for all checks:

*   **Decryption Check [PASS]**: Verified that locked messages decrypt correctly when the correct password is used.
*   **Tampering Check [PASS]**: Confirmed that if someone changes even one letter of the locked message, the system detects it and blocks decryption.
*   **Database Attack Check [PASS]**: Verified that if a hacker tries to input SQL commands, the system detects the attack.
*   **Website Code Attack Check [PASS]**: Verified that browser code injections are detected.
*   **Fake Tower Check [PASS]**: Confirmed that signal packets with invalid routing numbers are flagged as IMSI-Catcher threats.
*   **Silent Tracker Check [PASS]**: Confirmed that Type 0 SMS signals used for tracking are identified.

---

## CHAPTER FIVE: SUMMARY, CONCLUSION, AND RECOMMENDATIONS

### 5.3 Conclusion
GSM Guard successfully creates multiple layers of safety to protect mobile messages. By encrypting messages with AES-256 and adding a security seal, the system keeps text messages private. The OTP codes verify that users are who they claim to be, and the threat scanner stops hackers, fake cell towers, and silent trackers.

Testing proved that combining multiple security features is much safer than relying on just one security measure.

### 5.4 Recommendations
1.  **Keep it Updated**: Update your database buffer settings on production servers to keep things running fast.
2.  **Change Master Keys**: Change the `APP_SECRET_KEY` setting periodically.
3.  **Use Secure Links**: Enforce HTTPS links to keep hackers from listening to your connection.
