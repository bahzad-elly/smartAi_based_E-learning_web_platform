<?php
/**
 * Smart AI E-Learning – AJAX Comment API
 * POST  action=add|delete|edit  + data  → JSON response
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
    echo json_encode(['error' => 'login_required', 'message' => 'Please login to comment.']);
    exit;
}

$action = sanitize_input($_POST['action'] ?? '');

// ── ADD COMMENT ──────────────────────────────────────────────
if ($action === 'add') {
    $content_id  = sanitize_input($_POST['content_id'] ?? '');
    $comment_raw = trim($_POST['comment_box'] ?? '');

    if (empty($content_id) || empty($comment_raw)) {
        echo json_encode(['error' => 'Missing required fields.']);
        exit;
    }

    // XSS-safe but allow basic text
    $comment_text = htmlspecialchars(substr($comment_raw, 0, 1000), ENT_QUOTES, 'UTF-8');

    try {
        // Get tutor_id from content
        $sc = $conn->prepare("SELECT tutor_id FROM `content` WHERE id = ? LIMIT 1");
        $sc->execute([$content_id]);
        $content = $sc->fetch(PDO::FETCH_ASSOC);
        if (!$content) { echo json_encode(['error' => 'Content not found']); exit; }
        $tutor_id = $content['tutor_id'];

        // Duplicate check
        $dup = $conn->prepare("SELECT id FROM `comments` WHERE content_id=? AND user_id=? AND comment=? LIMIT 1");
        $dup->execute([$content_id, $user_id, $comment_text]);
        if ($dup->rowCount() > 0) {
            echo json_encode(['error' => 'You already posted this comment.']);
            exit;
        }

        $new_id = bin2hex(random_bytes(8));
        $conn->prepare("INSERT INTO `comments`(id, content_id, user_id, tutor_id, comment) VALUES(?,?,?,?,?)")
             ->execute([$new_id, $content_id, $user_id, $tutor_id, $comment_text]);

        // Fetch user for the response
        $usr = $conn->prepare("SELECT name, image FROM `users` WHERE id = ?");
        $usr->execute([$user_id]);
        $user = $usr->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success'      => true,
            'comment_id'   => $new_id,
            'comment_text' => $comment_text,
            'user_name'    => htmlspecialchars($user['name'] ?? 'You'),
            'user_image'   => 'uploaded_files/' . ($user['image'] ?? 'default.png'),
            'date'         => date('Y-m-d H:i:s'),
            'message'      => 'Comment posted!',
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }

// ── DELETE COMMENT ───────────────────────────────────────────
} elseif ($action === 'delete') {
    $comment_id = sanitize_input($_POST['comment_id'] ?? '');
    if (empty($comment_id)) { echo json_encode(['error' => 'Missing comment_id']); exit; }

    try {
        // Must own the comment
        $check = $conn->prepare("SELECT id FROM `comments` WHERE id = ? AND user_id = ?");
        $check->execute([$comment_id, $user_id]);
        if ($check->rowCount() === 0) {
            echo json_encode(['error' => 'Comment not found or not yours.']);
            exit;
        }
        $conn->prepare("DELETE FROM `comments` WHERE id = ?")->execute([$comment_id]);
        echo json_encode(['success' => true, 'message' => 'Comment deleted.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }

// ── EDIT / UPDATE COMMENT ────────────────────────────────────
} elseif ($action === 'edit') {
    $comment_id   = sanitize_input($_POST['comment_id'] ?? '');
    $comment_raw  = trim($_POST['comment_box'] ?? '');
    if (empty($comment_id) || empty($comment_raw)) { echo json_encode(['error' => 'Missing fields']); exit; }
    $comment_text = htmlspecialchars(substr($comment_raw, 0, 1000), ENT_QUOTES, 'UTF-8');

    try {
        $check = $conn->prepare("SELECT id FROM `comments` WHERE id = ? AND user_id = ?");
        $check->execute([$comment_id, $user_id]);
        if ($check->rowCount() === 0) {
            echo json_encode(['error' => 'Comment not found or not yours.']);
            exit;
        }
        $conn->prepare("UPDATE `comments` SET comment = ? WHERE id = ?")->execute([$comment_text, $comment_id]);
        echo json_encode(['success' => true, 'comment_text' => $comment_text, 'message' => 'Comment updated!']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error']);
    }

} else {
    echo json_encode(['error' => 'Unknown action.']);
}
