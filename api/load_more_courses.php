<?php
/**
 * Smart AI E-Learning – Load More Courses API
 * GET  ?offset=N&limit=6  → JSON array of course card HTML
 */

$bypass_csrf = true;
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/security.php';

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit  = min(12, max(1, (int)($_GET['limit']  ?? 6)));
$search = sanitize_input(trim($_GET['q'] ?? ''));

try {
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $conn->prepare("
            SELECT p.*, t.name AS tutor_name, t.image AS tutor_image
            FROM `playlist` p
            LEFT JOIN `tutors` t ON t.id = p.tutor_id
            WHERE p.status = 'active' AND p.title LIKE ?
            ORDER BY p.date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$like, $limit, $offset]);

        $countStmt = $conn->prepare("SELECT COUNT(*) FROM `playlist` WHERE status='active' AND title LIKE ?");
        $countStmt->execute([$like]);
    } else {
        $stmt = $conn->prepare("
            SELECT p.*, t.name AS tutor_name, t.image AS tutor_image
            FROM `playlist` p
            LEFT JOIN `tutors` t ON t.id = p.tutor_id
            WHERE p.status = 'active'
            ORDER BY p.date DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);

        $countStmt = $conn->prepare("SELECT COUNT(*) FROM `playlist` WHERE status='active'");
        $countStmt->execute();
    }

    $total = (int)$countStmt->fetchColumn();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cards = [];
    foreach ($courses as $c) {
        $cards[] = [
            'id'          => $c['id'],
            'title'       => htmlspecialchars($c['title']),
            'thumb'       => htmlspecialchars('uploaded_files/' . $c['thumb']),
            'tutor_name'  => htmlspecialchars($c['tutor_name'] ?? ''),
            'tutor_image' => htmlspecialchars('uploaded_files/' . ($c['tutor_image'] ?? '')),
            'date'        => $c['date'],
            'url'         => 'playlist.php?get_id=' . $c['id'],
        ];
    }

    echo json_encode([
        'courses'  => $cards,
        'total'    => $total,
        'offset'   => $offset,
        'has_more' => ($offset + $limit) < $total,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'courses' => []]);
}
