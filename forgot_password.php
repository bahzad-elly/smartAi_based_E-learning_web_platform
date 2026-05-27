<?php
include 'components/connect.php';

if(isset($_POST['submit']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')){

   $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
   $email = sanitize_input($_POST['email']);
   
   // Check both users and instructors tables for the email
   $select_user = $conn->prepare("SELECT id, 'student' AS role FROM `users` WHERE email = ? LIMIT 1");
   $select_user->execute([$email]);
   $row = $select_user->fetch(PDO::FETCH_ASSOC);
   
   if(!$row){
      $select_instructor = $conn->prepare("SELECT id, 'tutor' AS role FROM `instructors` WHERE email = ? LIMIT 1");
      $select_instructor->execute([$email]);
      $row = $select_instructor->fetch(PDO::FETCH_ASSOC);
   }

   $error_msg = '';
   $success_msg = '';

   if($row){
      // Generate a mock 6-digit OTP code and store it in session
      $otp = rand(100000, 999999);
      $_SESSION['reset_email'] = $email;
      $_SESSION['reset_role'] = $row['role'];
      $_SESSION['reset_otp'] = $otp;
      $_SESSION['reset_otp_expiry'] = time() + 300; // OTP valid for 5 minutes

      $success_msg = "Verification code generated successfully! [SIMULATED EMAIL DISPATCH] Your 6-digit verification code is: " . $otp;
      
      if($is_ajax){
         echo json_encode(['status' => 'success', 'redirect' => 'reset_password.php', 'message' => $success_msg]);
         exit;
      } else {
         $message[] = $success_msg;
         header('Refresh: 3; URL=reset_password.php');
      }
   } else {
      $error_msg = 'No account associated with that email was found!';
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
   <title>Forgot Password</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="form-container">

   <form action="" method="post" class="login" id="forgot-form">
      <?php csrf_input_render(); ?>

      <h3>Forgot Password</h3>
      <p style="text-align: center; font-size: 1.4rem; color: var(--light-color); margin-bottom: 2rem;">
         Enter your account email address below, and we will dispatch a 6-digit OTP verification code.
      </p>

      <p>your email <span>*</span></p>
      <input type="email" name="email" placeholder="enter your email" maxlength="50" required class="box">

      <input type="submit" name="submit" value="send reset code" class="btn">
      <p class="link" style="text-align: center; margin-top: 1.5rem;"><a href="login.php">Back to Login</a></p>
   </form>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
<script src="public/assets/js/auth.js"></script>
   
</body>
</html>
