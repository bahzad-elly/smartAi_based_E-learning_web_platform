<?php
/**
 * Smart E-Learning Web Platform - API Dispatcher & Router Entry Point
 */

require_once '../config/db.php';
require_once '../middleware/security.php';

header("Content-Type: application/json; charset=UTF-8");

// Verify CORS and requests
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Basic endpoint parsing
$path_parts = explode('api/index.php', $request_uri);
$endpoint = isset($path_parts[1]) ? trim($path_parts[1], '/') : '';
$endpoint_parts = explode('/', $endpoint);
$resource = isset($endpoint_parts[0]) ? $endpoint_parts[0] : '';

// Placeholder router response
$response = [
    "status" => "success",
    "message" => "Smart E-Learning REST API Gateway is Online",
    "timestamp" => time(),
    "requested_resource" => $resource
];

switch ($resource) {
    case 'chatbot':
        // Part 12: AI Chatbot Integration endpoint
        // Decode JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $user_message = isset($input['message']) ? trim($input['message']) : '';
        if (empty($user_message)) {
            echo json_encode(["status" => "error", "message" => "Empty message."]);
            exit;
        }

        // Fetch courses for recommendation context
        $courses_list = [];
        try {
            $stmt = $conn->prepare("SELECT title FROM `playlists` WHERE status = 'active' LIMIT 5");
            $stmt->execute();
            $courses_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            // Fallback to static list if database query fails
            $courses_list = ['Web Development', 'React Mastery', 'Database Basics', 'UI/UX Design'];
        }

        $openai_api_key = getenv('OPENAI_API_KEY') ?: '';
        $bot_reply = '';

        if (!empty($openai_api_key)) {
            // Call OpenAI chat API
            $data = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are EducaBot, the AI teaching assistant for the Educa Smart E-Learning Platform. Explain lessons, recommend courses from our current active list: ' . implode(', ', $courses_list) . '. Provide concise and helpful learning suggestions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $user_message
                    ]
                ],
                'temperature' => 0.7
            ];

            try {
                $ch = curl_init('https://api.openai.com/v1/chat/completions');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $openai_api_key
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                
                $res = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code === 200 && $res) {
                    $res_data = json_decode($res, true);
                    $bot_reply = $res_data['choices'][0]['message']['content'] ?? '';
                }
            } catch (Exception $curl_ex) {
                // cURL failed
            }
        }

        // If OpenAI is unconfigured or failed, use rule-based intelligent fallback
        if (empty($bot_reply)) {
            $bot_reply = generate_chatbot_fallback_response($user_message, $courses_list);
        }

        echo json_encode([
            "status" => "success",
            "reply" => $bot_reply
        ]);
        exit;
        
    case 'chat':
        // Part 10: Private Chat endpoint
        $response['message'] = "Real-time chat endpoint placeholder.";
        break;
        
    case 'notifications':
        // Part 11: Real-time notifications endpoint
        $response['message'] = "Notifications API placeholder.";
        break;
        
    case 'likes':
    case 'comments':
    case 'bookmarks':
        // Part 9: AJAX interaction endpoints
        $response['message'] = "AJAX features endpoint placeholder.";
        break;
        
    default:
        if (!empty($resource)) {
            http_response_code(404);
            $response['status'] = "error";
            $response['message'] = "Endpoint '{$resource}' not found.";
        }
        break;
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// ── AI CHATBOT FALLBACK ENGINE ───────────────────────────
function generate_chatbot_fallback_response($message, $courses) {
    $msg = strtolower($message);
    
    if (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false || strpos($msg, 'hey') !== false) {
        return "Hello! I am your AI learning assistant. How can I help you with your studies today?";
    }
    
    if (strpos($msg, 'course') !== false || strpos($msg, 'recommend') !== false || strpos($msg, 'study') !== false) {
        if (!empty($courses)) {
            return "Based on your interests, I recommend checking out our top courses: **" . implode("**, **", $courses) . "**. You can find them on our [Courses page](courses.php)!";
        }
        return "I recommend exploring our [Courses page](courses.php) to find topics matching your goals like Web Development or UI/UX Design!";
    }
    
    if (strpos($msg, 'cert') !== false || strpos($msg, 'verify') !== false) {
        return "Upon passing any course quiz with the minimum required score, you can view and download your certificate from your profile. Anyone can verify certificates using their unique code on the [Certificate Verification page](verify_certificate.php).";
    }

    if (strpos($msg, 'quiz') !== false || strpos($msg, 'exam') !== false) {
        return "Each playlist has an associated quiz. You can access them from the [Quizzes page](quiz.php). Quizzes are timed and automatically graded!";
    }

    if (strpos($msg, 'explain') !== false || strpos($msg, 'lesson') !== false) {
        return "To explain a lesson, please navigate to the [Courses page](courses.php), select a playlist, and open the lesson you want to learn. Under each lesson, you can also leave questions in the comments!";
    }

    if (strpos($msg, 'who are you') !== false || strpos($msg, 'your name') !== false) {
        return "I am **EducaBot**, the AI assistant here to help you navigate courses, explain content, and maximize your learning experience!";
    }

    return "That's an interesting question! As an AI assistant, I suggest reviewing the relevant course lessons or asking your tutor in the Chat Room for a direct answer. How else can I assist you?";
}
?>
