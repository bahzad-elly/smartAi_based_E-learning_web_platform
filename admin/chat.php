<?php
/**
 * Smart AI E-Learning - Chat Room (Part 10)
 * Instructor/Tutor-facing Messenger layout
 */

// Bypassing CSRF verification for GET dashboard queries
$bypass_csrf = true; 
require_once __DIR__ . '/../components/connect.php';

if (empty($tutor_id)) {
    header('location: login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Instructor Chat Room | Smart AI E-Learning</title>
   <meta name="description" content="Communicate in real-time with your course students.">
   <meta name="csrf_token" content="<?= csrf_token_generate() ?>">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
   <!-- Inherit user css custom elements for chat formatting directly -->
   <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="chat-container-main" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">

   <div class="chat-layout">
      <!-- Contacts List -->
      <div class="chat-sidebar">
         <div class="chat-sidebar-header">
            <h3><i class="fas fa-comments"></i> Students</h3>
         </div>
         <div class="chat-contacts" id="chat-contacts-list">
            <div class="ls-empty"><i class="fas fa-spinner fa-spin"></i> Loading contacts...</div>
         </div>
      </div>

      <!-- Messages Window -->
      <div class="chat-window" id="chat-window-box">
         <div class="chat-window-welcome" id="chat-welcome-screen">
            <i class="far fa-paper-plane" style="font-size: 6rem; color: var(--main-color); margin-bottom: 2rem;"></i>
            <h2>Student Messages</h2>
            <p>Select a student from the sidebar list to start a private conversation.</p>
         </div>

         <!-- Active Thread -->
         <div class="chat-active-thread" id="chat-active-thread" style="display: none;">
            <div class="chat-header-user">
               <img src="../images/default.png" alt="" id="chat-recipient-image">
               <div class="chat-recipient-details">
                  <h3 id="chat-recipient-name">Student Name</h3>
                  <span id="chat-recipient-status" class="status-offline"><i class="fas fa-circle"></i> Offline</span>
               </div>
            </div>

            <!-- Messages Area -->
            <div class="chat-messages" id="chat-messages-area">
               <!-- Messages render here -->
            </div>

            <!-- Typing indicator -->
            <div class="typing-indicator-wrapper" id="typing-indicator" style="display: none;">
               <div class="typing-dots">
                  <span></span><span></span><span></span>
               </div>
            </div>

            <!-- Attachment Preview -->
            <div class="attachment-preview" id="attachment-preview-container" style="display: none;">
               <span id="attachment-name">file.zip</span>
               <button type="button" id="remove-attachment-btn"><i class="fas fa-times"></i></button>
            </div>

            <!-- Input area -->
            <form id="chat-send-form" enctype="multipart/form-data" class="chat-input-form">
               <label for="attachment-input" class="chat-attach-btn" title="Upload Attachment">
                  <i class="fas fa-paperclip"></i>
               </label>
               <input type="file" id="attachment-input" name="attachment" style="display: none;">
               
               <input type="text" id="chat-input-message" placeholder="Type a message..." autocomplete="off">
               <button type="submit" class="chat-send-btn"><i class="fas fa-paper-plane"></i></button>
            </form>
         </div>
      </div>
   </div>

</section>

<?php include '../components/footer.php'; ?>

<script>
   let darkMode = localStorage.getItem('dark-mode');
   let body = document.body;
   if(darkMode === 'enabled') body.classList.add('dark');
</script>
<script src="../js/admin_script.js"></script>
<script>
   // Export global authenticated configurations for chat.js
   window.chatConfig = {
      role: 'tutor',
      csrfToken: '<?= csrf_token_generate() ?>',
      endpointPrefix: '../'
   };
</script>
<script src="../js/chat.js"></script>

</body>
</html>
