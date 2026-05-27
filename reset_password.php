<?php
include 'components/connect.php';

// Force redirection if forgot_password was not called
if(!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])){
   header('location:forgot_password.php');
   exit;
}

if(isset($_POST['submit']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')){

   $is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
   
   $otp = sanitize_input($_POST['otp']);
   $new_pass = $_POST['new_pass'];
   $confirm_pass = $_POST['confirm_pass'];
   
   $error_msg = '';
   $success_msg = '';

   // Check OTP Correctness and Expiry
   if(time() > $_SESSION['reset_otp_expiry']){
      $error_msg = 'The verification code has expired! Please try again.';
   } elseif($otp != $_SESSION['reset_otp']){
      $error_msg = 'Incorrect verification code!';
   } elseif($new_pass !== $confirm_pass){
      $error_msg = 'Confirm password does not match!';
   } else {
      $hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
      $email = $_SESSION['reset_email'];
      $role = $_SESSION['reset_role'];

      if($role === 'student'){
         $update_query = $conn->prepare("UPDATE `users` SET password = ? WHERE email = ?");
      } else {
         $update_query = $conn->prepare("UPDATE `instructors` SET password = ? WHERE email = ?");
      }
      
      $update_query->execute([$hashed_pass, $email]);

      // Clear the temporary reset sessions
      unset($_SESSION['reset_email']);
      unset($_SESSION['reset_role']);
      unset($_SESSION['reset_otp']);
      unset($_SESSION['reset_otp_expiry']);

      $success_msg = 'Your password was updated successfully! Redirecting you to login...';

      if($is_ajax){
         // Redirect tutors to admin portal and students to student portal
         $redirect_path = ($role === 'tutor') ? 'admin/login.php' : 'login.php';
         echo json_encode(['status' => 'success', 'redirect' => $redirect_path, 'message' => $success_msg]);
         exit;
      } else {
         $message[] = $success_msg;
         $redirect_path = ($role === 'tutor') ? 'admin/login.php' : 'login.php';
         header("Refresh: 2; URL={$redirect_path}");
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
   <title>Reset Password</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="form-container">

   <form action="" method="post" class="login" id="reset-form">
      <?php csrf_input_render(); ?>

      <h3>Reset Password</h3>
      <p style="text-align: center; font-size: 1.4rem; color: var(--light-color); margin-bottom: 2rem;">
         A verification code has been dispatched to <strong><?= sanitize_input($_SESSION['reset_email']); ?></strong>. Please verify it below.
      </p>

      <p>Verification Code <span>*</span></p>
      <input type="number" name="otp" placeholder="enter 6-digit OTP code" maxlength="6" required class="box">

      <p>New Password <span>*</span></p>
      <input type="password" name="new_pass" placeholder="enter new password" maxlength="20" required class="box">

      <p>Confirm Password <span>*</span></p>
      <input type="password" name="confirm_pass" placeholder="confirm new password" maxlength="20" required class="box">

      <input type="submit" name="submit" value="update password" class="btn">
   </form>

</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js"></script>
<script src="public/assets/js/auth.js"></script>
   
</body>
</html>
