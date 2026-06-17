<?php
if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<meta name="csrf_token" content="<?= csrf_token_generate() ?>">

<header class="header">

   <section class="flex">

      <a href="home.php" class="logo">Educa.</a>

      <!-- Live Search Wrapper (Part 9 – AJAX) -->
      <div class="live-search-wrapper search-form" style="padding:0; background:none;">
         <form action="search_course.php" method="post" class="search-form" style="width:100%; position:relative;">
            <input type="text" id="live-search-input" name="search_course"
                   placeholder="Search courses & lessons..." autocomplete="off" maxlength="100"
                   style="width:100%; background:var(--light-bg); border-radius:.5rem; padding:1.5rem 5rem 1.5rem 2rem; font-size:2rem; color:var(--black);">
            <button type="submit" class="fas fa-search" name="search_course_btn"
                    style="position:absolute; right:1.5rem; top:50%; transform:translateY(-50%); font-size:2rem; color:var(--black); background:none; cursor:pointer;"></button>
         </form>
         <div id="live-search-dropdown"></div>
      </div>

      <!-- Part 17 — Language Switcher -->
      <div class="lang-switcher" aria-label="Language selector">
         <?php
            $cur_lang = $_COOKIE['lang'] ?? $_SESSION['lang'] ?? 'en';
            $cur_page = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '');
         ?>
         <a href="?lang=en" class="<?= $cur_lang === 'en' ? 'active' : ''; ?>" aria-label="English" title="English">EN</a>
         <a href="?lang=ku" class="<?= $cur_lang === 'ku' ? 'active' : ''; ?>" aria-label="Kurdish" title="Kurdish">KU</a>
         <a href="?lang=ar" class="<?= $cur_lang === 'ar' ? 'active' : ''; ?>" aria-label="Arabic"  title="Arabic">AR</a>
      </div>

      <div class="icons">
         <div id="menu-btn" class="fas fa-bars" role="button" aria-label="Toggle sidebar menu" tabindex="0"></div>
         <div id="search-btn" class="fas fa-search" role="button" aria-label="Toggle search" tabindex="0"></div>
         <div id="user-btn" class="fas fa-user" role="button" aria-label="User profile" tabindex="0"></div>
         <div id="notif-bell-btn" class="fas fa-bell" role="button" aria-label="Notifications" tabindex="0">
            <span id="notif-badge" aria-live="polite" aria-label="Unread notifications count">0</span>
         </div>
         <div id="toggle-btn" class="fas fa-sun" role="button" aria-label="Toggle dark mode" tabindex="0"></div>
      </div>

      <!-- Notification Dropdown Container -->
      <div class="notif-dropdown-wrapper" id="notif-dropdown">
         <div class="notif-dropdown-header">
            <h3>Notifications</h3>
            <button class="notif-mark-all-btn" id="notif-mark-all">Mark all as read</button>
         </div>
         <div class="notif-list" id="notif-list-container">
            <!-- Dynamic alerts map here -->
         </div>
      </div>

      <div class="profile">
         <?php
            $select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
            $select_profile->execute([$user_id]);
            if($select_profile->rowCount() > 0){
            $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
         ?>
         <img src="uploaded_files/<?= $fetch_profile['image']; ?>" alt="">
         <h3><?= $fetch_profile['name']; ?></h3>
         <span>student</span>
         <a href="profile.php" class="btn">view profile</a>
         <div class="flex-btn">
            <a href="login.php" class="option-btn">login</a>
            <a href="register.php" class="option-btn">register</a>
         </div>
         <a href="components/user_logout.php" onclick="return confirm('logout from this website?');" class="delete-btn">logout</a>
         <?php
            }else{
         ?>
         <h3>please login or register</h3>
          <div class="flex-btn">
            <a href="login.php" class="option-btn">login</a>
            <a href="register.php" class="option-btn">register</a>
         </div>
         <?php
            }
         ?>
      </div>

   </section>

</header>

<!-- header section ends -->

<!-- side bar section starts  -->

<div class="side-bar">

   <div class="close-side-bar">
      <i class="fas fa-times"></i>
   </div>

   <div class="profile">
         <?php
            $select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
            $select_profile->execute([$user_id]);
            if($select_profile->rowCount() > 0){
            $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
         ?>
         <img src="uploaded_files/<?= $fetch_profile['image']; ?>" alt="">
         <h3><?= $fetch_profile['name']; ?></h3>
         <span>student</span>
         <a href="profile.php" class="btn">view profile</a>
         <?php
            }else{
         ?>
         <h3>please login or register</h3>
          <div class="flex-btn" style="padding-top: .5rem;">
            <a href="login.php" class="option-btn">login</a>
            <a href="register.php" class="option-btn">register</a>
         </div>
         <?php
            }
         ?>
      </div>

   <nav class="navbar" aria-label="Main navigation">
      <a href="home.php" aria-label="Home"><i class="fas fa-home" aria-hidden="true"></i><span>home</span></a>
      <a href="about.php" aria-label="About Us"><i class="fas fa-question" aria-hidden="true"></i><span>about us</span></a>
      <a href="courses.php" aria-label="All Courses"><i class="fas fa-graduation-cap" aria-hidden="true"></i><span>courses</span></a>
      <a href="teachers.php" aria-label="Browse Teachers"><i class="fas fa-chalkboard-user" aria-hidden="true"></i><span>teachers</span></a>
      <a href="chat.php" aria-label="Chat Room"><i class="fas fa-comments" aria-hidden="true"></i><span>chat room</span></a>
      <a href="quiz.php" aria-label="Quizzes and Exams"><i class="fas fa-brain" aria-hidden="true"></i><span>quizzes &amp; exams</span></a>
      <a href="exam_history.php" aria-label="My Results"><i class="fas fa-history" aria-hidden="true"></i><span>my results</span></a>
      <a href="verify_certificate.php" aria-label="Verify Certificate"><i class="fas fa-certificate" aria-hidden="true"></i><span>verify certificate</span></a>
      <a href="contact.php" aria-label="Contact Us"><i class="fas fa-headset" aria-hidden="true"></i><span>contact us</span></a>
   </nav>

</div>

<!-- side bar section ends -->