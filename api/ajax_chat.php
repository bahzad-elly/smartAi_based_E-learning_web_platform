<?php
/**
 * Smart AI E-Learning – AJAX Chat API (Part 10)
 * Handles retrieving messages, sending messages (with optional attachments), and managing status/typing activity.
 */

// Bypass global auto post-check if checking ourselves manually
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

$sender_type = !empty($current_user_id) ? 'user' : 'tutor';
$my_id = ($sender_type === 'user') ? $current_user_id : $current_tutor_id;

$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

switch ($action) {
    case 'fetch_contacts':
        // Fetch contacts (instructors for users, or users for instructors)
        try {
            if ($sender_type === 'user') {
                // Return all instructors/tutors
                $stmt = $conn->prepare("SELECT id, name, profession, image FROM `tutors` ORDER BY name ASC");
                $stmt->execute();
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add status info & last message
                foreach ($contacts as &$contact) {
                    $contact['last_msg'] = get_last_message($conn, $my_id, $contact['id']);
                    $contact['online'] = is_user_online($conn, $contact['id'], 'tutor');
                }
            } else {
                // Return users that have sent messages to this instructor
                $stmt = $conn->prepare("
                    SELECT DISTINCT u.id, u.name, u.image 
                    FROM `users` u
                    INNER JOIN `messages` m ON (m.sender_type = 'user' AND m.chat_id IN (
                        SELECT id FROM `chats` WHERE tutor_id = ?
                    ) AND u.id = (
                        SELECT user_id FROM `chats` WHERE id = m.chat_id
                    ))
                    ORDER BY u.name ASC
                ");
                $stmt->execute([$my_id]);
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Add status info & last message
                foreach ($contacts as &$contact) {
                    $contact['last_msg'] = get_last_message($conn, $contact['id'], $my_id);
                    $contact['online'] = is_user_online($conn, $contact['id'], 'user');
                }
            }
            echo json_encode(['status' => 'success', 'contacts' => $contacts]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'fetch_messages':
        $target_id = isset($_GET['target_id']) ? sanitize_input($_GET['target_id']) : '';
        if (empty($target_id)) {
            echo json_encode(['error' => 'Invalid target user.']);
            exit;
        }

        try {
            $chat_id = get_or_create_chat($conn, $current_user_id, $current_tutor_id, $target_id, $sender_type);
            
            // Fetch messages for this chat
            $stmt = $conn->prepare("SELECT * FROM `messages` WHERE chat_id = ? ORDER BY created_at ASC LIMIT 100");
            $stmt->execute([$chat_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Update recipient status (simulate heartbeat)
            update_heartbeat($conn, $my_id, $sender_type);

            echo json_encode([
                'status' => 'success',
                'chat_id' => $chat_id,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'send_message':
        // POST request requires CSRF validation
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!csrf_token_validate($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF validation failed.']);
            exit;
        }

        $target_id = isset($_POST['target_id']) ? sanitize_input($_POST['target_id']) : '';
        $message_text = isset($_POST['message']) ? trim($_POST['message']) : '';

        if (empty($target_id)) {
            echo json_encode(['error' => 'No recipient targeted.']);
            exit;
        }

        // Handle attachment upload
        $attachment = '';
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['attachment']['name'];
            $file_size = $_FILES['attachment']['size'];
            $file_tmp = $_FILES['attachment']['tmp_name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['png', 'jpg', 'jpeg', 'pdf', 'zip', 'doc', 'docx', 'txt'];
            if (!in_array($ext, $allowed_ext)) {
                echo json_encode(['error' => 'File type not allowed.']);
                exit;
            }

            if ($file_size > 5000000) { // 5MB limit
                echo json_encode(['error' => 'File exceeds limit (5MB).']);
                exit;
            }

            $attachment = unique_id() . '.' . $ext;
            $upload_path = __DIR__ . '/../uploaded_files/' . $attachment;
            move_uploaded_file($file_tmp, $upload_path);
        }

        if (empty($message_text) && empty($attachment)) {
            echo json_encode(['error' => 'Cannot send an empty message.']);
            exit;
        }

        try {
            $chat_id = get_or_create_chat($conn, $current_user_id, $current_tutor_id, $target_id, $sender_type);
            $msg_id = unique_id();

            $stmt = $conn->prepare("INSERT INTO `messages` (id, chat_id, sender_type, message, attachment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$msg_id, $chat_id, $sender_type, $message_text, $attachment]);

            echo json_encode([
                'status' => 'success',
                'message' => [
                    'id' => $msg_id,
                    'chat_id' => $chat_id,
                    'sender_type' => $sender_type,
                    'message' => htmlspecialchars($message_text),
                    'attachment' => $attachment,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'typing':
        $target_id = isset($_POST['target_id']) ? sanitize_input($_POST['target_id']) : '';
        $is_typing = isset($_POST['is_typing']) ? (int)$_POST['is_typing'] : 0;
        
        // Save typing state in session or database logs. Since session is local, we store it in a temp session key
        // Accessible to simulation
        if (!empty($target_id)) {
            $_SESSION['typing_state_' . $my_id . '_' . $target_id] = [
                'time' => time(),
                'is_typing' => $is_typing
            ];
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['error' => 'Missing fields.']);
        }
        break;

    case 'status':
        $target_id = isset($_GET['target_id']) ? sanitize_input($_GET['target_id']) : '';
        if (empty($target_id)) {
            echo json_encode(['error' => 'Invalid target.']);
            exit;
        }

        $target_type = ($sender_type === 'user') ? 'tutor' : 'user';
        $online = is_user_online($conn, $target_id, $target_type);

        // Check typing state of recipient targeting me
        $typing_key = 'typing_state_' . $target_id . '_' . $my_id;
        $typing = false;
        if (isset($_SESSION[$typing_key])) {
            $typing_info = $_SESSION[$typing_key];
            if (time() - $typing_info['time'] < 4 && $typing_info['is_typing'] == 1) {
                $typing = true;
            }
        }

        echo json_encode([
            'status' => 'success',
            'online' => $online,
            'typing' => $typing
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action requested.']);
        break;
}

// ── UTILITIES ────────────────────────────────────────

function get_or_create_chat($conn, $user_id, $tutor_id, $target_id, $sender_type) {
    if ($sender_type === 'user') {
        $u_id = $user_id;
        $t_id = $target_id;
    } else {
        $u_id = $target_id;
        $t_id = $tutor_id;
    }

    $stmt = $conn->prepare("SELECT id FROM `chats` WHERE user_id = ? AND tutor_id = ? LIMIT 1");
    $stmt->execute([$u_id, $t_id]);
    $chat = $stmt->fetch();

    if ($chat) {
        return $chat['id'];
    } else {
        $new_id = unique_id();
        $ins = $conn->prepare("INSERT INTO `chats` (id, user_id, tutor_id) VALUES (?, ?, ?)");
        $ins->execute([$new_id, $u_id, $t_id]);
        return $new_id;
    }
}

function get_last_message($conn, $user_id, $tutor_id) {
    $stmt = $conn->prepare("
        SELECT message, created_at FROM `messages` 
        WHERE chat_id = (SELECT id FROM `chats` WHERE user_id = ? AND tutor_id = ? LIMIT 1)
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $tutor_id]);
    $msg = $stmt->fetch();
    return $msg ? $msg['message'] : 'No messages yet';
}

function update_heartbeat($conn, $my_id, $type) {
    // Record active activity in session or database logs
    $_SESSION['last_activity_' . $type . '_' . $my_id] = time();
}

function is_user_online($conn, $user_id, $type) {
    // Check local session state
    $key = 'last_activity_' . $type . '_' . $user_id;
    if (isset($_SESSION[$key])) {
        return (time() - $_SESSION[$key]) < 15; // Active in last 15 seconds
    }
    return false;
}
