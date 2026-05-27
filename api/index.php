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
        $response['message'] = "AI Chatbot endpoint placeholder.";
        break;
        
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
?>
