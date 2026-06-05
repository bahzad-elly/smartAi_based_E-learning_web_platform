<?php
/**
 * Smart AI E-Learning - AJAX: Submit Quiz, Auto-Grade, Issue Certificate
 */

include '../components/connect.php';

// Must be logged in
if (empty($user_id)) {
    header('location: ../login.php');
    exit;
}

// Validate CSRF
// (connect.php runs security_csrf_check() automatically)

$quiz_id        = isset($_POST['quiz_id']) ? sanitize_input($_POST['quiz_id']) : '';
$total_from_post = (int)($_POST['total_questions'] ?? 0);

if (!$quiz_id) { header('location: ../quiz.php'); exit; }

// Fetch quiz info
$quiz_stmt = $conn->prepare("SELECT * FROM `quizzes` WHERE id = ? LIMIT 1");
$quiz_stmt->execute([$quiz_id]);
$quiz = $quiz_stmt->fetch();
if (!$quiz) { header('location: ../quiz.php'); exit; }

// Fetch all questions for this quiz (ordered same way – we re-fetch correct answers)
$questions = $conn->prepare("SELECT * FROM `questions` WHERE quiz_id = ?");
$questions->execute([$quiz_id]);
$all_q = $questions->fetchAll();
$total_questions = count($all_q);

// ── GRADE ──────────────────────────────────────────
$score = 0;
$detailed_results = [];

foreach ($all_q as $qi => $q) {
    $submitted_answer_id = isset($_POST['answer_' . $qi]) ? sanitize_input($_POST['answer_' . $qi]) : null;

    // Get correct answer(s) for this question
    $correct_stmt = $conn->prepare("SELECT * FROM `answers` WHERE question_id = ? AND is_correct = 1 LIMIT 1");
    $correct_stmt->execute([$q['id']]);
    $correct_ans = $correct_stmt->fetch();

    // Get all answers for review
    $all_ans = $conn->prepare("SELECT * FROM `answers` WHERE question_id = ?");
    $all_ans->execute([$q['id']]);

    $is_correct = false;
    if ($submitted_answer_id && $correct_ans && $submitted_answer_id === $correct_ans['id']) {
        $score++;
        $is_correct = true;
    }

    $detailed_results[] = [
        'question'       => $q['question_text'],
        'is_correct'     => $is_correct,
        'submitted_id'   => $submitted_answer_id,
        'correct_id'     => $correct_ans ? $correct_ans['id'] : null,
        'correct_text'   => $correct_ans ? $correct_ans['answer_text'] : 'N/A',
        'question_id'    => $q['id'],
    ];
}

$score_pct   = ($total_questions > 0) ? round($score / $total_questions * 100) : 0;
$pass_status = ($score_pct >= $quiz['passing_score']) ? 'pass' : 'fail';

// ── SAVE RESULT ────────────────────────────────────
$result_id = unique_id();
$ins = $conn->prepare("INSERT INTO `exam_results`(id, quiz_id, user_id, score, total_questions, status) VALUES(?,?,?,?,?,?)");
$ins->execute([$result_id, $quiz_id, $user_id, $score, $total_questions, $pass_status]);

// Clear session timer
$session_key = 'quiz_start_' . $quiz_id . '_' . $user_id;
unset($_SESSION[$session_key]);

// ── AUTO-ISSUE CERTIFICATE if passed ───────────────
$cert_id = null;
if ($pass_status === 'pass') {
    // Check if cert already exists
    $cert_check = $conn->prepare("SELECT id FROM `certificates` WHERE user_id = ? AND quiz_id = ? LIMIT 1");
    $cert_check->execute([$user_id, $quiz_id]);
    $existing = $cert_check->fetch();

    if (!$existing) {
        $cert_id   = unique_id();
        $cert_code = 'CERT-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
        $qr_hash   = hash('sha256', $cert_id . $user_id . $quiz_id . time());

        $ins_cert = $conn->prepare("INSERT INTO `certificates`(id, user_id, quiz_id, certificate_code, qr_hash) VALUES(?,?,?,?,?)");
        $ins_cert->execute([$cert_id, $user_id, $quiz_id, $cert_code, $qr_hash]);
    } else {
        $cert_id = $existing['id'];
    }
}

// Store in session to pass to results page
$_SESSION['quiz_result'] = [
    'result_id'        => $result_id,
    'quiz_id'          => $quiz_id,
    'quiz_title'       => $quiz['title'],
    'score'            => $score,
    'total'            => $total_questions,
    'score_pct'        => $score_pct,
    'pass_status'      => $pass_status,
    'passing_score'    => $quiz['passing_score'],
    'cert_id'          => $cert_id,
    'detailed_results' => $detailed_results,
];

header('location: ../quiz_result.php');
exit;
