<?php
/**
 * Smart AI E-Learning – AJAX Like Toggle API
 * POST  content_id + csrf_token  → JSON {liked, count}
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/security.php';

// Session & Auth
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
    echo json_encode(['error' => 'login_required', 'message' => 'Please login to like videos.']);
    exit;
}

$content_id = sanitize_input($_POST['content_id'] ?? '');
if (empty($content_id)) {
    echo json_encode(['error' => 'Missing content_id']);
    exit;
}

try {
    // Get tutor_id for the content
    $sc = $conn->prepare("SELECT tutor_id FROM `content` WHERE id = ? LIMIT 1");
    $sc->execute([$content_id]);
    $content = $sc->fetch(PDO::FETCH_ASSOC);
    if (!$content) {
        echo json_encode(['error' => 'Content not found']);
        exit;
    }
    $tutor_id = $content['tutor_id'];

    // Check if already liked
    $check = $conn->prepare("SELECT id FROM `likes` WHERE user_id = ? AND content_id = ?");
    $check->execute([$user_id, $content_id]);

    if ($check->rowCount() > 0) {
        // Remove like
        $conn->prepare("DELETE FROM `likes` WHERE user_id = ? AND content_id = ?")->execute([$user_id, $content_id]);
        $liked = false;
    } else {
        // Add like
        $conn->prepare("INSERT INTO `likes`(user_id, tutor_id, content_id) VALUES(?,?,?)")->execute([$user_id, $tutor_id, $content_id]);
        $liked = true;
    }

    // Get updated count
    $cnt = $conn->prepare("SELECT COUNT(*) FROM `likes` WHERE content_id = ?");
    $cnt->execute([$content_id]);
    $count = (int)$cnt->fetchColumn();

    echo json_encode(['liked' => $liked, 'count' => $count, 'message' => $liked ? 'Added to likes!' : 'Removed from likes!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
