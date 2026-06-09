<?php
/**
 * Smart AI E-Learning – AJAX Bookmark Toggle API
 * POST  playlist_id + csrf_token  → JSON {bookmarked}
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/security.php';

if (session_status() == PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'] ?? ($_COOKIE['user_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF validation
$token = $_POST['csrf_token'] ?? '';
if (!csrf_token_validate($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF validation failed']);
    exit;
}

if (empty($user_id)) {
    echo json_encode(['error' => 'login_required', 'message' => 'Please login to bookmark courses.']);
    exit;
}

$playlist_id = sanitize_input($_POST['playlist_id'] ?? '');
if (empty($playlist_id)) {
    echo json_encode(['error' => 'Missing playlist_id']);
    exit;
}

try {
    // Verify playlist exists
    $sp = $conn->prepare("SELECT id FROM `playlist` WHERE id = ? AND status = 'active' LIMIT 1");
    $sp->execute([$playlist_id]);
    if ($sp->rowCount() === 0) {
        echo json_encode(['error' => 'Playlist not found']);
        exit;
    }

    // Check if already bookmarked
    $check = $conn->prepare("SELECT id FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
    $check->execute([$user_id, $playlist_id]);

    if ($check->rowCount() > 0) {
        // Remove bookmark
        $conn->prepare("DELETE FROM `bookmark` WHERE user_id = ? AND playlist_id = ?")->execute([$user_id, $playlist_id]);
        $bookmarked = false;
        $message = 'Removed from bookmarks!';
    } else {
        // Add bookmark
        $conn->prepare("INSERT INTO `bookmark`(user_id, playlist_id) VALUES(?,?)")->execute([$user_id, $playlist_id]);
        $bookmarked = true;
        $message = 'Added to bookmarks!';
    }

    echo json_encode(['bookmarked' => $bookmarked, 'message' => $message]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
