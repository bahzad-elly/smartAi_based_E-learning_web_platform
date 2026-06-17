<footer class="footer" role="contentinfo">
   <p>&copy; <?= date('Y'); ?> <span>Smart AI E-Learning Platform</span> &mdash; All rights reserved.</p>
   <nav class="footer-links" aria-label="Footer navigation">
      <a href="<?= (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : ''; ?>about.php">About</a>
      <a href="<?= (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : ''; ?>contact.php">Contact</a>
      <a href="<?= (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : ''; ?>verify_certificate.php">Verify Certificate</a>
   </nav>
</footer>

<?php
// Dynamic path prefix for subfolder file inclusions
$path_prefix = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : '';
?>
<!-- Floating AI Chatbot Widget (Part 12) -->
<?php include_once $path_prefix . 'components/chatbot.php'; ?>
<script src="<?= $path_prefix ?>js/chatbot.js" defer></script>