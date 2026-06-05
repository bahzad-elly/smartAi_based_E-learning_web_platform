<?php
/**
 * Smart AI E-Learning - Exam History Page
 * Shows all past quiz attempts for the logged-in student
 */

include 'components/connect.php';

if (empty($user_id)) { header('location: login.php'); exit; }

// Optional filter by quiz
$filter_quiz_id = isset($_GET['quiz_id']) ? sanitize_input($_GET['quiz_id']) : '';

// Fetch user info
$u_stmt = $conn->prepare("SELECT * FROM `users` WHERE id = ? LIMIT 1");
$u_stmt->execute([$user_id]);
$user = $u_stmt->fetch();

// Fetch history
if ($filter_quiz_id) {
    $hist_stmt = $conn->prepare("
        SELECT er.*, q.title AS quiz_title, q.passing_score, q.time_limit, p.title AS playlist_title
        FROM `exam_results` er
        JOIN `quizzes` q ON q.id = er.quiz_id
        LEFT JOIN `playlist` p ON p.id = q.playlist_id
        WHERE er.user_id = ? AND er.quiz_id = ?
        ORDER BY er.created_at DESC
    ");
    $hist_stmt->execute([$user_id, $filter_quiz_id]);
} else {
    $hist_stmt = $conn->prepare("
        SELECT er.*, q.title AS quiz_title, q.passing_score, q.time_limit, p.title AS playlist_title
        FROM `exam_results` er
        JOIN `quizzes` q ON q.id = er.quiz_id
        LEFT JOIN `playlist` p ON p.id = q.playlist_id
        WHERE er.user_id = ?
        ORDER BY er.created_at DESC
    ");
    $hist_stmt->execute([$user_id]);
}

$history = $hist_stmt->fetchAll();

// Stats
$total_attempts = count($history);
$passes = array_filter($history, fn($r) => $r['status'] === 'pass');
$fails  = array_filter($history, fn($r) => $r['status'] === 'fail');
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Exam History | Smart E-Learning</title>
   <meta name="description" content="View your complete quiz and exam attempt history.">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <style>
      .history-header {
         background: var(--white);
         border-radius: 1rem;
         padding: 2.5rem;
         display: flex;
         align-items: center;
         gap: 2rem;
         flex-wrap: wrap;
         margin-bottom: 2rem;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
      }
      .history-header img {
         width: 7rem; height: 7rem;
         border-radius: 50%;
         object-fit: cover;
         border: 3px solid var(--main-color);
      }
      .history-header .info h2 { font-size:2.2rem; color:var(--black); margin-bottom:.3rem; }
      .history-header .info span { font-size:1.5rem; color:var(--light-color); }

      .stats-row {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
         gap: 1.5rem;
         margin-bottom: 2.5rem;
      }
      .stat-card {
         background: var(--white);
         border-radius: 1rem;
         padding: 2rem;
         text-align: center;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
      }
      .stat-card .icon { font-size: 2.5rem; margin-bottom: .5rem; }
      .stat-card .num  { font-size: 3rem; font-weight: 900; color: var(--main-color); display:block; }
      .stat-card .lbl  { font-size: 1.4rem; color: var(--light-color); }

      .history-table { width:100%; border-collapse:collapse; background:var(--white); border-radius:1rem; overflow:hidden; box-shadow:0 .3rem 1.5rem rgba(0,0,0,.07); }
      .history-table th { background: var(--main-color); color:#fff; padding:1.4rem 1.5rem; font-size:1.5rem; text-align:left; }
      .history-table td { padding:1.4rem 1.5rem; font-size:1.5rem; color:var(--black); border-bottom:1px solid var(--light-bg); vertical-align:middle; }
      .history-table tr:last-child td { border-bottom:none; }
      .history-table tr:hover td { background:rgba(142,68,173,.04); }

      .score-bar-wrap { background:var(--light-bg); border-radius:2rem; height:.8rem; width:10rem; overflow:hidden; display:inline-block; vertical-align:middle; }
      .score-bar-fill { height:100%; border-radius:2rem; }

      .badge-pass { background:#d5f5e3; color:#1e8449; padding:.4rem 1rem; border-radius:2rem; font-size:1.3rem; font-weight:700; white-space:nowrap; }
      .badge-fail { background:#fde8e8; color:#c0392b; padding:.4rem 1rem; border-radius:2rem; font-size:1.3rem; font-weight:700; white-space:nowrap; }

      @media(max-width:768px) {
         .history-table { display:block; overflow-x:auto; }
      }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="exam-history">

   <h1 class="heading"><i class="fas fa-history" style="color:var(--main-color);"></i>
      <?= $filter_quiz_id ? 'Quiz Attempt History' : 'My Exam History' ?>
   </h1>

   <!-- Profile Header -->
   <div class="history-header">
      <img src="uploaded_files/<?= htmlspecialchars($user['image'] ?? 'default.png') ?>" alt="<?= htmlspecialchars($user['name']) ?>">
      <div class="info">
         <h2><?= htmlspecialchars($user['name']) ?></h2>
         <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></span>
      </div>
   </div>

   <!-- Stats -->
   <div class="stats-row">
      <div class="stat-card">
         <div class="icon">📝</div>
         <span class="num"><?= $total_attempts ?></span>
         <span class="lbl">Total Attempts</span>
      </div>
      <div class="stat-card">
         <div class="icon">✅</div>
         <span class="num" style="color:#27ae60;"><?= count($passes) ?></span>
         <span class="lbl">Passed</span>
      </div>
      <div class="stat-card">
         <div class="icon">❌</div>
         <span class="num" style="color:#e74c3c;"><?= count($fails) ?></span>
         <span class="lbl">Failed</span>
      </div>
      <div class="stat-card">
         <div class="icon">📊</div>
         <span class="num"><?= $total_attempts > 0 ? round(count($passes)/$total_attempts*100) : 0 ?>%</span>
         <span class="lbl">Pass Rate</span>
      </div>
   </div>

   <?php if (empty($history)): ?>
      <p class="empty"><i class="fas fa-inbox"></i> No exam attempts yet. <a href="quiz.php" class="inline-btn" style="margin-left:1rem;">Take a Quiz</a></p>
   <?php else: ?>
   <table class="history-table">
      <thead>
         <tr>
            <th>#</th>
            <th>Quiz</th>
            <th>Course</th>
            <th>Score</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
         </tr>
      </thead>
      <tbody>
      <?php foreach($history as $i => $h):
         $pct  = $h['total_questions'] > 0 ? round($h['score'] / $h['total_questions'] * 100) : 0;
         $color = $pct >= $h['passing_score'] ? '#27ae60' : ($pct >= $h['passing_score']*0.7 ? '#f39c12' : '#e74c3c');
      ?>
      <tr>
         <td style="color:var(--light-color);"><?= $i+1 ?></td>
         <td><strong><?= htmlspecialchars($h['quiz_title']) ?></strong></td>
         <td><?= htmlspecialchars($h['playlist_title'] ?? '-') ?></td>
         <td><strong><?= $h['score'] ?>/<?= $h['total_questions'] ?></strong> (<?= $pct ?>%)</td>
         <td>
            <div class="score-bar-wrap">
               <div class="score-bar-fill" style="width:<?= $pct ?>%; background:<?= $color ?>;"></div>
            </div>
         </td>
         <td>
            <span class="<?= $h['status'] === 'pass' ? 'badge-pass' : 'badge-fail' ?>">
               <?= $h['status'] === 'pass' ? '✓ Passed' : '✗ Failed' ?>
            </span>
         </td>
         <td style="color:var(--light-color); white-space:nowrap;"><?= date('M j, Y', strtotime($h['created_at'])) ?></td>
         <td>
            <a href="take_quiz.php?quiz_id=<?= $h['quiz_id'] ?>" class="inline-btn" style="font-size:1.3rem; padding:.6rem 1.2rem;">
               <i class="fas fa-redo"></i> Retry
            </a>
            <?php if ($h['status'] === 'pass'):
               $cert_check = $conn->prepare("SELECT id FROM `certificates` WHERE user_id = ? AND quiz_id = ? LIMIT 1");
               $cert_check->execute([$user_id, $h['quiz_id']]);
               $cert = $cert_check->fetch();
               if ($cert):
            ?>
            <a href="certificate.php?cert_id=<?= $cert['id'] ?>" class="inline-option-btn" style="font-size:1.3rem; padding:.6rem 1.2rem; margin-left:.5rem;">
               <i class="fas fa-certificate"></i>
            </a>
            <?php endif; endif; ?>
         </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
   </table>
   <?php endif; ?>

   <div class="flex-btn" style="margin-top:2rem; flex-wrap:wrap; gap:1rem;">
      <a href="quiz.php" class="inline-btn"><i class="fas fa-list"></i> All Quizzes</a>
      <?php if ($filter_quiz_id): ?>
      <a href="exam_history.php" class="inline-option-btn"><i class="fas fa-list-alt"></i> Full History</a>
      <?php endif; ?>
   </div>

</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>
