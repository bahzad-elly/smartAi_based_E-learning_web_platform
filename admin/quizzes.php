<?php
/**
 * Smart AI E-Learning - Admin: Quiz Management
 */

include '../components/connect.php';

// Admin must be logged in
if (!isset($_SESSION['admin_id'])) {
    header('location: login.php');
    exit;
}
$admin_id = $_SESSION['admin_id'];

// Delete a quiz
if (isset($_POST['delete_quiz'])) {
    $del_id = sanitize_input($_POST['quiz_id']);
    $stmt = $conn->prepare("DELETE FROM `quizzes` WHERE id = ?");
    $stmt->execute([$del_id]);
    $message[] = 'Quiz deleted successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Manage Quizzes | Admin</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="show-contents">

   <h1 class="heading">Quiz Management</h1>

   <?php if(isset($message)) foreach($message as $msg): ?>
   <div class="message"><span><?= htmlspecialchars($msg) ?></span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>
   <?php endforeach; ?>

   <div class="flex-btn" style="margin-bottom:2rem;">
      <a href="add_quiz.php" class="inline-btn"><i class="fas fa-plus"></i> Add New Quiz</a>
   </div>

   <div class="box-container">
   <?php
      $select_quizzes = $conn->prepare("
         SELECT q.*, p.title AS playlist_title
         FROM `quizzes` q
         LEFT JOIN `playlist` p ON q.playlist_id = p.id
         ORDER BY q.created_at DESC
      ");
      $select_quizzes->execute();
      if ($select_quizzes->rowCount() > 0):
         while ($quiz = $select_quizzes->fetch()):
            // Count questions
            $count_q = $conn->prepare("SELECT COUNT(*) FROM `questions` WHERE quiz_id = ?");
            $count_q->execute([$quiz['id']]);
            $total_questions = $count_q->fetchColumn();
            // Count attempts
            $count_a = $conn->prepare("SELECT COUNT(*) FROM `exam_results` WHERE quiz_id = ?");
            $count_a->execute([$quiz['id']]);
            $total_attempts = $count_a->fetchColumn();
   ?>
   <div class="box">
      <div class="tutor">
         <div>
            <h3 class="title"><?= htmlspecialchars($quiz['title']) ?></h3>
            <span><?= htmlspecialchars($quiz['playlist_title'] ?? 'No Playlist') ?></span>
         </div>
      </div>
      <p><i class="fas fa-clock" style="color:var(--main-color);margin-right:.5rem;"></i> Time: <strong><?= $quiz['time_limit'] ?> min</strong></p>
      <p><i class="fas fa-star" style="color:var(--oragen);margin-right:.5rem;"></i> Passing Score: <strong><?= $quiz['passing_score'] ?>%</strong></p>
      <p><i class="fas fa-question-circle" style="color:var(--main-color);margin-right:.5rem;"></i> Questions: <strong><?= $total_questions ?></strong></p>
      <p><i class="fas fa-users" style="color:var(--main-color);margin-right:.5rem;"></i> Attempts: <strong><?= $total_attempts ?></strong></p>
      <div class="flex-btn" style="margin-top:1.5rem; flex-wrap:wrap; gap:.8rem;">
         <a href="manage_questions.php?quiz_id=<?= $quiz['id'] ?>" class="inline-option-btn"><i class="fas fa-list"></i> Questions</a>
         <a href="add_quiz.php?edit=<?= $quiz['id'] ?>" class="inline-btn"><i class="fas fa-edit"></i> Edit</a>
         <form method="post" style="display:inline;" onsubmit="return confirm('Delete this quiz and all its questions?');">
            <?php csrf_input_render(); ?>
            <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
            <button type="submit" name="delete_quiz" class="inline-delete-btn"><i class="fas fa-trash"></i> Delete</button>
         </form>
      </div>
   </div>
   <?php
         endwhile;
      else:
         echo '<p class="empty">No quizzes created yet. <a href="add_quiz.php" class="inline-btn" style="margin-left:1rem;">Add First Quiz</a></p>';
      endif;
   ?>
   </div>

</section>

<?php include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
</body>
</html>
