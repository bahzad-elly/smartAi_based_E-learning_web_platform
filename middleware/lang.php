<?php
/**
 * Smart E-Learning Web Platform - Multilingual Localization Middleware
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$supported_locales = ['en', 'ku', 'ar'];
$default_locale = 'en';

// Determine Locale Preference
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_locales)) {
    $current_locale = $_GET['lang'];
    $_SESSION['lang'] = $current_locale;
    setcookie('lang', $current_locale, time() + (60 * 60 * 24 * 30), '/'); // Keep language for 30 days
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $supported_locales)) {
    $current_locale = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $supported_locales)) {
    $current_locale = $_COOKIE['lang'];
    $_SESSION['lang'] = $current_locale;
} else {
    $current_locale = $default_locale;
    $_SESSION['lang'] = $current_locale;
}

// Determine layout direction (RTL vs LTR)
$direction = ($current_locale === 'ar' || $current_locale === 'ku') ? 'rtl' : 'ltr';

// Load language translation definitions
$translation_file = dirname(__DIR__) . "/locales/{$current_locale}.json";
$translations = [];

if (file_exists($translation_file)) {
    $json_content = file_get_contents($translation_file);
    $translations = json_decode($json_content, true);
    if (!is_array($translations)) {
        $translations = [];
    }
}

/**
 * Global localization translator function.
 * Matches string keys to the active translation dictionary file.
 */
if (!function_exists('__')) {
    function __($key) {
        global $translations;
        return isset($translations[$key]) ? $translations[$key] : $key;
    }
}
?>
