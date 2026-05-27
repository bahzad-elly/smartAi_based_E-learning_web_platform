<?php

include 'components/connect.php';

if(isset($_POST['submit']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')){

   $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
   
   $email = $_POST['email'];
   $email = sanitize_input($email);
   $pass = $_POST['pass'];

   $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ? LIMIT 1");
   $select_user->execute([$email]);
   $row = $select_user->fetch(PDO::FETCH_ASSOC);
   
   $error_msg = '';

   if($select_user->rowCount() > 0){
      // Password verification using secure password_verify instead of sha1
      if(password_verify($pass, $row['password'])){
         $_SESSION['user_id'] = $row['id'];
         setcookie('user_id', $row['id'], time() + 60*60*24*30, '/');
         
         if($is_ajax){
            echo json_encode(['status' => 'success', 'redirect' => 'home.php', 'message' => 'Login successful!']);
            exit;
         } else {
            header('location:home.php');
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
   <link rel="stylesheet" href="css/style.css">

</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="form-container">

   <form action="" method="post" enctype="multipart/form-data" class="login" id="login-form">
      <!-- Inject CSRF token protection hidden input -->
      <?php csrf_input_render(); ?>

      <h3>welcome back!</h3>
      <p>your email <span>*</span></p>
      <input type="email" name="email" placeholder="enter your email" maxlength="50" required class="box">
      <p>your password <span>*</span></p>
      <input type="password" name="pass" placeholder="enter your password" maxlength="20" required class="box">
      
      <!-- Google Auth mock button integration -->
      <button type="button" class="btn google-btn" id="google-login-btn" style="background-color: #db4437; margin-bottom: 1rem;">
         <i class="fab fa-google" style="margin-right: .5rem;"></i> Sign in with Google
      </button>

      <div class="flex-btn" style="margin-bottom: 1rem;">
         <p class="link">forgot password? <a href="forgot_password.php">reset here</a></p>
         <p class="link">new user? <a href="register.php">register now</a></p>
      </div>
      
      <input type="submit" name="submit" value="login now" class="btn">
   </form>

</section>

<?php include 'components/footer.php'; ?>

<!-- custom js file link  -->
<script src="js/script.js"></script>
<script src="public/assets/js/auth.js"></script>
   
</body>
</html>