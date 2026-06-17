<?php

include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

$select_likes = $conn->prepare("SELECT * FROM `likes` WHERE user_id = ?");
$select_likes->execute([$user_id]);
$total_likes = $select_likes->rowCount();

$select_comments = $conn->prepare("SELECT * FROM `comments` WHERE user_id = ?");
$select_comments->execute([$user_id]);
$total_comments = $select_comments->rowCount();

$select_bookmark = $conn->prepare("SELECT * FROM `bookmarks` WHERE user_id = ?");
$select_bookmark->execute([$user_id]);
$total_bookmarked = $select_bookmark->rowCount();

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Home | Smart AI E-Learning Platform</title>
   <meta name="description" content="Learn from expert tutors with AI-powered personalized learning. Browse hundreds of courses in development, design, business and more.">
   <meta name="keywords" content="e-learning, online courses, AI learning, programming, design, business">
   <meta name="robots" content="index, follow">
   <!-- Open Graph -->
   <meta property="og:title" content="Smart AI E-Learning Platform">
   <meta property="og:description" content="Learn from expert tutors with AI-powered personalized learning.">
   <meta property="og:type" content="website">
   <meta name="csrf_token" content="<?= csrf_token_generate() ?>">
   <!-- Preconnect for performance (Part 20) -->
   <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
   <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<!-- Page Loader (Part 16 / Part 20) -->
<div id="page-loader" aria-hidden="true">
   <div class="loader-ring"></div>
   <span class="loader-text">Loading...</span>
</div>

<?php include 'components/user_header.php'; ?>

<!-- quick select section starts  -->

<section class="quick-select">

   <h1 class="heading">quick options</h1>

   <div class="box-container">

      <?php
         if($user_id != ''){
      ?>
      <div class="box">
         <h3 class="title">likes and comments</h3>
         <p>total likes : <span><?= $total_likes; ?></span></p>
         <a href="likes.php" class="inline-btn">view likes</a>
         <p>total comments : <span><?= $total_comments; ?></span></p>
         <a href="comments.php" class="inline-btn">view comments</a>
         <p>saved playlist : <span><?= $total_bookmarked; ?></span></p>
         <a href="bookmark.php" class="inline-btn">view bookmark</a>
      </div>
      <?php
         }else{ 
      ?>
      <div class="box" style="text-align: center;">
         <h3 class="title">please login or register</h3>
          <div class="flex-btn" style="padding-top: .5rem;">
            <a href="login.php" class="option-btn">login</a>
            <a href="register.php" class="option-btn">register</a>
         </div>
      </div>
      <?php
      }
      ?>

      <div class="box">
         <h3 class="title">top categories</h3>
         <div class="flex">
            <a href="search_course.php?"><i class="fas fa-code"></i><span>development</span></a>
            <a href="#"><i class="fas fa-chart-simple"></i><span>business</span></a>
            <a href="#"><i class="fas fa-pen"></i><span>design</span></a>
            <a href="#"><i class="fas fa-chart-line"></i><span>marketing</span></a>
            <a href="#"><i class="fas fa-music"></i><span>music</span></a>
            <a href="#"><i class="fas fa-camera"></i><span>photography</span></a>
            <a href="#"><i class="fas fa-cog"></i><span>software</span></a>
            <a href="#"><i class="fas fa-vial"></i><span>science</span></a>
         </div>
      </div>

      <div class="box">
         <h3 class="title">popular topics</h3>
         <div class="flex">
            <a href="#"><i class="fab fa-html5"></i><span>HTML</span></a>
            <a href="#"><i class="fab fa-css3"></i><span>CSS</span></a>
            <a href="#"><i class="fab fa-js"></i><span>javascript</span></a>
            <a href="#"><i class="fab fa-react"></i><span>react</span></a>
            <a href="#"><i class="fab fa-php"></i><span>PHP</span></a>
            <a href="#"><i class="fab fa-bootstrap"></i><span>bootstrap</span></a>
         </div>
      </div>

      <div class="box tutor">
         <h3 class="title">become a tutor</h3>
         <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Ipsa, laudantium.</p>
         <a href="admin/register.php" class="inline-btn">get started</a>
      </div>

   </div>

</section>

<!-- quick select section ends -->

<!-- courses section starts  -->

<section class="courses">

   <h1 class="heading">latest courses</h1>

   <div class="box-container">

      <?php
         $select_courses = $conn->prepare("SELECT * FROM `playlists` WHERE status = ? ORDER BY date DESC LIMIT 6");
         $select_courses->execute(['active']);
         if($select_courses->rowCount() > 0){
            while($fetch_course = $select_courses->fetch(PDO::FETCH_ASSOC)){
               $course_id = $fetch_course['id'];

               $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE id = ?");
               $select_tutor->execute([$fetch_course['tutor_id']]);
               $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);
      ?>
      <div class="box reveal-card">
         <div class="tutor">
            <img src="uploaded_files/<?= htmlspecialchars($fetch_tutor['image'] ?? ''); ?>" alt="<?= htmlspecialchars($fetch_tutor['name'] ?? ''); ?>" loading="lazy" onerror="this.src='images/default.png'">
            <div>
               <h3><?= htmlspecialchars($fetch_tutor['name'] ?? ''); ?></h3>
               <span><?= $fetch_course['date'] ?? ''; ?></span>
            </div>
         </div>
         <img src="uploaded_files/<?= htmlspecialchars($fetch_course['thumb']); ?>" class="thumb" alt="<?= htmlspecialchars($fetch_course['title']); ?>" loading="lazy" onerror="this.src='images/default.png'">
         <h3 class="title"><?= htmlspecialchars($fetch_course['title']); ?></h3>
         <a href="playlist.php?get_id=<?= $course_id; ?>" class="inline-btn">view playlist</a>
      </div>
      <?php
         }
      }else{
         echo '<p class="empty">no courses added yet!</p>';
      }
      ?>

   </div>

   <div class="more-btn">
      <a href="courses.php" class="inline-option-btn">view more</a>
   </div>

</section>

<!-- courses section ends -->












<!-- ─── Part 13: Recommended for You ─────────────────────────────── -->
<section class="courses recommended-section" id="recommended-section">
   <h1 class="heading" id="recommended-heading">
      <i class="fas fa-star" style="color:var(--main-color);"></i>
      recommended for you
   </h1>
   <div class="box-container" id="recommended-box-container">
      <!-- skeleton placeholders while loading -->
      <?php for($i=0; $i<6; $i++): ?>
      <div class="box skeleton-card">
         <div class="skeleton skeleton-avatar"></div>
         <div class="skeleton skeleton-thumb"></div>
         <div class="skeleton skeleton-line"></div>
         <div class="skeleton skeleton-btn"></div>
      </div>
      <?php endfor; ?>
   </div>
</section>
<!-- ─── end recommended section ─────────────────────────────────── -->

<!-- footer section starts  -->
<?php include 'components/footer.php'; ?>
<!-- footer section ends -->

<!-- custom js file link  -->
<script src="js/script.js" defer></script>
<script src="js/ajax.js" defer></script>
<script>
/* Part 16/20 — page loader & scroll reveal */
window.addEventListener('load', function () {
   const loader = document.getElementById('page-loader');
   if (loader) loader.classList.add('hidden');
});

function revealOnScroll() {
   document.querySelectorAll('.reveal-card:not(.visible)').forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight - 60) el.classList.add('visible');
   });
}
window.addEventListener('scroll', revealOnScroll, { passive: true });
revealOnScroll();
</script>
<script>
/* Part 13 — load personalised recommendations via AJAX */
(function () {
   const container  = document.getElementById('recommended-box-container');
   const headingEl  = document.getElementById('recommended-heading');
   if (!container) return;

   fetch('api/recommendations.php?action=for_you')
      .then(r => r.json())
      .then(data => {
         container.innerHTML = '';
         if (data.status !== 'success' || data.courses.length === 0) {
            document.getElementById('recommended-section').style.display = 'none';
            return;
         }

         // Update heading label
         if (data.label === 'personalized') {
            headingEl.innerHTML = '<i class="fas fa-magic" style="color:var(--main-color);"></i> recommended for you';
         } else {
            headingEl.innerHTML = '<i class="fas fa-fire" style="color:#e74c3c;"></i> trending courses';
         }

         data.courses.forEach(course => {
            const thumb = course.thumb
               ? `uploaded_files/${escHtml(course.thumb)}`
               : 'images/default.png';
            const avatar = course.tutor_image
               ? `uploaded_files/${escHtml(course.tutor_image)}`
               : 'images/default.png';
            const enroll = course.enrollment_count > 0
               ? `<span class="enroll-badge"><i class="fas fa-users"></i> ${course.enrollment_count}</span>`
               : '';

            container.insertAdjacentHTML('beforeend', `
               <div class="box reveal-card">
                  <div class="tutor">
                     <img src="${avatar}" alt="${escHtml(course.tutor_name)}" loading="lazy" onerror="this.src='images/default.png'">
                     <div>
                        <h3>${escHtml(course.tutor_name)}</h3>
                        ${enroll}
                     </div>
                  </div>
                  <img src="${thumb}" class="thumb" alt="${escHtml(course.title)}" loading="lazy" onerror="this.src='images/default.png'">
                  <h3 class="title">${escHtml(course.title)}</h3>
                  <a href="playlist.php?get_id=${course.id}" class="inline-btn">
                     <i class="fas fa-play"></i> view playlist
                  </a>
               </div>
            `);
         });

         // Trigger scroll-reveal animation
         if (typeof revealOnScroll === 'function') revealOnScroll();
      })
      .catch(() => {
         document.getElementById('recommended-section').style.display = 'none';
      });

   function escHtml(str) {
      return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
   }
})();
</script>
   
</body>
</html>