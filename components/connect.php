<?php
/**
 * Smart E-Learning Web Platform - Global Bootstrap & Connection Manager
 */

// Load absolute paths to secure config, security middleware, and multilingual matrix
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/security.php';
require_once __DIR__ . '/../middleware/lang.php';

// Unify Student authentication state check
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_COOKIE['user_id'])) {
    // Session restoration for back-compatibility
    $user_id = sanitize_input($_COOKIE['user_id']);
    $_SESSION['user_id'] = $user_id;
} else {
    $user_id = '';
}

// Unify Tutor authentication state check
if (isset($_SESSION['tutor_id'])) {
    $tutor_id = $_SESSION['tutor_id'];
} elseif (isset($_COOKIE['tutor_id'])) {
    $tutor_id = sanitize_input($_COOKIE['tutor_id']);
    $_SESSION['tutor_id'] = $tutor_id;
} else {
    $tutor_id = '';
}

// Automatic CSRF token processing on standard POST forms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($bypass_csrf)) {
    // If not specifically bypassed, check CSRF token
    security_csrf_check();
}
?>