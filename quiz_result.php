<?php
/**
 * Smart AI E-Learning - Quiz Results Page
 * Shows score, pass/fail, answer review, ranking
 */

include 'components/connect.php';

if (empty($user_id)) { header('location: login.php'); exit; }

// Get result from session
if (!isset($_SESSION['quiz_result'])) { header('location: quiz.php'); exit; }
$result = $_SESSION['quiz_result'];
unset($_SESSION['quiz_result']); // consume it

$quiz_id     = $result['quiz_id'];
$pass_status = $result['pass_status'];
$score       = $result['score'];
$total       = $result['total'];
$score_pct   = $result['score_pct'];
$cert_id     = $result['cert_id'];

// ── Student Ranking ──────────────────────────────
// Get all students' best scores for this quiz
$ranking_stmt = $conn->prepare("
   SELECT er.user_id, u.name, MAX(er.score / er.total_questions) AS best_ratio, MAX(er.score) AS best_score
   FROM `exam_results` er
   JOIN `users` u ON u.id = er.user_id
   WHERE er.quiz_id = ? AND er.status = 'pass'
   GROUP BY er.user_id, u.name
   ORDER BY best_ratio DESC
   LIMIT 10
");
$ranking_stmt->execute([$quiz_id]);
$ranking = $ranking_stmt->fetchAll();

$my_rank = 0;
foreach ($ranking as $i => $r) {
   if ($r['user_id'] === $user_id) { $my_rank = $i + 1; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Quiz Results | Smart E-Learning</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <style>
      .result-hero {
         border-radius: 1rem;
         padding: 4rem 3rem;
         text-align: center;
         margin-bottom: 3rem;
         position: relative;
         overflow: hidden;
      }
      .result-hero.pass {
         background: linear-gradient(135deg, #27ae60, #1e8449);
         color: #fff;
      }
      .result-hero.fail {
         background: linear-gradient(135deg, #e74c3c, #c0392b);
         color: #fff;
      }
      .result-hero .big-icon { font-size: 6rem; margin-bottom: 1.5rem; animation: bounceIn .5s ease; }
      @keyframes bounceIn { 0%{transform:scale(0)} 70%{transform:scale(1.1)} 100%{transform:scale(1)} }
      .result-hero h2 { font-size: 3.5rem; margin-bottom: 1rem; }
      .result-hero p  { font-size: 1.8rem; opacity:.85; }

      /* Score circle */
      .score-circle {
         width: 14rem; height: 14rem;
         border-radius: 50%;
         border: 6px solid rgba(255,255,255,.4);
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: center;
         margin: 2rem auto;
         background: rgba(255,255,255,.15);
      }
      .score-circle .pct { font-size: 3.5rem; font-weight: 900; }
      .score-circle .lbl { font-size: 1.3rem; opacity: .8; }

      .result-stats {
         display: flex;
         gap: 2rem;
         justify-content: center;
         flex-wrap: wrap;
         margin-top: 1.5rem;
      }
      .result-stats .stat {
         background: rgba(255,255,255,.15);
         border-radius: .8rem;
         padding: 1rem 2rem;
         text-align: center;
      }
      .result-stats .stat strong { display:block; font-size:2rem; }
      .result-stats .stat span  { font-size:1.3rem; opacity:.8; }

      /* Certificate banner */
      .cert-banner {
         background: linear-gradient(135deg, #f39c12, #e67e22);
         border-radius: 1rem;
         padding: 2rem 2.5rem;
         display: flex;
         align-items: center;
         gap: 2rem;
         margin-bottom: 2rem;
         flex-wrap: wrap;
      }
      .cert-banner i { font-size: 4rem; color: #fff; }
      .cert-banner div { flex: 1; }
      .cert-banner h3 { font-size: 2rem; color: #fff; margin-bottom: .3rem; }
      .cert-banner p  { font-size: 1.5rem; color: rgba(255,255,255,.85); }
      .cert-banner a  {
         background: #fff;
         color: #e67e22;
         padding: 1rem 2.5rem;
         border-radius: .5rem;
         font-size: 1.6rem;
         font-weight: 700;
         text-decoration: none;
         white-space: nowrap;
         transition: all .2s;
      }
      .cert-banner a:hover { background: #fef9e7; }

      /* Answer Review */
      .review-section { margin-bottom: 3rem; }
      .review-card {
         background: var(--white);
         border-radius: .8rem;
         padding: 2rem;
         margin-bottom: 1.2rem;
         border-left: 4px solid var(--light-bg);
      }
      .review-card.correct { border-color: #27ae60; }
      .review-card.wrong   { border-color: #e74c3c; }
      .review-card .q-num  { font-size:1.3rem; color:var(--light-color); text-transform:uppercase; letter-spacing:.05em; }
      .review-card .q-text { font-size:1.7rem; color:var(--black); margin:.8rem 0 1rem; }
      .review-card .answer-row { font-size:1.5rem; display:flex; align-items:center; gap:.8rem; }
      .review-card .answer-row.correct-ans { color: #1e8449; }
      .review-card .answer-row.wrong-ans   { color: #c0392b; }
      .badge-right { background:#d5f5e3; color:#1e8449; padding:.3rem 1rem; border-radius:2rem; font-size:1.3rem; font-weight:600; margin-left:auto; }
      .badge-wrong { background:#fde8e8; color:#c0392b; padding:.3rem 1rem; border-radius:2rem; font-size:1.3rem; font-weight:600; margin-left:auto; }

      /* Ranking */
      .ranking-table { width:100%; border-collapse:collapse; background:var(--white); border-radius:.8rem; overflow:hidden; }
      .ranking-table th { background:var(--main-color); color:#fff; padding:1.2rem 1.5rem; font-size:1.5rem; text-align:left; }
      .ranking-table td { padding:1.2rem 1.5rem; font-size:1.5rem; color:var(--black); border-bottom:1px solid var(--light-bg); }
      .ranking-table tr:last-child td { border-bottom:none; }
      .ranking-table .me td { background:rgba(142,68,173,.07); font-weight:700; }
      .rank-medal { font-size:1.8rem; }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="quiz-result">

   <!-- Result Hero -->
   <div class="result-hero <?= $pass_status ?>">
      <div class="big-icon">
         <?= $pass_status === 'pass' ? '🏆' : '😔' ?>
      </div>
      <h2><?= $pass_status === 'pass' ? 'Congratulations! You Passed!' : 'Keep Trying! You Can Do It!' ?></h2>
      <p><?= htmlspecialchars($result['quiz_title']) ?></p>

      <div class="score-circle">
         <span class="pct"><?= $score_pct ?>%</span>
         <span class="lbl">Score</span>
      </div>

      <div class="result-stats">
         <div class="stat"><strong><?= $score ?>/<?= $total ?></strong><span>Correct Answers</span></div>
         <div class="stat"><strong><?= $result['passing_score'] ?>%</strong><span>Passing Score</span></div>
         <?php if ($my_rank > 0): ?>
         <div class="stat"><strong>#<?= $my_rank ?></strong><span>Your Rank</span></div>
         <?php endif; ?>
      </div>
   </div>

   <!-- Certificate Banner -->
   <?php if ($pass_status === 'pass' && $cert_id): ?>
   <div class="cert-banner">
      <i class="fas fa-certificate"></i>
      <div>
         <h3>🎉 Certificate Earned!</h3>
         <p>Your certificate has been automatically generated. Download it now!</p>
      </div>
      <a href="certificate.php?cert_id=<?= $cert_id ?>"><i class="fas fa-download"></i> Download PDF</a>
   </div>
   <?php elseif ($pass_status === 'fail'): ?>
   <div style="background:var(--white); border-radius:1rem; padding:2rem; margin-bottom:2rem; text-align:center;">
      <p style="font-size:1.7rem; color:var(--light-color); margin-bottom:1.5rem;">
         <i class="fas fa-redo" style="color:var(--main-color);"></i>
         You need <strong><?= $result['passing_score'] ?>%</strong> to pass. You scored <strong><?= $score_pct ?>%</strong>. Try again!
      </p>
      <a href="take_quiz.php?quiz_id=<?= $quiz_id ?>" class="inline-btn"><i class="fas fa-redo"></i> Retake Quiz</a>
   </div>
   <?php endif; ?>

   <!-- Answer Review -->
   <h1 class="heading"><i class="fas fa-check-double" style="color:var(--main-color);"></i> Answer Review</h1>

   <div class="review-section">
   <?php foreach($result['detailed_results'] as $i => $dr): ?>
   <div class="review-card <?= $dr['is_correct'] ? 'correct' : 'wrong' ?>">
      <div class="q-num">Question <?= $i+1 ?></div>
      <div class="q-text"><?= htmlspecialchars($dr['question']) ?></div>
      <?php if (!$dr['is_correct'] && $dr['submitted_id']): ?>
      <div class="answer-row wrong-ans" style="margin-bottom:.5rem;">
         <i class="fas fa-times-circle"></i>
         Your answer was incorrect
         <span class="badge-wrong">✗ Wrong</span>
      </div>
      <?php elseif (!$dr['submitted_id']): ?>
      <div class="answer-row wrong-ans" style="margin-bottom:.5rem;">
         <i class="fas fa-minus-circle"></i>
         Not answered
         <span class="badge-wrong">— Skipped</span>
      </div>
      <?php endif; ?>
      <div class="answer-row correct-ans">
         <i class="fas fa-check-circle"></i>
         Correct answer: <strong><?= htmlspecialchars($dr['correct_text']) ?></strong>
         <?php if ($dr['is_correct']): ?>
         <span class="badge-right">✓ Correct</span>
         <?php endif; ?>
      </div>
   </div>
   <?php endforeach; ?>
   </div>

   <!-- Leaderboard -->
   <?php if (!empty($ranking)): ?>
   <h1 class="heading"><i class="fas fa-trophy" style="color:#f39c12;"></i> Leaderboard – Top Performers</h1>

   <table class="ranking-table">
      <thead>
         <tr>
            <th>Rank</th>
            <th>Student Name</th>
            <th>Best Score</th>
         </tr>
      </thead>
      <tbody>
      <?php foreach($ranking as $i => $r): ?>
      <tr <?= $r['user_id'] === $user_id ? 'class="me"' : '' ?>>
         <td>
            <span class="rank-medal">
               <?= $i===0 ? '🥇' : ($i===1 ? '🥈' : ($i===2 ? '🥉' : '#'.($i+1))) ?>
            </span>
         </td>
         <td><?= htmlspecialchars($r['name']) ?> <?= $r['user_id']===$user_id ? '<span style="background:var(--main-color);color:#fff;padding:.2rem .8rem;border-radius:2rem;font-size:1.2rem;margin-left:.5rem;">You</span>' : '' ?></td>
         <td><?= round($r['best_ratio'] * 100) ?>%</td>
      </tr>
      <?php endforeach; ?>
      </tbody>
   </table>
   <?php endif; ?>

   <!-- Navigation -->
   <div class="flex-btn" style="margin-top:3rem; flex-wrap:wrap; gap:1rem;">
      <a href="quiz.php" class="inline-btn"><i class="fas fa-list"></i> All Quizzes</a>
      <a href="exam_history.php" class="inline-option-btn"><i class="fas fa-history"></i> My History</a>
      <a href="home.php" class="inline-option-btn"><i class="fas fa-home"></i> Home</a>
   </div>

</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
<?php if ($pass_status === 'pass'): ?>
<script>
// Confetti celebration
(function() {
   const colors = ['#8e44ad','#27ae60','#f39c12','#3498db','#e74c3c'];
   const count  = 100;
   for (let i = 0; i < count; i++) {
      const el = document.createElement('div');
      el.style.cssText = `
         position:fixed;
         width:${Math.random()*10+5}px;
         height:${Math.random()*10+5}px;
         background:${colors[Math.floor(Math.random()*colors.length)]};
         top:-20px;
         left:${Math.random()*100}vw;
         border-radius:${Math.random()>0.5?'50%':'2px'};
         opacity:${Math.random()+0.3};
         animation:fall ${Math.random()*3+2}s linear ${Math.random()*2}s forwards;
         z-index:9999;
         pointer-events:none;
      `;
      document.body.appendChild(el);
   }
   const style = document.createElement('style');
   style.textContent = '@keyframes fall { to { transform:translateY(105vh) rotate(720deg); opacity:0; } }';
   document.head.appendChild(style);
   setTimeout(() => document.querySelectorAll('[style*="fall"]').forEach(e=>e.remove()), 6000);
})();
</script>
<?php endif; ?>
</body>
</html>
