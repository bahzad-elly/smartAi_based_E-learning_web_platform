<?php
/**
 * Smart E-Learning Web Platform - Security & Middleware Layer
 */

// 1. Establish Secure Session Settings
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1); // Protect cookies against XSS hijacking
    ini_set('session.use_only_cookies', 1);
    
    // Enable secure cookies only if HTTPS is present
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// 2. Prevent Session Hijacking (Verify User Agent & IP)
if (!isset($_SESSION['user_agent_signature'])) {
    $_SESSION['user_agent_signature'] = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
} else {
    if ($_SESSION['user_agent_signature'] !== md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'])) {
        // Unrecognized device configuration - destroy session for safety
        session_destroy();
        session_start();
    }
}

// 3. HTTP Security Hardening Headers
header("X-Frame-Options: SAMEORIGIN");                               // Avoid Clickjacking
header("X-Content-Type-Options: nosniff");                          // Avoid MIME sniffing
header("X-XSS-Protection: 1; mode=block");                          // Refuse reflected XSS execution
header("Referrer-Policy: strict-origin-when-cross-origin");          // Control header leaks
header("Content-Security-Policy: default-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data:; media-src 'self'; frame-ancestors 'none';");

/**
 * Clean user inputs recursively to prevent Cross-Site Scripting (XSS)
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a cryptographically secure CSRF Token
 */
function csrf_token_generate() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output an input element with the secure CSRF token
 */
function csrf_input_render() {
    $token = csrf_token_generate();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate submitted CSRF Token
 */
function csrf_token_validate($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate incoming request CSRF token automatically
 */
function security_csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!csrf_token_validate($token)) {
            http_response_code(403);
            die("Security Error: CSRF Validation Failed.");
        }
    }
}
?>
