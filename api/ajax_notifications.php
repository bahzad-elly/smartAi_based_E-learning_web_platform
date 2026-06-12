<?php
/**
 * Smart AI E-Learning – AJAX Notifications API (Part 11)
 * Handles retrieving notifications and marking them as read.
 */

$bypass_csrf = true; 
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../components/connect.php';

// Ensure the caller is either a logged-in user or instructor
$current_user_id = $user_id;
$current_tutor_id = $tutor_id;

if (empty($current_user_id) && empty($current_tutor_id)) {
    http_response_code(401);
    echo json_encode(['error' => 'login_required']);
    exit;
}

$role = !empty($current_user_id) ? 'user' : 'tutor';
$my_id = ($role === 'user') ? $current_user_id : $current_tutor_id;

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : 'fetch';

switch ($action) {
    case 'fetch':
        try {
            if ($role === 'user') {
                // Fetch student notifications (specific to student or global)
                $stmt = $conn->prepare("
                    SELECT * FROM `notifications` 
                    WHERE (user_id = ? OR (user_id IS NULL AND tutor_id IS NULL)) 
                    ORDER BY created_at DESC LIMIT 10
                ");
                $stmt->execute([$my_id]);
            } else {
                // Fetch instructor notifications
                $stmt = $conn->prepare("
                    SELECT * FROM `notifications` 
                    WHERE tutor_id = ? 
                    ORDER BY created_at DESC LIMIT 10
                ");
                $stmt->execute([$my_id]);
            }
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Count unread
            if ($role === 'user') {
                $count_stmt = $conn->prepare("
                    SELECT COUNT(*) FROM `notifications` 
                    WHERE (user_id = ? OR (user_id IS NULL AND tutor_id IS NULL)) AND status = 'unread'
                ");
                $count_stmt->execute([$my_id]);
            } else {
                $count_stmt = $conn->prepare("
                    SELECT COUNT(*) FROM `notifications` 
                    WHERE tutor_id = ? AND status = 'unread'
                ");
                $count_stmt->execute([$my_id]);
            }
            $unread_count = $count_stmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'notifications' => $notifications,
                'unread_count' => (int)$unread_count
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'mark_read':
        $notif_id = isset($_POST['id']) ? sanitize_input($_POST['id']) : '';
        
        try {
            if ($notif_id === 'all') {
                if ($role === 'user') {
                    $stmt = $conn->prepare("
                        UPDATE `notifications` SET status = 'read' 
                        WHERE (user_id = ? OR (user_id IS NULL AND tutor_id IS NULL)) AND status = 'unread'
                    ");
                    $stmt->execute([$my_id]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE `notifications` SET status = 'read' 
                        WHERE tutor_id = ? AND status = 'unread'
                    ");
                    $stmt->execute([$my_id]);
                }
            } else {
                // Mark single notification
                if ($role === 'user') {
                    $stmt = $conn->prepare("
                        UPDATE `notifications` SET status = 'read' 
                        WHERE id = ? AND (user_id = ? OR user_id IS NULL)
                    ");
                    $stmt->execute([$notif_id, $my_id]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE `notifications` SET status = 'read' 
                        WHERE id = ? AND tutor_id = ?
                    ");
                    $stmt->execute([$notif_id, $my_id]);
                }
            }

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'add':
        // Secure token validation
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!csrf_token_validate($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF validation failed.']);
            exit;
        }

        $target_user = isset($_POST['user_id']) ? sanitize_input($_POST['user_id']) : null;
        $target_tutor = isset($_POST['tutor_id']) ? sanitize_input($_POST['tutor_id']) : null;
        $title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
        $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

        if (empty($title) || empty($message)) {
            echo json_encode(['error' => 'Title and message are required.']);
            exit;
        }

        try {
            $id = unique_id();
            $stmt = $conn->prepare("
                INSERT INTO `notifications` (id, user_id, tutor_id, title, message, status) 
                VALUES (?, ?, ?, ?, ?, 'unread')
            ");
            $stmt->execute([$id, $target_user, $target_tutor, $title, $message]);

            echo json_encode(['status' => 'success', 'notification_id' => $id]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
        break;
}
