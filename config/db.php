<?php
/**
 * Smart E-Learning Web Platform - Database Configuration & PDO Manager
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'course_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $db_dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Establishing a secure and persistent PDO connection
    $conn = new PDO($db_dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Error handling through exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Standardizing associative array fetching
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Disabling emulate prepares for SQL Injection safety
    ]);
} catch (PDOException $e) {
    // Graceful error handling in case of connection failure
    die("Database Connection Failure: " . $e->getMessage());
}

/**
 * Utility function to generate secure, unique cryptographic 20-character IDs
 * Falls back to mt_rand only if random_bytes is not available
 */
if (!function_exists('unique_id')) {
    function unique_id() {
        if (function_exists('random_bytes')) {
            try {
                return substr(bin2hex(random_bytes(10)), 0, 20);
            } catch (Exception $e) {
                // Fallback
            }
        }
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $rand = [];
        $length = strlen($str) - 1;
        for ($i = 0; $i < 20; $i++) {
            $n = mt_rand(0, $length);
            $rand[] = $str[$n];
        }
        return implode('', $rand);
    }
}
?>
