<?php
/**
 * Smart AI E-Learning – Live Search API
 * GET  ?q=keyword  → JSON array of matching courses & lessons
 */

$bypass_csrf = true;
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/security.php';

$q = isset($_GET['q']) ? sanitize_input(trim($_GET['q'])) : '';

if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

try {
    // Search courses (playlists)
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.thumb, t.name AS tutor_name
        FROM `playlist` p
        LEFT JOIN `tutors` t ON t.id = p.tutor_id
        WHERE p.title LIKE ? AND p.status = 'active'
        ORDER BY p.date DESC
        LIMIT 5
    ");
    $stmt->execute([$like]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'type'       => 'course',
            'id'         => $row['id'],
            'title'      => $row['title'],
            'thumb'      => 'uploaded_files/' . $row['thumb'],
            'tutor_name' => $row['tutor_name'] ?? '',
            'url'        => 'playlist.php?get_id=' . $row['id'],
            'icon'       => 'fa-graduation-cap',
        ];
    }

    // Search video lessons
    $stmt2 = $conn->prepare("
        SELECT c.id, c.title, c.thumb, t.name AS tutor_name
        FROM `content` c
        LEFT JOIN `tutors` t ON t.id = c.tutor_id
        WHERE c.title LIKE ? AND c.status = 'active'
        ORDER BY c.date DESC
        LIMIT 4
    ");
    $stmt2->execute([$like]);
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'type'       => 'lesson',
            'id'         => $row['id'],
            'title'      => $row['title'],
            'thumb'      => 'uploaded_files/' . $row['thumb'],
            'tutor_name' => $row['tutor_name'] ?? '',
            'url'        => 'watch_video.php?get_id=' . $row['id'],
            'icon'       => 'fa-play-circle',
        ];
    }

    echo json_encode(['results' => $results, 'query' => $q]);
} catch (Exception $e) {
    echo json_encode(['results' => [], 'error' => 'Search failed.']);
}
