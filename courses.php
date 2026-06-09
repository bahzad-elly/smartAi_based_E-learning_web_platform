<?php

include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>All Courses | Smart AI E-Learning</title>
   <meta name="description" content="Browse all available courses on the Smart AI E-Learning platform.">
   <meta name="csrf_token" content="<?= csrf_token_generate() ?>">

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'components/user_header.php'; ?>

<!-- courses section starts  -->

<section class="courses">

   <h1 class="heading">all courses</h1>

   <div class="box-container" id="courses-box-container">

      <?php
         /* Initial render: first 6 courses only – Load More handles the rest via AJAX */
         $initial_limit = 6;
         $select_courses = $conn->prepare("SELECT p.*, t.name AS tutor_name, t.image AS tutor_image FROM `playlist` p LEFT JOIN `tutors` t ON t.id = p.tutor_id WHERE p.status = 'active' ORDER BY p.date DESC LIMIT ?");
         $select_courses->execute([$initial_limit]);

         /* Total count for showing/hiding Load More */
         $total_stmt = $conn->prepare("SELECT COUNT(*) FROM `playlist` WHERE status = 'active'");
         $total_stmt->execute();
         $total_courses = (int)$total_stmt->fetchColumn();

         if($select_courses->rowCount() > 0){
            while($fetch_course = $select_courses->fetch(PDO::FETCH_ASSOC)){
               $course_id = $fetch_course['id'];
      ?>
      <div class="box">
         <div class="tutor">
            <img src="uploaded_files/<?= htmlspecialchars($fetch_course['tutor_image'] ?? ''); ?>" alt="" onerror="this.src='images/default.png'">
            <div>
               <h3><?= htmlspecialchars($fetch_course['tutor_name'] ?? ''); ?></h3>
               <span><?= $fetch_course['date']; ?></span>
            </div>
         </div>
         <img src="uploaded_files/<?= htmlspecialchars($fetch_course['thumb']); ?>" class="thumb" alt="<?= htmlspecialchars($fetch_course['title']); ?>" onerror="this.src='images/default.png'">
         <h3 class="title"><?= htmlspecialchars($fetch_course['title']); ?></h3>
         <a href="playlist.php?get_id=<?= $course_id; ?>" class="inline-btn"><i class="fas fa-play"></i> View Playlist</a>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">No courses added yet!</p>';
      }
      ?>

   </div>

   <!-- Load More Button (Part 9 – AJAX) -->
   <?php if($total_courses > $initial_limit): ?>
   <div class="load-more-wrapper">
      <button id="load-more-btn" data-offset="<?= $initial_limit; ?>" data-limit="6">
         <i class="fas fa-plus"></i> Load More Courses
      </button>
   </div>
   <?php endif; ?>

</section>

<!-- courses section ends -->










<?php include 'components/footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>
<script src="js/ajax.js"></script>
   
</body>
</html>