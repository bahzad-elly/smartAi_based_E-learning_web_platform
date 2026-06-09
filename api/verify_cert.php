<?php
/**
 * Smart AI E-Learning – AJAX Certificate Verification API
 * GET  ?code=CERT-XXXX  → JSON response with validity + details
 */

$bypass_csrf = true; // GET-only endpoint, no CSRF needed
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/security.php';

$cert_code = isset($_GET['code']) ? sanitize_input(strtoupper(trim($_GET['code']))) : '';

if (empty($cert_code)) {
    echo json_encode(['valid' => false, 'message' => 'No certificate code provided.']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.*,
               u.name  AS student_name,
               q.title AS quiz_title,
               p.title AS course_title
        FROM `certificates` c
        JOIN `users`   u ON u.id = c.user_id
        JOIN `quizzes` q ON q.id = c.quiz_id
        LEFT JOIN `playlist` p ON p.id = q.playlist_id
        WHERE c.certificate_code = ?
        LIMIT 1
    ");
    $stmt->execute([$cert_code]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cert) {
        echo json_encode([
            'valid'        => true,
            'student_name' => htmlspecialchars($cert['student_name']),
            'quiz_title'   => htmlspecialchars($cert['quiz_title']),
            'course_title' => htmlspecialchars($cert['course_title'] ?? 'General Studies'),
            'issued_date'  => date('F j, Y', strtotime($cert['issued_at'])),
            'cert_code'    => htmlspecialchars($cert['certificate_code']),
            'score'        => isset($cert['score']) ? (int)$cert['score'] : null,
        ]);
    } else {
        echo json_encode(['valid' => false, 'message' => 'Certificate not found. Please check the code and try again.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Server error. Please try again later.']);
}
