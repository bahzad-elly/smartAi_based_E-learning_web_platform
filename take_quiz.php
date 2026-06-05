<?php
/**
 * Smart AI E-Learning - Student: Take Quiz Page
 * Real-time AJAX timer, dynamic question loading, anti-cheat
 */

include 'components/connect.php';

if (empty($user_id)) {
    header('location: login.php');
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? sanitize_input($_GET['quiz_id']) : '';
if (!$quiz_id) { header('location: quiz.php'); exit; }

// Fetch quiz
$quiz_stmt = $conn->prepare("SELECT q.*, p.title AS playlist_title FROM `quizzes` q LEFT JOIN `playlist` p ON q.playlist_id = p.id WHERE q.id = ? LIMIT 1");
$quiz_stmt->execute([$quiz_id]);
$quiz = $quiz_stmt->fetch();
if (!$quiz) { header('location: quiz.php'); exit; }

// Count questions
$q_count = $conn->prepare("SELECT COUNT(*) FROM `questions` WHERE quiz_id = ?");
$q_count->execute([$quiz_id]);
$total_questions = (int)$q_count->fetchColumn();
if ($total_questions === 0) { header('location: quiz.php'); exit; }

// Load questions (with optional shuffle)
$order = !empty($quiz['shuffle_questions']) ? 'RAND()' : 'q.rowid ASC';
$questions_stmt = $conn->prepare("SELECT q.*, GROUP_CONCAT(a.id, '||', a.answer_text, '||', a.is_correct ORDER BY RAND() SEPARATOR ';;') AS answers FROM `questions` q JOIN `answers` a ON a.question_id = q.id WHERE q.quiz_id = ? GROUP BY q.id ORDER BY $order");
$questions_stmt->execute([$quiz_id]);
$questions_raw = $questions_stmt->fetchAll();

// Set session start time for server-side timer validation
$session_key = 'quiz_start_' . $quiz_id . '_' . $user_id;
if (!isset($_SESSION[$session_key])) {
    $_SESSION[$session_key] = time();
}
$start_time   = $_SESSION[$session_key];
$time_limit   = (int)$quiz['time_limit'];
$seconds_left = ($time_limit > 0) ? max(0, ($time_limit * 60) - (time() - $start_time)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= htmlspecialchars($quiz['title']) ?> | Quiz</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <style>
      body { user-select: none; }
      .quiz-container {
         max-width: 850px;
         margin: 0 auto;
         padding: 2rem;
      }
      /* Header sticky bar */
      .quiz-topbar {
         background: var(--white);
         border-radius: .8rem;
         padding: 1.5rem 2rem;
         display: flex;
         justify-content: space-between;
         align-items: center;
         flex-wrap: wrap;
         gap: 1rem;
         margin-bottom: 2.5rem;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
         position: sticky;
         top: 8rem;
         z-index: 100;
      }
      .quiz-topbar .quiz-title { font-size: 1.8rem; font-weight: 700; color: var(--black); }
      .quiz-topbar .quiz-title small { display:block; font-size: 1.3rem; color: var(--light-color); font-weight:400; }

      /* Timer */
      #timer-display {
         display: flex;
         align-items: center;
         gap: .8rem;
         font-size: 2.2rem;
         font-weight: 700;
         padding: .8rem 1.8rem;
         border-radius: .5rem;
         background: var(--light-bg);
         color: var(--black);
         transition: all .3s;
         min-width: 10rem;
         justify-content: center;
      }
      #timer-display.warning { background: #fef9e7; color: #f39c12; }
      #timer-display.danger  { background: #fde8e8; color: #e74c3c; animation: pulse .5s infinite alternate; }
      @keyframes pulse { from { opacity:1; } to { opacity:.6; } }

      /* Progress bar */
      .progress-bar-wrap {
         background: var(--light-bg);
         border-radius: 2rem;
         height: .8rem;
         margin-bottom: 2rem;
         overflow: hidden;
      }
      .progress-bar-fill {
         height: 100%;
         background: linear-gradient(90deg, var(--main-color), #6c2d9a);
         border-radius: 2rem;
         transition: width .4s ease;
         width: 0%;
      }

      /* Question cards */
      .question-slide {
         background: var(--white);
         border-radius: 1rem;
         padding: 3rem;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
         display: none;
         animation: fadeIn .3s ease;
      }
      .question-slide.active { display: block; }
      @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

      .question-number { font-size: 1.3rem; color: var(--main-color); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 1rem; }
      .question-text   { font-size: 2rem; color: var(--black); line-height: 1.6; margin-bottom: 2rem; }

      /* Answer options */
      .options-list { list-style: none; display: flex; flex-direction: column; gap: 1rem; }
      .option-item  { position: relative; }
      .option-item input[type="radio"] { position: absolute; opacity: 0; }
      .option-label {
         display: flex;
         align-items: center;
         gap: 1.5rem;
         padding: 1.5rem 2rem;
         border: 2px solid var(--light-bg);
         border-radius: .8rem;
         cursor: pointer;
         font-size: 1.7rem;
         color: var(--black);
         transition: all .2s;
         background: var(--white);
      }
      .option-label .letter {
         min-width: 3.5rem;
         height: 3.5rem;
         border-radius: 50%;
         background: var(--light-bg);
         display: flex;
         align-items: center;
         justify-content: center;
         font-weight: 700;
         font-size: 1.5rem;
         color: var(--light-color);
         transition: all .2s;
         flex-shrink: 0;
      }
      .option-item input:checked + .option-label {
         border-color: var(--main-color);
         background: rgba(142,68,173,.07);
      }
      .option-item input:checked + .option-label .letter {
         background: var(--main-color);
         color: #fff;
      }
      .option-label:hover {
         border-color: var(--main-color);
         background: rgba(142,68,173,.04);
      }

      /* Navigation */
      .quiz-nav {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-top: 2.5rem;
         gap: 1rem;
         flex-wrap: wrap;
      }
      .nav-btn {
         padding: 1.2rem 2.5rem;
         border-radius: .5rem;
         font-size: 1.6rem;
         cursor: pointer;
         border: none;
         font-weight: 600;
         display: flex;
         align-items: center;
         gap: .8rem;
         transition: all .2s;
      }
      .btn-prev { background: var(--light-bg); color: var(--light-color); }
      .btn-prev:hover { background: var(--black); color: var(--white); }
      .btn-next { background: var(--main-color); color: #fff; }
      .btn-next:hover { background: #6c2d9a; }
      .btn-submit { background: #27ae60; color: #fff; }
      .btn-submit:hover { background: #1e8449; }

      /* Question Navigator */
      .q-navigator {
         background: var(--white);
         border-radius: 1rem;
         padding: 2rem;
         margin-top: 2rem;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
      }
      .q-navigator h4 { font-size:1.5rem; color:var(--light-color); margin-bottom:1rem; }
      .q-nav-dots { display:flex; flex-wrap:wrap; gap:.8rem; }
      .q-dot {
         width: 3.5rem; height: 3.5rem;
         border-radius: .5rem;
         font-size: 1.4rem;
         font-weight: 600;
         display: flex;
         align-items: center;
         justify-content: center;
         cursor: pointer;
         background: var(--light-bg);
         color: var(--light-color);
         transition: all .2s;
         border: 2px solid transparent;
      }
      .q-dot.answered { background: rgba(142,68,173,.15); color: var(--main-color); border-color: var(--main-color); }
      .q-dot.current  { background: var(--main-color); color: #fff; }

      /* Confirmation overlay */
      .confirm-overlay {
         display: none;
         position: fixed;
         inset: 0;
         background: rgba(0,0,0,.6);
         z-index: 9999;
         align-items: center;
         justify-content: center;
      }
      .confirm-overlay.show { display: flex; }
      .confirm-box {
         background: var(--white);
         border-radius: 1rem;
         padding: 3rem;
         max-width: 45rem;
         text-align: center;
         box-shadow: 0 1rem 3rem rgba(0,0,0,.3);
      }
      .confirm-box h3 { font-size:2.2rem; color:var(--black); margin-bottom:1rem; }
      .confirm-box p  { font-size:1.6rem; color:var(--light-color); margin-bottom:2rem; }
      .confirm-box .flex-btn { justify-content:center; }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<div class="quiz-container">

   <!-- Sticky Top Bar -->
   <div class="quiz-topbar">
      <div class="quiz-title">
         <i class="fas fa-brain" style="color:var(--main-color);"></i>
         <?= htmlspecialchars($quiz['title']) ?>
         <small><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($quiz['playlist_title'] ?? '') ?></small>
      </div>
      <?php if ($time_limit > 0): ?>
      <div id="timer-display">
         <i class="fas fa-clock"></i>
         <span id="timer-text">--:--</span>
      </div>
      <?php else: ?>
      <div id="timer-display" style="background:rgba(142,68,173,.1); color:var(--main-color);">
         <i class="fas fa-infinity"></i> No Time Limit
      </div>
      <?php endif; ?>
   </div>

   <!-- Progress Bar -->
   <div class="progress-bar-wrap">
      <div class="progress-bar-fill" id="progress-bar"></div>
   </div>

   <form id="quiz-form" method="post" action="api/submit_quiz.php">
      <input type="hidden" name="csrf_token" value="<?= csrf_token_generate() ?>">
      <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
      <input type="hidden" name="total_questions" value="<?= $total_questions ?>">

      <?php
      $letters = ['A','B','C','D'];
      foreach ($questions_raw as $qi => $q):
         $answers_raw = explode(';;', $q['answers']);
      ?>
      <div class="question-slide <?= $qi === 0 ? 'active' : '' ?>" data-index="<?= $qi ?>" id="question-<?= $qi ?>">
         <div class="question-number">Question <?= $qi+1 ?> of <?= $total_questions ?></div>
         <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>

         <ul class="options-list">
         <?php foreach($answers_raw as $ai => $ans_raw):
            $parts     = explode('||', $ans_raw);
            if (count($parts) < 2) continue;
            $ans_id    = $parts[0];
            $ans_text  = $parts[1];
            $letter    = isset($letters[$ai]) ? $letters[$ai] : ($ai+1);
         ?>
         <li class="option-item">
            <input type="radio" name="answer_<?= $qi ?>" id="opt_<?= $qi ?>_<?= $ai ?>"
                   value="<?= htmlspecialchars($ans_id) ?>"
                   data-question="<?= $qi ?>"
                   onchange="markAnswered(<?= $qi ?>)">
            <label for="opt_<?= $qi ?>_<?= $ai ?>" class="option-label">
               <span class="letter"><?= $letter ?></span>
               <span><?= htmlspecialchars($ans_text) ?></span>
            </label>
         </li>
         <?php endforeach; ?>
         </ul>

         <div class="quiz-nav">
            <?php if ($qi > 0): ?>
            <button type="button" class="nav-btn btn-prev" onclick="goToQuestion(<?= $qi-1 ?>)">
               <i class="fas fa-arrow-left"></i> Previous
            </button>
            <?php else: ?>
            <span></span>
            <?php endif; ?>

            <?php if ($qi < $total_questions - 1): ?>
            <button type="button" class="nav-btn btn-next" onclick="goToQuestion(<?= $qi+1 ?>)">
               Next <i class="fas fa-arrow-right"></i>
            </button>
            <?php else: ?>
            <button type="button" class="nav-btn btn-submit" onclick="document.getElementById('confirm-overlay').classList.add('show')">
               <i class="fas fa-paper-plane"></i> Submit Quiz
            </button>
            <?php endif; ?>
         </div>
      </div>
      <?php endforeach; ?>
   </form>

   <!-- Question Navigator -->
   <div class="q-navigator">
      <h4>Question Navigator (click to jump)</h4>
      <div class="q-nav-dots" id="q-nav">
         <?php for($i=0; $i<$total_questions; $i++): ?>
         <div class="q-dot <?= $i===0?'current':'' ?>" id="dot-<?= $i ?>" onclick="goToQuestion(<?= $i ?>)"><?= $i+1 ?></div>
         <?php endfor; ?>
      </div>
      <p style="font-size:1.3rem; color:var(--light-color); margin-top:1rem;">
         <span style="background:rgba(142,68,173,.15); padding:.2rem .8rem; border-radius:.3rem; color:var(--main-color);">■</span> Answered &nbsp;
         <span style="background:var(--main-color); padding:.2rem .8rem; border-radius:.3rem; color:#fff;">■</span> Current
      </p>
   </div>

</div>

<!-- Submit Confirmation Overlay -->
<div class="confirm-overlay" id="confirm-overlay">
   <div class="confirm-box">
      <i class="fas fa-paper-plane" style="font-size:4rem; color:var(--main-color); margin-bottom:1.5rem;"></i>
      <h3>Submit Quiz?</h3>
      <p id="confirm-msg">Are you sure you want to submit your answers? You cannot change them after submission.</p>
      <div class="flex-btn">
         <button type="button" class="inline-option-btn" onclick="document.getElementById('confirm-overlay').classList.remove('show')">
            <i class="fas fa-times"></i> Go Back
         </button>
         <button type="button" class="inline-btn" onclick="submitQuiz()">
            <i class="fas fa-check"></i> Submit Now
         </button>
      </div>
   </div>
</div>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
<script>
// ─── State ─────────────────────────────────────────
const TOTAL_Q      = <?= $total_questions ?>;
const TIME_LIMIT   = <?= $time_limit ?>;  // minutes, 0 = none
let   secondsLeft  = <?= $seconds_left ?>; // server-calculated
let   currentQ     = 0;
const answered     = new Array(TOTAL_Q).fill(false);

// ─── Timer ──────────────────────────────────────────
if (TIME_LIMIT > 0) {
   const timerEl   = document.getElementById('timer-display');
   const timerText = document.getElementById('timer-text');

   function updateTimer() {
      if (secondsLeft <= 0) {
         timerText.textContent = '00:00';
         timerEl.className = 'danger';
         submitQuiz(true); // Auto-submit on timeout
         return;
      }
      const m = Math.floor(secondsLeft / 60);
      const s = secondsLeft % 60;
      timerText.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
      if (secondsLeft <= 60)       timerEl.className = 'danger';
      else if (secondsLeft <= 300) timerEl.className = 'warning';
      secondsLeft--;
   }
   updateTimer();
   setInterval(updateTimer, 1000);
}

// ─── Navigation ─────────────────────────────────────
function goToQuestion(idx) {
   if (idx < 0 || idx >= TOTAL_Q) return;
   document.querySelectorAll('.question-slide').forEach(s => s.classList.remove('active'));
   document.querySelectorAll('.q-dot').forEach((d,i) => {
      d.classList.remove('current');
      d.classList.toggle('answered', answered[i]);
   });
   document.getElementById('question-' + idx).classList.add('active');
   document.getElementById('dot-' + idx).classList.add('current');
   document.getElementById('dot-' + idx).classList.remove('answered');
   currentQ = idx;
   updateProgress();
   window.scrollTo({ top: 0, behavior: 'smooth' });
}

function markAnswered(qi) {
   answered[qi] = true;
   const dot = document.getElementById('dot-' + qi);
   if (dot && qi !== currentQ) dot.classList.add('answered');
   updateProgress();
   // Count unanswered for confirm dialog
   const unanswered = answered.filter(a => !a).length;
   document.getElementById('confirm-msg').textContent = unanswered > 0
      ? `You have ${unanswered} unanswered question(s). Are you sure?`
      : 'All questions answered! Ready to submit?';
}

function updateProgress() {
   const pct = ((answered.filter(a=>a).length) / TOTAL_Q) * 100;
   document.getElementById('progress-bar').style.width = pct + '%';
}

// ─── Submit ──────────────────────────────────────────
function submitQuiz(autoSubmit = false) {
   if (!autoSubmit) {
      // Collect answers via AJAX
   }
   document.getElementById('quiz-form').submit();
}

// ─── Anti-cheat: tab visibility warning ─────────────
document.addEventListener('visibilitychange', () => {
   if (document.hidden) {
      console.warn('Tab switch detected');
   }
});

// ─── Prevent right-click & keyboard shortcuts ────────
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
   if ((e.ctrlKey || e.metaKey) && ['c','v','u','s'].includes(e.key.toLowerCase())) {
      e.preventDefault();
   }
});
</script>
</body>
</html>
