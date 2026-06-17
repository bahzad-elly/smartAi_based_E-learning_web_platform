<?php
/**
 * Smart AI E-Learning - Admin: Add / Edit Quiz
 */

include '../components/connect.php';

if (empty($tutor_id)) {
    header('location: login.php');
    exit;
}

// Check if editing
$edit_id = isset($_GET['edit']) ? sanitize_input($_GET['edit']) : null;
$edit_quiz = null;
if ($edit_id) {
    $s = $conn->prepare("SELECT * FROM `quizzes` WHERE id = ? LIMIT 1");
    $s->execute([$edit_id]);
    $edit_quiz = $s->fetch();
}

if (isset($_POST['save_quiz'])) {
    $title         = sanitize_input($_POST['title']);
    $playlist_id   = sanitize_input($_POST['playlist_id']);
    $time_limit    = (int)$_POST['time_limit'];
    $passing_score = (int)$_POST['passing_score'];
    $shuffle       = isset($_POST['shuffle_questions']) ? 1 : 0;

    if ($edit_id) {
        $stmt = $conn->prepare("UPDATE `quizzes` SET title=?, playlist_id=?, time_limit=?, passing_score=?, shuffle_questions=? WHERE id=?");
        $stmt->execute([$title, $playlist_id, $time_limit, $passing_score, $shuffle, $edit_id]);
        $message[] = 'Quiz updated successfully!';
    } else {
        $new_id = unique_id();
        $stmt = $conn->prepare("INSERT INTO `quizzes`(id, playlist_id, title, time_limit, passing_score, shuffle_questions) VALUES(?,?,?,?,?,?)");
        $stmt->execute([$new_id, $playlist_id, $title, $time_limit, $passing_score, $shuffle]);

        // Create a global notification for students about the new quiz
        try {
            $notif_id = unique_id();
            $notif_title = 'New Quiz Available';
            $notif_message = 'A new quiz "' . $title . '" has been published. Good luck!';
            $ins_notif = $conn->prepare("INSERT INTO `notifications` (id, user_id, tutor_id, title, message, status) VALUES (?, NULL, NULL, ?, ?, 'unread')");
            $ins_notif->execute([$notif_id, $notif_title, $notif_message]);
        } catch (Exception $ex) {
            // Ignore notification failures
        }

        $edit_id = $new_id;
        $message[] = 'Quiz created! Now add questions below.';
        header("location: manage_questions.php?quiz_id=$new_id");
        exit;
    }
}

// Get all playlists for dropdown
$playlists = $conn->prepare("SELECT * FROM `playlists` WHERE status='active' ORDER BY title ASC");
$playlists->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= $edit_quiz ? 'Edit Quiz' : 'Add New Quiz' ?> | Admin</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="form-container">

   <h1 class="heading"><?= $edit_quiz ? 'Edit Quiz' : 'Add New Quiz' ?></h1>

   <?php if(isset($message)) foreach($message as $msg): ?>
   <div class="message form"><span><?= htmlspecialchars($msg) ?></span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>
   <?php endforeach; ?>

   <form action="" method="post" class="form" style="background:var(--white); padding:2.5rem; border-radius:.5rem;">
      <?php csrf_input_render(); ?>

      <div class="input-field">
         <label for="title">Quiz Title</label>
         <input type="text" id="title" name="title" class="box" placeholder="e.g. JavaScript Fundamentals Quiz"
                value="<?= htmlspecialchars($edit_quiz['title'] ?? '') ?>" required maxlength="100">
      </div>

      <div class="input-field">
         <label for="playlist_id">Select Course (Playlist)</label>
         <select name="playlist_id" id="playlist_id" class="box" required style="font-size:1.6rem; color:var(--black); padding:1rem; width:100%;">
            <option value="">-- Select Course --</option>
            <?php while ($pl = $playlists->fetch()): ?>
            <option value="<?= $pl['id'] ?>" <?= ($edit_quiz && $edit_quiz['playlist_id'] == $pl['id']) ? 'selected' : '' ?>>
               <?= htmlspecialchars($pl['title']) ?>
            </option>
            <?php endwhile; ?>
         </select>
      </div>

      <div class="flex" style="gap:1.5rem; flex-wrap:wrap;">
         <div class="input-field" style="flex:1 1 18rem;">
            <label for="time_limit">Time Limit (minutes) <small style="color:var(--light-color);">0 = no limit</small></label>
            <input type="number" id="time_limit" name="time_limit" class="box"
                   min="0" max="300" placeholder="e.g. 30"
                   value="<?= $edit_quiz['time_limit'] ?? 30 ?>" required>
         </div>
         <div class="input-field" style="flex:1 1 18rem;">
            <label for="passing_score">Passing Score (%)</label>
            <input type="number" id="passing_score" name="passing_score" class="box"
                   min="1" max="100" placeholder="e.g. 60"
                   value="<?= $edit_quiz['passing_score'] ?? 60 ?>" required>
         </div>
      </div>

      <div class="input-field" style="display:flex; align-items:center; gap:1rem; margin-top:1rem;">
         <input type="checkbox" name="shuffle_questions" id="shuffle_questions" style="width:2rem; height:2rem; cursor:pointer;"
                <?= (!empty($edit_quiz['shuffle_questions'])) ? 'checked' : '' ?>>
         <label for="shuffle_questions" style="font-size:1.7rem; cursor:pointer;">Shuffle questions randomly each attempt</label>
      </div>

      <div class="flex-btn" style="margin-top:2rem;">
         <input type="submit" name="save_quiz" value="<?= $edit_quiz ? 'Update Quiz' : 'Save & Add Questions' ?>" class="btn">
         <a href="quizzes.php" class="option-btn">Cancel</a>
      </div>
   </form>

</section>

<?php include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
</body>
</html>
