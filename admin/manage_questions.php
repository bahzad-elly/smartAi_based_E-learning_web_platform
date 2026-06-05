<?php
/**
 * Smart AI E-Learning - Admin: Manage Quiz Questions & Answers
 */

include '../components/connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('location: login.php');
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? sanitize_input($_GET['quiz_id']) : '';
if (!$quiz_id) { header('location: quizzes.php'); exit; }

// Fetch quiz info
$q_stmt = $conn->prepare("SELECT q.*, p.title AS playlist_title FROM `quizzes` q LEFT JOIN `playlist` p ON q.playlist_id = p.id WHERE q.id = ? LIMIT 1");
$q_stmt->execute([$quiz_id]);
$quiz = $q_stmt->fetch();
if (!$quiz) { header('location: quizzes.php'); exit; }

// ── ADD QUESTION ──────────────────────────────────
if (isset($_POST['add_question'])) {
    $question_text = sanitize_input($_POST['question_text']);
    $type          = sanitize_input($_POST['question_type']);  // multiple_choice | true_false
    $q_id          = unique_id();

    $ins = $conn->prepare("INSERT INTO `questions`(id, quiz_id, question_text, type) VALUES(?,?,?,?)");
    $ins->execute([$q_id, $quiz_id, $question_text, $type]);

    if ($type === 'true_false') {
        // Two answers: True & False
        foreach (['True', 'False'] as $ans_text) {
            $a_id      = unique_id();
            $is_correct = ($ans_text === $_POST['tf_correct']) ? 1 : 0;
            $conn->prepare("INSERT INTO `answers`(id, question_id, answer_text, is_correct) VALUES(?,?,?,?)")
                 ->execute([$a_id, $q_id, $ans_text, $is_correct]);
        }
    } else {
        // MCQ – up to 4 options
        $options   = $_POST['options']   ?? [];
        $correct_i = (int)($_POST['correct_option'] ?? 0);
        foreach ($options as $idx => $opt_text) {
            if (trim($opt_text) === '') continue;
            $a_id      = unique_id();
            $is_correct = ($idx == $correct_i) ? 1 : 0;
            $conn->prepare("INSERT INTO `answers`(id, question_id, answer_text, is_correct) VALUES(?,?,?,?)")
                 ->execute([$a_id, $q_id, sanitize_input($opt_text), $is_correct]);
        }
    }
    $message[] = 'Question added successfully!';
}

// ── DELETE QUESTION ───────────────────────────────
if (isset($_POST['delete_question'])) {
    $del_qid = sanitize_input($_POST['question_id']);
    $conn->prepare("DELETE FROM `questions` WHERE id = ? AND quiz_id = ?")->execute([$del_qid, $quiz_id]);
    $message[] = 'Question deleted.';
}

// ── FETCH ALL QUESTIONS ───────────────────────────
$questions = $conn->prepare("SELECT * FROM `questions` WHERE quiz_id = ? ORDER BY rowid ASC");
$questions->execute([$quiz_id]);
$all_questions = $questions->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Manage Questions | Admin</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
   <style>
      .question-box { background:var(--white); padding:2rem; border-radius:.5rem; margin-bottom:1.5rem; border-left:4px solid var(--main-color); }
      .question-box h4 { font-size:1.8rem; color:var(--black); margin-bottom:1rem; }
      .answer-list { list-style:none; display:flex; flex-wrap:wrap; gap:.8rem; }
      .answer-list li { padding:.6rem 1.2rem; border-radius:.5rem; font-size:1.5rem; background:var(--light-bg); color:var(--light-color); }
      .answer-list li.correct { background:#27ae60; color:#fff; font-weight:600; }
      .q-type-badge { display:inline-block; padding:.3rem 1rem; border-radius:2rem; font-size:1.3rem; background:var(--main-color); color:#fff; margin-bottom:.8rem; }
      .add-form { background:var(--white); padding:2.5rem; border-radius:.5rem; }
      .options-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
      @media(max-width:600px){ .options-grid{grid-template-columns:1fr;} }
      .radio-label { display:flex; align-items:center; gap:.8rem; font-size:1.6rem; cursor:pointer; }
   </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="show-contents">

   <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:2rem;">
      <div>
         <h1 class="heading" style="border:none; margin-bottom:.3rem;">Questions: <?= htmlspecialchars($quiz['title']) ?></h1>
         <p style="font-size:1.6rem; color:var(--light-color);">
            Course: <?= htmlspecialchars($quiz['playlist_title'] ?? '-') ?> &nbsp;|&nbsp;
            Time: <?= $quiz['time_limit'] ?> min &nbsp;|&nbsp;
            Pass: <?= $quiz['passing_score'] ?>% &nbsp;|&nbsp;
            Questions: <strong><?= count($all_questions) ?></strong>
         </p>
      </div>
      <a href="quizzes.php" class="inline-option-btn"><i class="fas fa-arrow-left"></i> Back to Quizzes</a>
   </div>

   <?php if(isset($message)) foreach($message as $msg): ?>
   <div class="message"><span><?= htmlspecialchars($msg) ?></span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>
   <?php endforeach; ?>

   <!-- ── ADD QUESTION FORM ── -->
   <div class="add-form" style="margin-bottom:3rem;">
      <h2 style="font-size:2rem; color:var(--black); margin-bottom:1.5rem;"><i class="fas fa-plus-circle" style="color:var(--main-color);"></i> Add New Question</h2>
      <form method="post" id="add-question-form">
         <?php csrf_input_render(); ?>

         <div class="input-field">
            <label>Question Text</label>
            <textarea name="question_text" class="box" rows="3" placeholder="Enter your question here..." required maxlength="1000" style="resize:vertical;"></textarea>
         </div>

         <div class="input-field">
            <label>Question Type</label>
            <div style="display:flex; gap:2rem;">
               <label class="radio-label"><input type="radio" name="question_type" value="multiple_choice" checked onchange="toggleType(this.value)"> Multiple Choice</label>
               <label class="radio-label"><input type="radio" name="question_type" value="true_false" onchange="toggleType(this.value)"> True / False</label>
            </div>
         </div>

         <!-- MCQ Options -->
         <div id="mcq-options">
            <label style="font-size:1.6rem; color:var(--black); display:block; margin-bottom:1rem;">Answer Options (mark the correct one)</label>
            <div class="options-grid">
               <?php for($i=0;$i<4;$i++): ?>
               <div style="display:flex; align-items:center; gap:1rem;">
                  <input type="radio" name="correct_option" value="<?= $i ?>" <?= $i==0?'checked':'' ?> style="width:2rem;height:2rem;cursor:pointer;">
                  <input type="text" name="options[]" class="box" placeholder="Option <?= chr(65+$i) ?>" style="flex:1;" maxlength="300"
                         <?= $i<2?'required':'' ?>>
               </div>
               <?php endfor; ?>
            </div>
            <p style="font-size:1.4rem; color:var(--light-color); margin-top:.5rem;">Select the radio button next to the correct answer.</p>
         </div>

         <!-- True/False Options -->
         <div id="tf-options" style="display:none;">
            <label style="font-size:1.6rem; color:var(--black); display:block; margin-bottom:1rem;">Correct Answer</label>
            <div style="display:flex; gap:2rem;">
               <label class="radio-label"><input type="radio" name="tf_correct" value="True" checked> ✓ True</label>
               <label class="radio-label"><input type="radio" name="tf_correct" value="False"> ✗ False</label>
            </div>
         </div>

         <input type="submit" name="add_question" value="Add Question" class="btn" style="margin-top:1.5rem;">
      </form>
   </div>

   <!-- ── EXISTING QUESTIONS ── -->
   <h2 style="font-size:2rem; color:var(--black); margin-bottom:1.5rem;"><i class="fas fa-list" style="color:var(--main-color);"></i> Existing Questions (<?= count($all_questions) ?>)</h2>

   <?php if (empty($all_questions)): ?>
      <p class="empty">No questions yet. Add your first question above!</p>
   <?php else: ?>
      <?php foreach($all_questions as $i => $q): ?>
      <?php
         $answers = $conn->prepare("SELECT * FROM `answers` WHERE question_id = ? ORDER BY id ASC");
         $answers->execute([$q['id']]);
         $all_answers = $answers->fetchAll();
      ?>
      <div class="question-box">
         <span class="q-type-badge"><?= $q['type'] === 'true_false' ? '<i class="fas fa-toggle-on"></i> True/False' : '<i class="fas fa-list-ul"></i> Multiple Choice' ?></span>
         <h4>Q<?= $i+1 ?>. <?= htmlspecialchars($q['question_text']) ?></h4>
         <ul class="answer-list">
            <?php foreach($all_answers as $ans): ?>
            <li class="<?= $ans['is_correct'] ? 'correct' : '' ?>">
               <?= $ans['is_correct'] ? '✓ ' : '' ?><?= htmlspecialchars($ans['answer_text']) ?>
            </li>
            <?php endforeach; ?>
         </ul>
         <form method="post" style="margin-top:1rem;" onsubmit="return confirm('Delete this question?');">
            <?php csrf_input_render(); ?>
            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
            <button type="submit" name="delete_question" class="inline-delete-btn" style="font-size:1.4rem; padding:.7rem 1.5rem;">
               <i class="fas fa-trash"></i> Delete Question
            </button>
         </form>
      </div>
      <?php endforeach; ?>
   <?php endif; ?>

</section>

<?php include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
<script>
function toggleType(type) {
   document.getElementById('mcq-options').style.display  = (type === 'multiple_choice') ? 'block' : 'none';
   document.getElementById('tf-options').style.display   = (type === 'true_false')      ? 'block' : 'none';
   // Toggle required on MCQ inputs
   const opts = document.querySelectorAll('#mcq-options input[type="text"]');
   opts.forEach((el, i) => { el.required = (type === 'multiple_choice' && i < 2); });
}
</script>
</body>
</html>
