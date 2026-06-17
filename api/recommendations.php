<?php
/**
 * Part 13 — AI Recommendation System
 * Recommends courses based on: watch history, bookmarks, quiz scores, enrollments
 * Actions: for_you | trending | popular
 */

include '../components/connect.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? trim($_GET['action']) : 'for_you';

// Collect user-interest category IDs from watch history + bookmarks
$user_category_ids   = [];
$enrolled_playlist_ids = [];

if (!empty($user_id)) {

    // Categories from lessons the user has watched (completed or in-progress)
    $stmt = $conn->prepare("
        SELECT DISTINCT c.category_id
        FROM   user_progress up
        JOIN   lessons      l  ON l.id          = up.lesson_id
        JOIN   courses      c  ON c.playlist_id = l.playlist_id
        WHERE  up.user_id = ?
          AND  c.category_id IS NOT NULL
        LIMIT  15
    ");
    $stmt->execute([$user_id]);
    $user_category_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Categories from bookmarked playlists
    $stmt2 = $conn->prepare("
        SELECT DISTINCT c.category_id
        FROM   bookmarks b
        JOIN   courses   c ON c.playlist_id = b.playlist_id
        WHERE  b.user_id = ?
          AND  c.category_id IS NOT NULL
    ");
    $stmt2->execute([$user_id]);
    $bm_cats           = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    $user_category_ids = array_unique(array_merge($user_category_ids, $bm_cats));

    // Playlists already enrolled in (exclude from recommendations)
    $stmt3 = $conn->prepare("
        SELECT DISTINCT c.playlist_id
        FROM   enrollments e
        JOIN   courses     c ON c.id = e.course_id
        WHERE  e.user_id = ?
    ");
    $stmt3->execute([$user_id]);
    $enrolled_playlist_ids = $stmt3->fetchAll(PDO::FETCH_COLUMN);
}

// ────────────────────────────────────────────────────────────────────
// Helper: build exclusion SQL fragment & param array
// ────────────────────────────────────────────────────────────────────
function build_exclude_clause(array $ids, string $col = 'p.id'): array
{
    if (empty($ids)) {
        return ['', []];
    }
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    return ["AND $col NOT IN ($ph)", $ids];
}

// ====================================================================
//  ACTION: for_you  — personalised (falls back to trending)
// ====================================================================
if ($action === 'for_you') {

    $recommended = [];
    $label       = 'trending';

    if (!empty($user_category_ids)) {
        $cat_ph   = implode(',', array_fill(0, count($user_category_ids), '?'));
        [$ex_sql, $ex_params] = build_exclude_clause($enrolled_playlist_ids);

        $params = array_merge(['active'], $user_category_ids, $ex_params);

        $stmt4 = $conn->prepare("
            SELECT DISTINCT
                   p.id, p.title, p.thumb, p.tutor_id,
                   i.name  AS tutor_name,
                   i.image AS tutor_image,
                   COUNT(DISTINCT e.user_id) AS enrollment_count
            FROM   playlists    p
            JOIN   courses      c ON c.playlist_id = p.id
            JOIN   instructors  i ON i.id           = p.tutor_id
            LEFT JOIN enrollments e ON e.course_id  = c.id
            WHERE  p.status = ?
              AND  c.category_id IN ($cat_ph)
              $ex_sql
            GROUP BY p.id
            ORDER BY enrollment_count DESC
            LIMIT  6
        ");
        $stmt4->execute($params);
        $recommended = $stmt4->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($recommended)) {
            $label = 'personalized';
        }
    }

    // Top up with trending if fewer than 6
    if (count($recommended) < 6) {
        $need        = 6 - count($recommended);
        $seen_ids    = array_merge($enrolled_playlist_ids, array_column($recommended, 'id'));
        [$ex2, $ep2] = build_exclude_clause($seen_ids);
        $params2     = array_merge(['active'], $ep2);

        $stmt5 = $conn->prepare("
            SELECT p.id, p.title, p.thumb, p.tutor_id,
                   i.name  AS tutor_name,
                   i.image AS tutor_image,
                   COUNT(DISTINCT e.user_id) AS enrollment_count
            FROM   playlists   p
            JOIN   instructors i ON i.id          = p.tutor_id
            LEFT JOIN courses  c ON c.playlist_id = p.id
            LEFT JOIN enrollments e ON e.course_id = c.id
            WHERE  p.status = ? $ex2
            GROUP BY p.id
            ORDER BY enrollment_count DESC, p.id DESC
            LIMIT  $need
        ");
        $stmt5->execute($params2);
        $trending    = $stmt5->fetchAll(PDO::FETCH_ASSOC);
        $recommended = array_merge($recommended, $trending);
    }

    echo json_encode(['status' => 'success', 'label' => $label, 'courses' => $recommended]);
    exit;
}

// ====================================================================
//  ACTION: trending  — most enrolled globally
// ====================================================================
if ($action === 'trending') {
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.thumb, p.tutor_id,
               i.name  AS tutor_name,
               i.image AS tutor_image,
               COUNT(DISTINCT e.user_id) AS enrollment_count
        FROM   playlists   p
        JOIN   instructors i ON i.id          = p.tutor_id
        LEFT JOIN courses  c ON c.playlist_id = p.id
        LEFT JOIN enrollments e ON e.course_id = c.id
        WHERE  p.status = 'active'
        GROUP BY p.id
        ORDER BY enrollment_count DESC, p.id DESC
        LIMIT  6
    ");
    $stmt->execute();
    echo json_encode(['status' => 'success', 'label' => 'trending', 'courses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ====================================================================
//  ACTION: popular  — highest-rated (by course_reviews)
// ====================================================================
if ($action === 'popular') {
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.thumb, p.tutor_id,
               i.name  AS tutor_name,
               i.image AS tutor_image,
               ROUND(AVG(cr.rating), 1) AS avg_rating,
               COUNT(cr.id)             AS review_count
        FROM   playlists     p
        JOIN   instructors   i  ON i.id          = p.tutor_id
        JOIN   courses       c  ON c.playlist_id = p.id
        JOIN   course_reviews cr ON cr.course_id  = c.id
        WHERE  p.status = 'active'
        GROUP BY p.id
        HAVING review_count >= 1
        ORDER BY avg_rating DESC, review_count DESC
        LIMIT  6
    ");
    $stmt->execute();
    echo json_encode(['status' => 'success', 'label' => 'popular', 'courses' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
