<?php

include '../components/connect.php';

if(isset($_POST['submit']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')){

   $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
   
   $email = $_POST['email'];
   $email = sanitize_input($email);
   $pass = $_POST['pass'];

   // Select instructors table to match database specifications
   $select_tutor = $conn->prepare("SELECT * FROM `instructors` WHERE email = ? LIMIT 1");
   $select_tutor->execute([$email]);
   $row = $select_tutor->fetch(PDO::FETCH_ASSOC);
   
   $error_msg = '';

   if($select_tutor->rowCount() > 0){
      // Password verification using secure password_verify
      if(password_verify($pass, $row['password'])){
         $_SESSION['tutor_id'] = $row['id'];
         setcookie('tutor_id', $row['id'], time() + 60*60*24*30, '/');
         
         if($is_ajax){
            echo json_encode(['status' => 'success', 'redirect' => 'dashboard.php', 'message' => 'Login successful!']);
            exit;
         } else {
            header('location:dashboard.php');
            exit;
         }
      } else {
         $error_msg = 'incorrect email or password!';
      }
   } else {
      $error_msg = 'incorrect email or password!';
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
   <title><?= __('login'); ?></title>

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

   <form action="" method="post" enctype="multipart/form-data" class="login" id="login-form">
      <!-- Inject CSRF token protection hidden input -->
      <?php csrf_input_render(); ?>

      <h3>welcome back tutor!</h3>
      <p>your email <span>*</span></p>
      <input type="email" name="email" placeholder="enter your email" maxlength="50" required class="box">
      <p>your password <span>*</span></p>
      <input type="password" name="pass" placeholder="enter your password" maxlength="20" required class="box">
      
      <!-- Google Auth mock button integration -->
      <button type="button" class="btn google-btn" id="google-login-btn" style="background-color: #db4437; margin-bottom: 1rem;">
         <i class="fab fa-google" style="margin-right: .5rem;"></i> Sign in with Google
      </button>

      <div class="flex-btn" style="margin-bottom: 1rem;">
         <p class="link">forgot password? <a href="../forgot_password.php">reset here</a></p>
         <p class="link">don't have an account? <a href="register.php">register now</a></p>
      </div>

      <input type="submit" name="submit" value="login now" class="btn">
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