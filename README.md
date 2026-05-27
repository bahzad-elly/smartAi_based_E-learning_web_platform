# Smart AI-Based E-Learning Web Platform - Developer Portfolio

This is my graduation-level E-Learning Platform, custom-engineered using PHP 8, MySQL, AJAX, JavaScript, and Artificial Intelligence integrations. In this document, I describe my development workflow, architectural modifications, database models, and security integrations.

---

## 🛠️ Section 1: Modern Folder Structure (Part 1)

To bring the project up to professional, production-grade enterprise standards, I restructured the directory layouts into a clean, modular MVC-like layout. 

### My New Directory Architecture:
*   `/config/` — Contains global application and database configurations.
    *   `db.php` — My secure, shared database driver that handles custom PDO setups.
    *   `course_db.sql` — Backup copy of my SQL schema.
*   `/middleware/` — Contains core processing classes and route authorization checks.
    *   `security.php` — My security guard, managing anti-XSS, anti-CSRF, session protection, and HTTP headers.
    *   `lang.php` — My multilingual controller negotiating dynamic translations.
*   `/locales/` — Stores localized translation key-value mappings in raw JSON arrays:
    *   `en.json` (English), `ku.json` (Kurdish), and `ar.json` (Arabic).
*   `/api/` — Serving as the central gateway for secure REST endpoints.
    *   `index.php` — My API request proxy router for AJAX, Chat, AI Chatbot, and Notifications.
*   `/public/assets/` — Modern asset layouts containing subfolders for `css/`, `js/`, and static `images/`.

### My Global Application Bootstrap Hook:
I completely refactored `components/connect.php` to act as the unified bootstrap manager. Now, by calling this file:
1.  I dynamically initialize the secure database PDO instance from `/config/db.php`.
2.  I inject HTTP security headers (CSP, XSS Protection, Frame Ancestors) and session anti-hijack checks from `/middleware/security.php`.
3.  I load the appropriate translation dictionaries based on locale parameters from `/middleware/lang.php`.
4.  I run automated security middleware checks on incoming POST requests to validate CSRF tokens, shielding every page against Cross-Site Request Forgery (CSRF).

---

## 💾 Section 2: Highly Normalized 24-Table Schema (Part 2)

I designed and deployed a comprehensive database structure in `course_db.sql` that scales perfectly to graduation requirements. I migrated table engines to InnoDB, enforced complete foreign key integrity with appropriate delete cascades, and established composite and performance indexes.

### My Relational Table Inventory:
1.  **Core Profiles:** `users` (students), `instructors` (profession tutors), `admins` (platform staff).
2.  **Asset Classifications:** `categories` (course segments) and `playlists` (mentor groups).
3.  **Academic Contents:** `courses` (playlist wrappers with pricing/difficulty levels) and `lessons` (lesson videos).
4.  **Social Interactions:** `comments` (video logs), `replies` (nested comment threads), `likes` (lesson counts), `bookmarks` (saved playlists), and `course_reviews` (course rating scales).
5.  **Exams & Quizzes:** `quizzes` (time-limited tests), `questions` (individual questions), `answers` (multiple-choice or true/false options), and `exam_results` (history of scores).
6.  **Graduations:** `certificates` (storing unique codes and QR-validation verification keys).
7.  **Real-Time Communications:** `chats` (message session headers), `messages` (chat bubbles with attachments), and `notifications` (unread/read dashboard alerts).
8.  **Platform Business Logs:** `reports` (revenue metrics) and `logs` (security auditing trails for logins/action changes).

---

## 🔒 Section 3: Secure AJAX Authentication System (Part 3)

I built a bulletproof authentication, session management, and verification engine supporting seamless student and tutor operations.

### Key Security Features I Implemented:
1.  **Robust Cryptography (bcrypt):** I retired the outdated `sha1` hash functions. All passwords are now salted and cryptographically hashed using native PHP `password_hash($pass, PASSWORD_BCRYPT)` and checked securely via `password_verify()`.
2.  **Anti-CSRF Tokens:** I embedded unique token injections (`csrf_input_render()`) into forms and validate them before processing (`security_csrf_check()`).
3.  **Session Hijacking Protections:** In my security middleware, I encrypt the user's User-Agent and IP configuration on login, matching it on every request to prevent active session hijacking.
4.  **XSS & Input Sanitization:** I built a recursive input clean filter (`sanitize_input()`) to clean form posts and prevent XSS script injections.

### Key Functional Features I Created:
1.  **Asynchronous AJAX Forms:** I refactored `login.php` and `register.php` (both student and tutor portals) to output clean JSON structures when requested via AJAX. 
2.  **Simulated OTP Password Resets:** I created `forgot_password.php` and `reset_password.php`. If an account exists, I generate a random 6-digit OTP code, save it with a 5-minute expiry in session, simulate a mail dispatch in a visual notification block, and require validation to set a new password.
3.  **Mock Google OAuth Single Sign-On:** I integrated a mock "Sign in with Google" button. When clicked, it renders a high-fidelity Google Account Select popup modal, executes standard OAuth loader animations, and safely injects the selected student or tutor profile into the active session.
4.  **Dynamic UI Toast Controls (`public/assets/js/auth.js`):** I intercepted form submissions using the Fetch API, added progress-loader spinner updates on input buttons, and rendered beautiful notification toast blocks to report execution states dynamically without refreshing the page.

---

## 🚀 How to Run and Test My Code

### 1. Database Installation
1. Open your local database manager (e.g. phpMyAdmin).
2. Create a new database named `course_db`.
3. Import the `course_db.sql` file located in my project root directory.

### 2. Standard Server Setup
1. Move the project folder inside your web server directory (e.g. `C:/xampp/htdocs/project`).
2. Boot up your local PHP and MySQL servers.
3. Navigate to `http://localhost/project/login.php` to access my student portal, or `http://localhost/project/admin/login.php` to access my tutor dashboard.

### 3. Testing My Features
*   **Sign up with standard forms:** Register a student/tutor. Notice the dynamic AJAX toast verification and profile picture uploads in `/uploaded_files`.
*   **Sign in with Google:** Click the Google button on the login/register forms, click a profile, and enjoy the smooth OAuth loading simulations.
*   **Simulated OTP:** Click "Forgot password" on the student login form, input a registered email, copy the simulated 6-digit OTP code shown in the notification, and reset your password securely.
*   **Multi-language selection:** Test my multi-language negotiation by calling `?lang=ku` (Kurdish) or `?lang=ar` (Arabic) and watch the page orientation transition to RTL dynamically.
