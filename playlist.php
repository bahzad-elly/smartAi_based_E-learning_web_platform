<?php

include 'components/connect.php';

if(isset($_COOKIE['user_id'])){
   $user_id = $_COOKIE['user_id'];
}else{
   $user_id = '';
}

if(isset($_GET['get_id'])){
   $get_id = $_GET['get_id'];
}else{
   $get_id = '';
   header('location:home.php');
}

if(isset($_POST['save_list'])){

   if($user_id != ''){
      
      $list_id = $_POST['list_id'];
      $list_id = filter_var($list_id, FILTER_SANITIZE_STRING);

      $select_list = $conn->prepare("SELECT * FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
      $select_list->execute([$user_id, $list_id]);

      if($select_list->rowCount() > 0){
         $remove_bookmark = $conn->prepare("DELETE FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
         $remove_bookmark->execute([$user_id, $list_id]);
         $message[] = 'playlist removed!';
      }else{
         $insert_bookmark = $conn->prepare("INSERT INTO `bookmark`(user_id, playlist_id) VALUES(?,?)");
         $insert_bookmark->execute([$user_id, $list_id]);
         $message[] = 'playlist saved!';
      }

   }else{
      $message[] = 'please login first!';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Playlist | Smart AI E-Learning</title>
   <meta name="description" content="View course playlist and videos.">
   <meta name="csrf_token" content="<?= csrf_token_generate() ?>">

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'components/user_header.php'; ?>

<!-- playlist section starts  -->

<section class="playlist">

   <h1 class="heading">playlist details</h1>

   <div class="row">

      <?php
         $select_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? and status = ? LIMIT 1");
         $select_playlist->execute([$get_id, 'active']);
         if($select_playlist->rowCount() > 0){
            $fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC);

            $playlist_id = $fetch_playlist['id'];

            $count_videos = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ?");
            $count_videos->execute([$playlist_id]);
            $total_videos = $count_videos->rowCount();

            $select_tutor = $conn->prepare("SELECT * FROM `tutors` WHERE id = ? LIMIT 1");
            $select_tutor->execute([$fetch_playlist['tutor_id']]);
            $fetch_tutor = $select_tutor->fetch(PDO::FETCH_ASSOC);

            $select_bookmark = $conn->prepare("SELECT * FROM `bookmark` WHERE user_id = ? AND playlist_id = ?");
            $select_bookmark->execute([$user_id, $playlist_id]);

      ?>

      <div class="col">
         <!-- AJAX Bookmark Button (Part 9) -->
         <?php
            $is_bookmarked = ($select_bookmark->rowCount() > 0);
         ?>
         <div class="save-list">
            <button class="ajax-bookmark-btn <?= $is_bookmarked ? 'bookmarked' : '' ?>"
                    data-playlist-id="<?= $playlist_id; ?>"
                    title="<?= $is_bookmarked ? 'Remove Bookmark' : 'Save Playlist' ?>">
               <i class="<?= $is_bookmarked ? 'fas' : 'far' ?> fa-bookmark"></i>
               <span><?= $is_bookmarked ? 'Saved' : 'Save Playlist' ?></span>
            </button>
         </div>
         <div class="thumb">
            <span><?= $total_videos; ?> videos</span>
            <img src="uploaded_files/<?= $fetch_playlist['thumb']; ?>" alt="">
         </div>
      </div>

      <div class="col">
         <div class="tutor">
            <img src="uploaded_files/<?= $fetch_tutor['image']; ?>" alt="">
            <div>
               <h3><?= $fetch_tutor['name']; ?></h3>
               <span><?= $fetch_tutor['profession']; ?></span>
            </div>
         </div>
         <div class="details">
            <h3><?= $fetch_playlist['title']; ?></h3>
            <p><?= $fetch_playlist['description']; ?></p>
            <div class="date"><i class="fas fa-calendar"></i><span><?= $fetch_playlist['date']; ?></span></div>
         </div>
      </div>

      <?php
         }else{
            echo '<p class="empty">this playlist was not found!</p>';
         }  
      ?>

   </div>

</section>

<!-- playlist section ends -->

<!-- videos container section starts  -->

<section class="videos-container">

   <h1 class="heading">playlist videos</h1>

   <div class="box-container">

      <?php
         $select_content = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ? AND status = ? ORDER BY date DESC");
         $select_content->execute([$get_id, 'active']);
         if($select_content->rowCount() > 0){
            while($fetch_content = $select_content->fetch(PDO::FETCH_ASSOC)){  
      ?>
      <a href="watch_video.php?get_id=<?= $fetch_content['id']; ?>" class="box">
         <i class="fas fa-play"></i>
         <img src="uploaded_files/<?= $fetch_content['thumb']; ?>" alt="">
         <h3><?= $fetch_content['title']; ?></h3>
      </a>
      <?php
            }
         }else{
            echo '<p class="empty">no videos added yet!</p>';
         }
      ?>

   </div>

</section>

<!-- videos container section ends -->











<?php include 'components/footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>
<script src="js/ajax.js"></script>
   
</body>
</html>