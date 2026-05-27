<?php

include '../components/connect.php';

if(isset($_POST['submit']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')){

   $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

   $id = unique_id();
   $name = $_POST['name'];
   $name = sanitize_input($name);
   $profession = $_POST['profession'];
   $profession = sanitize_input($profession);
   $email = $_POST['email'];
   $email = sanitize_input($email);
   
   $pass = $_POST['pass'];
   $cpass = $_POST['cpass'];

   $image = $_FILES['image']['name'];
   $image = sanitize_input($image);
   $ext = pathinfo($image, PATHINFO_EXTENSION);
   $rename = unique_id().'.'.$ext;
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = '../uploaded_files/'.$rename;

   // Select instructors table to perfectly match the Part 2 blueprint
   $select_tutor = $conn->prepare("SELECT * FROM `instructors` WHERE email = ?");
   $select_tutor->execute([$email]);
   
   $error_msg = '';

   if($select_tutor->rowCount() > 0){
      $error_msg = 'email already taken!';
   } elseif($pass != $cpass){
      $error_msg = 'confirm password not matched!';
   } elseif($image_size > 2000000) {
      $error_msg = 'image size is too large!';
   } else {
      $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
      
      $insert_tutor = $conn->prepare("INSERT INTO `instructors`(id, name, profession, email, password, image) VALUES(?,?,?,?,?,?)");
      $insert_tutor->execute([$id, $name, $profession, $email, $hashed_password, $rename]);
      move_uploaded_file($image_tmp_name, $image_folder);
      
      // Auto login tutor into session
      $_SESSION['tutor_id'] = $id;
      setcookie('tutor_id', $id, time() + 60*60*24*30, '/');
      
      if($is_ajax){
         echo json_encode(['status' => 'success', 'redirect' => 'dashboard.php', 'message' => 'Registration successful!']);
         exit;
      } else {
         header('location:dashboard.php');
         exit;
      }
   }

   if($is_ajax && !empty($error_msg)){
      echo json_encode(['status' => 'error', 'message' => $error_msg]);
      exit;
   } else {
      $message[] = $error_msg;
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= __('register'); ?></title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body style="padding-left: 0;">

<?php
if(isset($message)){
   foreach($message as $message){
      echo '
      <div class="message form">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<section class="form-container">

   <form class="register" action="" method="post" enctype="multipart/form-data" id="register-form">
      <!-- CSRF protection -->
      <?php csrf_input_render(); ?>

      <h3>register new tutor</h3>
      
      <div class="flex">
         <div class="col">
            <p>your name <span>*</span></p>
            <input type="text" name="name" placeholder="enter your name" maxlength="50" required class="box">
            <p>your profession <span>*</span></p>
            <select name="profession" class="box" required>
               <option value="" disabled selected>-- select your profession</option>
               <option value="developer">developer</option>
               <option value="designer">designer</option>
               <option value="musician">musician</option>
               <option value="biologist">biologist</option>
               <option value="teacher">teacher</option>
               <option value="engineer">engineer</option>
               <option value="lawyer">lawyer</option>
               <option value="accountant">accountant</option>
               <option value="doctor">doctor</option>
               <option value="journalist">journalist</option>
               <option value="photographer">photographer</option>
            </select>
            <p>your email <span>*</span></p>
            <input type="email" name="email" placeholder="enter your email" maxlength="50" required class="box">
         </div>
         <div class="col">
            <p>your password <span>*</span></p>
            <input type="password" name="pass" id="pass" placeholder="enter your password" maxlength="20" required class="box">
            <p>confirm password <span>*</span></p>
            <input type="password" name="cpass" id="cpass" placeholder="confirm your password" maxlength="20" required class="box">
            <p>select pic <span>*</span></p>
            <input type="file" name="image" accept="image/*" required class="box">
         </div>
      </div>
      
      <!-- Google Auth mock button integration -->
      <button type="button" class="btn google-btn" id="google-login-btn" style="background-color: #db4437; margin-bottom: 1rem;">
         <i class="fab fa-google" style="margin-right: .5rem;"></i> Sign in with Google
      </button>

      <p class="link">already have an account? <a href="login.php">login now</a></p>
      <input type="submit" name="submit" value="register now" class="btn">
   </form>

</section>

<script>
let darkMode = localStorage.getItem('dark-mode');
let body = document.body;

const enableDarkMode = () =>{
   body.classList.add('dark');
   localStorage.setItem('dark-mode', 'enabled');
}

const disableDarkMode = () =>{
   body.classList.remove('dark');
   localStorage.setItem('dark-mode', 'disabled');
}

if(darkMode === 'enabled'){
   enableDarkMode();
}else{
   disableDarkMode();
}
</script>
<script src="../public/assets/js/auth.js"></script>
   
</body>
</html>