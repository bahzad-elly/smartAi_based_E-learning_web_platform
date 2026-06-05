<?php
/**
 * Smart AI E-Learning - Student: Quiz List Page
 * Shows all available quizzes for courses the student can access
 */

include 'components/connect.php';

if (empty($user_id)) {
    header('location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Quizzes & Exams | Smart E-Learning</title>
   <meta name="description" content="Test your knowledge with our interactive quizzes and earn certificates upon passing.">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <style>
      .quiz-hero {
         background: linear-gradient(135deg, var(--main-color), #6c2d9a);
         border-radius: 1rem;
         padding: 3rem;
         color: #fff;
         text-align: center;
         margin-bottom: 3rem;
      }
      .quiz-hero h2 { font-size: 3rem; margin-bottom: 1rem; }
      .quiz-hero p  { font-size: 1.7rem; opacity: .85; }
      .quiz-stats   { display:flex; gap:2rem; justify-content:center; flex-wrap:wrap; margin-top:1.5rem; }
      .quiz-stats .stat { background:rgba(255,255,255,.15); border-radius:.8rem; padding:1rem 2rem; }
      .quiz-stats .stat span { font-size:2.5rem; font-weight:700; display:block; }
      .quiz-stats .stat small { font-size:1.3rem; opacity:.8; }

      .quiz-grid {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(32rem, 1fr));
         gap: 2rem;
      }
      .quiz-card {
         background: var(--white);
         border-radius: 1rem;
         padding: 2.5rem;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
         border-top: 4px solid var(--main-color);
         transition: transform .2s, box-shadow .2s;
         position: relative;
         overflow: hidden;
      }
      .quiz-card:hover { transform: translateY(-4px); box-shadow: 0 .8rem 2.5rem rgba(0,0,0,.13); }
      .quiz-card::before {
         content: '';
         position: absolute;
         top: -3rem; right: -3rem;
         width: 10rem; height: 10rem;
         background: rgba(142,68,173,.06);
         border-radius: 50%;
      }
      .quiz-card .course-label {
         font-size: 1.3rem;
         color: var(--main-color);
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: .05em;
         margin-bottom: .8rem;
      }
      .quiz-card h3 { font-size: 1.9rem; color: var(--black); margin-bottom:1.5rem; }
      .quiz-meta {
         display: flex;
         flex-wrap: wrap;
         gap: 1rem;
         margin-bottom: 1.5rem;
      }
      .quiz-meta .tag {
         display: flex;
         align-items: center;
         gap: .5rem;
         background: var(--light-bg);
         padding: .5rem 1rem;
         border-radius: 2rem;
         font-size: 1.4rem;
         color: var(--light-color);
      }
      .quiz-meta .tag i { color: var(--main-color); }
      .attempt-badge {
         display: inline-block;
         padding: .4rem 1.2rem;
         border-radius: 2rem;
         font-size: 1.3rem;
         font-weight: 600;
         margin-bottom: 1.5rem;
      }
      .attempt-badge.passed { background: #d5f5e3; color: #1e8449; }
      .attempt-badge.failed { background: #fde8e8; color: #c0392b; }
      .attempt-badge.new    { background: #eaf2ff; color: #2874a6; }
      .quiz-card .actions { display:flex; gap:1rem; flex-wrap:wrap; }
      .btn-quiz-start {
         flex: 1;
         background: linear-gradient(135deg, var(--main-color), #6c2d9a);
         color: #fff;
         border: none;
         border-radius: .5rem;
         padding: 1.2rem 2rem;
         font-size: 1.6rem;
         font-weight: 600;
         cursor: pointer;
         text-align: center;
         transition: opacity .2s;
         text-decoration: none;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: .7rem;
      }
      .btn-quiz-start:hover { opacity: .85; }
      .btn-hist {
         background: var(--light-bg);
         color: var(--light-color);
         border: none;
         border-radius: .5rem;
         padding: 1.2rem 1.5rem;
         font-size: 1.6rem;
         cursor: pointer;
         text-decoration: none;
         display: flex;
         align-items: center;
         gap: .5rem;
         transition: background .2s;
      }
      .btn-hist:hover { background: var(--black); color: var(--white); }
      .filter-bar {
         display: flex;
         gap: 1rem;
         flex-wrap: wrap;
         margin-bottom: 2rem;
         align-items: center;
      }
      .filter-bar span { font-size: 1.6rem; color: var(--light-color); }
      .filter-btn {
         padding: .7rem 2rem;
         border-radius: 2rem;
         border: 2px solid var(--light-bg);
         background: var(--white);
         font-size: 1.5rem;
         cursor: pointer;
         color: var(--light-color);
         transition: all .2s;
      }
      .filter-btn.active, .filter-btn:hover {
         border-color: var(--main-color);
         color: var(--main-color);
         background: rgba(142,68,173,.07);
      }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="quizzes" id="quizzes-section">

   <!-- Hero Banner -->
   <?php
      $total_quizzes  = $conn->query("SELECT COUNT(*) FROM `quizzes`")->fetchColumn();
      $my_passes      = $conn->prepare("SELECT COUNT(*) FROM `exam_results` WHERE user_id = ? AND status = 'pass'");
      $my_passes->execute([$user_id]);
      $pass_count = $my_passes->fetchColumn();

      $my_certs = $conn->prepare("SELECT COUNT(*) FROM `certificates` WHERE user_id = ?");
      $my_certs->execute([$user_id]);
      $cert_count = $my_certs->fetchColumn();
   ?>
   <div class="quiz-hero">
      <h2><i class="fas fa-brain"></i> Quizzes & Exams</h2>
      <p>Test your knowledge, earn certificates and climb the leaderboard!</p>
      <div class="quiz-stats">
         <div class="stat"><span><?= $total_quizzes ?></span><small>Available Quizzes</small></div>
         <div class="stat"><span><?= $pass_count ?></span><small>Quizzes Passed</small></div>
         <div class="stat"><span><?= $cert_count ?></span><small>Certificates Earned</small></div>
      </div>
   </div>

   <h1 class="heading">All Available Quizzes</h1>

   <!-- Filter Buttons -->
   <div class="filter-bar">
      <span>Filter:</span>
      <button class="filter-btn active" onclick="filterQuizzes('all', this)" id="filter-all">All</button>
      <button class="filter-btn" onclick="filterQuizzes('new', this)" id="filter-new">Not Attempted</button>
      <button class="filter-btn" onclick="filterQuizzes('passed', this)" id="filter-passed">Passed ✓</button>
      <button class="filter-btn" onclick="filterQuizzes('failed', this)" id="filter-failed">Failed ✗</button>
   </div>

   <div class="quiz-grid" id="quiz-grid">
   <?php
      $all_quizzes = $conn->prepare("
         SELECT q.*, p.title AS playlist_title
         FROM `quizzes` q
         LEFT JOIN `playlist` p ON q.playlist_id = p.id
         ORDER BY q.created_at DESC
      ");
      $all_quizzes->execute();

      if ($all_quizzes->rowCount() > 0):
         while ($quiz = $all_quizzes->fetch()):
            // Count questions
            $qcount = $conn->prepare("SELECT COUNT(*) FROM `questions` WHERE quiz_id = ?");
            $qcount->execute([$quiz['id']]);
            $num_q = $qcount->fetchColumn();

            // Student's best result for this quiz
            $best = $conn->prepare("SELECT * FROM `exam_results` WHERE quiz_id = ? AND user_id = ? ORDER BY score DESC LIMIT 1");
            $best->execute([$quiz['id'], $user_id]);
            $best_result = $best->fetch();

            // Total attempts
            $attempts_stmt = $conn->prepare("SELECT COUNT(*) FROM `exam_results` WHERE quiz_id = ? AND user_id = ?");
            $attempts_stmt->execute([$quiz['id'], $user_id]);
            $attempts = $attempts_stmt->fetchColumn();

            $filter_class = 'new';
            if ($best_result) {
                $filter_class = $best_result['status'];
            }
   ?>
   <div class="quiz-card" data-filter="<?= $filter_class ?>">
      <div class="course-label"><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($quiz['playlist_title'] ?? 'General') ?></div>
      <h3><?= htmlspecialchars($quiz['title']) ?></h3>

      <div class="quiz-meta">
         <span class="tag"><i class="fas fa-clock"></i>
            <?= $quiz['time_limit'] > 0 ? $quiz['time_limit'].' min' : 'No Limit' ?>
         </span>
         <span class="tag"><i class="fas fa-question-circle"></i> <?= $num_q ?> questions</span>
         <span class="tag"><i class="fas fa-star"></i> Pass: <?= $quiz['passing_score'] ?>%</span>
         <span class="tag"><i class="fas fa-redo"></i> <?= $attempts ?> attempt<?= $attempts!=1?'s':'' ?></span>
      </div>

      <?php if ($best_result): ?>
         <div class="attempt-badge <?= $best_result['status'] ?>">
            <?php if ($best_result['status'] === 'pass'): ?>
               <i class="fas fa-check-circle"></i> Passed – Best Score: <?= round($best_result['score'] / max($best_result['total_questions'],1) * 100) ?>%
            <?php else: ?>
               <i class="fas fa-times-circle"></i> Best Score: <?= round($best_result['score'] / max($best_result['total_questions'],1) * 100) ?>%
            <?php endif; ?>
         </div>
      <?php else: ?>
         <div class="attempt-badge new"><i class="fas fa-flag"></i> Not attempted yet</div>
      <?php endif; ?>

      <div class="actions">
         <?php if ($num_q > 0): ?>
         <a href="take_quiz.php?quiz_id=<?= $quiz['id'] ?>" class="btn-quiz-start">
            <i class="fas fa-play"></i>
            <?= $attempts > 0 ? 'Retake Quiz' : 'Start Quiz' ?>
         </a>
         <?php else: ?>
         <span class="btn-quiz-start" style="opacity:.5; cursor:not-allowed;"><i class="fas fa-exclamation-circle"></i> No questions yet</span>
         <?php endif; ?>
         <a href="exam_history.php?quiz_id=<?= $quiz['id'] ?>" class="btn-hist"><i class="fas fa-history"></i></a>
      </div>

      <?php if ($best_result && $best_result['status'] === 'pass'): ?>
      <?php
         $cert_check = $conn->prepare("SELECT id FROM `certificates` WHERE user_id = ? AND quiz_id = ? LIMIT 1");
         $cert_check->execute([$user_id, $quiz['id']]);
         $cert = $cert_check->fetch();
      ?>
      <?php if ($cert): ?>
      <a href="certificate.php?cert_id=<?= $cert['id'] ?>" style="display:block; margin-top:1rem; text-align:center; font-size:1.5rem; color:#1e8449; text-decoration:none;">
         <i class="fas fa-certificate"></i> Download Certificate
      </a>
      <?php endif; ?>
      <?php endif; ?>
   </div>
   <?php
         endwhile;
      else:
         echo '<p class="empty" style="grid-column:1/-1;">No quizzes available yet. Check back later!</p>';
      endif;
   ?>
   </div>

   <!-- Quick Links -->
   <div style="display:flex; gap:1.5rem; flex-wrap:wrap; margin-top:3rem;">
      <a href="exam_history.php" class="inline-btn"><i class="fas fa-history"></i> My Exam History</a>
      <a href="profile.php" class="inline-option-btn"><i class="fas fa-certificate"></i> My Certificates</a>
   </div>

</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
<script>
function filterQuizzes(type, btn) {
   document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
   btn.classList.add('active');
   document.querySelectorAll('.quiz-card').forEach(card => {
      if (type === 'all' || card.dataset.filter === type) {
         card.style.display = '';
      } else {
         card.style.display = 'none';
      }
   });
}
</script>
</body>
</html>
