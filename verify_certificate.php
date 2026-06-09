<?php
/**
 * Smart AI E-Learning - Certificate Verification Page
 * Publicly accessible - anyone can verify a certificate by its code
 */

include 'components/connect.php';
$bypass_csrf = true; // GET-only page, CSRF not needed

$cert_code = isset($_GET['code']) ? sanitize_input($_GET['code']) : '';
$cert = null;
$verified = false;

if ($cert_code) {
    $stmt = $conn->prepare("
        SELECT c.*,
               u.name AS student_name,
               q.title AS quiz_title,
               p.title AS course_title
        FROM `certificates` c
        JOIN `users` u ON u.id = c.user_id
        JOIN `quizzes` q ON q.id = c.quiz_id
        LEFT JOIN `playlist` p ON p.id = q.playlist_id
        WHERE c.certificate_code = ?
        LIMIT 1
    ");
    $stmt->execute([$cert_code]);
    $cert = $stmt->fetch();
    $verified = ($cert !== false);
}

$issued_date = $cert ? date('F j, Y', strtotime($cert['issued_at'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Verify Certificate | Smart AI E-Learning</title>
   <meta name="description" content="Verify the authenticity of a Smart AI E-Learning certificate.">
   <meta name="csrf_token" content="<?= csrf_token_generate() ?>">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <style>
      .verify-page { max-width: 750px; margin: 0 auto; padding: 2rem; }

      .verify-hero {
         text-align: center;
         padding: 4rem 2rem;
         border-radius: 1rem;
         margin-bottom: 3rem;
      }
      .verify-hero.valid {
         background: linear-gradient(135deg, #d5f5e3, #a9dfbf);
         border: 3px solid #27ae60;
      }
      .verify-hero.invalid {
         background: linear-gradient(135deg, #fde8e8, #f5b7b1);
         border: 3px solid #e74c3c;
      }
      .verify-hero.neutral {
         background: var(--white);
         border: 3px solid var(--light-bg);
      }
      .verify-hero .big-icon { font-size: 6rem; margin-bottom: 1.5rem; }
      .verify-hero h2 { font-size: 2.8rem; color: var(--black); margin-bottom: 1rem; }
      .verify-hero p  { font-size: 1.7rem; color: var(--light-color); }

      /* Search form */
      .verify-form {
         background: var(--white);
         border-radius: 1rem;
         padding: 3rem;
         margin-bottom: 3rem;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
      }
      .verify-form h3 { font-size: 2rem; color: var(--black); margin-bottom: 1.5rem; }
      .verify-input-group {
         display: flex;
         gap: 1rem;
         flex-wrap: wrap;
      }
      .verify-input-group input {
         flex: 1;
         padding: 1.4rem 2rem;
         border: 2px solid var(--light-bg);
         border-radius: .8rem;
         font-size: 1.7rem;
         color: var(--black);
         background: var(--white);
         font-family: 'Nunito', sans-serif;
         transition: border-color .2s;
         min-width: 20rem;
      }
      .verify-input-group input:focus { border-color: var(--main-color); }
      .verify-input-group input::placeholder { color: var(--light-color); }
      .verify-input-group button {
         background: linear-gradient(135deg, var(--main-color), #6c2d9a);
         color: #fff;
         border: none;
         border-radius: .8rem;
         padding: 1.4rem 3rem;
         font-size: 1.7rem;
         font-weight: 700;
         cursor: pointer;
         display: flex;
         align-items: center;
         gap: .8rem;
         transition: opacity .2s;
      }
      .verify-input-group button:hover { opacity: .85; }

      /* Certificate details */
      .cert-details {
         background: var(--white);
         border-radius: 1rem;
         padding: 3rem;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
         margin-bottom: 2rem;
      }
      .cert-details h3 {
         font-size: 2rem;
         color: var(--black);
         margin-bottom: 2rem;
         padding-bottom: 1rem;
         border-bottom: 2px solid var(--light-bg);
      }
      .detail-row {
         display: flex;
         gap: 1.5rem;
         align-items: flex-start;
         padding: 1.2rem 0;
         border-bottom: 1px solid var(--light-bg);
      }
      .detail-row:last-child { border-bottom: none; }
      .detail-row .icon {
         width: 4rem;
         height: 4rem;
         border-radius: 50%;
         background: rgba(142,68,173,.1);
         display: flex;
         align-items: center;
         justify-content: center;
         flex-shrink: 0;
      }
      .detail-row .icon i { color: var(--main-color); font-size: 1.6rem; }
      .detail-row .info label { font-size:1.3rem; color:var(--light-color); display:block; margin-bottom:.3rem; }
      .detail-row .info span { font-size:1.7rem; color:var(--black); font-weight:600; }

      .valid-badge {
         display: inline-flex;
         align-items: center;
         gap: .8rem;
         background: #d5f5e3;
         color: #1e8449;
         padding: .8rem 2rem;
         border-radius: 2rem;
         font-size: 1.7rem;
         font-weight: 700;
         margin-bottom: 2rem;
      }
      .valid-badge i { font-size: 2rem; }

      /* Security info */
      .security-note {
         background: rgba(142,68,173,.06);
         border-left: 4px solid var(--main-color);
         border-radius: 0 .5rem .5rem 0;
         padding: 1.5rem;
         margin-bottom: 2rem;
         font-size: 1.5rem;
         color: var(--light-color);
         line-height: 1.7;
      }
      .security-note i { color: var(--main-color); margin-right: .5rem; }

      /* How to use section */
      .how-it-works {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
         gap: 1.5rem;
         margin-top: 3rem;
      }
      .how-card {
         background: var(--white);
         border-radius: 1rem;
         padding: 2rem;
         text-align: center;
         box-shadow: 0 .3rem 1.5rem rgba(0,0,0,.07);
      }
      .how-card .step-num {
         width: 4.5rem; height: 4.5rem;
         border-radius: 50%;
         background: linear-gradient(135deg, var(--main-color), #6c2d9a);
         color: #fff;
         font-size: 1.8rem;
         font-weight: 900;
         display: flex;
         align-items: center;
         justify-content: center;
         margin: 0 auto 1rem;
      }
      .how-card h4 { font-size: 1.6rem; color: var(--black); margin-bottom: .5rem; }
      .how-card p  { font-size: 1.4rem; color: var(--light-color); line-height: 1.6; }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="verify-page">

   <h1 class="heading"><i class="fas fa-shield-alt" style="color:var(--main-color);"></i> Certificate Verification</h1>

   <!-- AJAX Search Form -->
   <div class="verify-form">
      <h3><i class="fas fa-search" style="color:var(--main-color);"></i> Enter Certificate Code</h3>
      <form id="ajax-verify-form">
         <div class="verify-input-group">
            <input type="text" id="cert-code-input" name="code"
                   placeholder="e.g. CERT-A1B2C3D4E5"
                   value="<?= htmlspecialchars($cert_code) ?>"
                   maxlength="30" autocomplete="off"
                   style="text-transform:uppercase; letter-spacing:.05em;">
            <button type="submit"><i class="fas fa-search"></i> Verify Now</button>
         </div>
      </form>
   </div>

   <!-- AJAX Result Container -->
   <div id="verify-result">
   <?php if ($cert_code): ?>
      <?php if ($verified): ?>
      <!-- Pre-loaded valid result (from URL param) -->
      <div class="verify-hero valid animate-in">
         <div class="big-icon">✅</div>
         <h2>Certificate is Valid!</h2>
         <p>This certificate has been verified as authentic and issued by Smart AI E-Learning Platform.</p>
      </div>
      <div class="valid-badge"><i class="fas fa-check-circle"></i> Verified Authentic Certificate</div>
      <div class="cert-details">
         <h3><i class="fas fa-certificate" style="color:#f39c12;"></i> Certificate Details</h3>
         <div class="detail-row">
            <div class="icon"><i class="fas fa-user-graduate"></i></div>
            <div class="info"><label>Student Name</label><span><?= htmlspecialchars($cert['student_name']) ?></span></div>
         </div>
         <div class="detail-row">
            <div class="icon"><i class="fas fa-brain"></i></div>
            <div class="info"><label>Quiz / Assessment</label><span><?= htmlspecialchars($cert['quiz_title']) ?></span></div>
         </div>
         <div class="detail-row">
            <div class="icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="info"><label>Course</label><span><?= htmlspecialchars($cert['course_title'] ?? 'General Studies') ?></span></div>
         </div>
         <div class="detail-row">
            <div class="icon"><i class="fas fa-calendar-check"></i></div>
            <div class="info"><label>Date of Issue</label><span><?= $issued_date ?></span></div>
         </div>
         <div class="detail-row">
            <div class="icon"><i class="fas fa-fingerprint"></i></div>
            <div class="info"><label>Certificate ID</label><span style="font-family:monospace;"><?= htmlspecialchars($cert['certificate_code']) ?></span></div>
         </div>
         <div class="detail-row">
            <div class="icon"><i class="fas fa-hashtag"></i></div>
            <div class="info"><label>Verification Hash</label><span style="font-family:monospace; font-size:1.3rem; color:var(--light-color);"><?= substr($cert['qr_hash'], 0, 32) ?>...</span></div>
         </div>
      </div>
      <?php else: ?>
      <div class="verify-hero invalid animate-in">
         <div class="big-icon">❌</div>
         <h2>Certificate Not Found</h2>
         <p>The code "<strong><?= htmlspecialchars($cert_code) ?></strong>" does not match any certificate in our system.</p>
      </div>
      <?php endif; ?>
   <?php else: ?>
   <div class="verify-hero neutral">
      <div class="big-icon">🔍</div>
      <h2>Verify a Certificate</h2>
      <p>Enter a certificate ID above to verify its authenticity instantly.</p>
   </div>
   <?php endif; ?>
   </div>

   <!-- Security Note -->
   <div class="security-note">
      <i class="fas fa-lock"></i>
      <strong>Security Notice:</strong> All certificates are cryptographically signed with a unique hash.
      This verification page is the official source of truth for all Smart AI E-Learning certificates.
      Any certificate not found here should be considered invalid.
   </div>

   <!-- How it works -->
   <h2 style="font-size:2rem; color:var(--black); margin-bottom:1.5rem;">How Certificate Verification Works</h2>
   <div class="how-it-works">
      <div class="how-card">
         <div class="step-num">1</div>
         <h4>Complete a Quiz</h4>
         <p>Pass a quiz with a score above the required threshold.</p>
      </div>
      <div class="how-card">
         <div class="step-num">2</div>
         <h4>Get Your Certificate</h4>
         <p>A certificate is automatically generated with a unique ID and QR code.</p>
      </div>
      <div class="how-card">
         <div class="step-num">3</div>
         <h4>Share & Verify</h4>
         <p>Share your certificate ID or QR code. Employers can verify here instantly.</p>
      </div>
   </div>

</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
<script src="js/ajax.js"></script>
</body>
</html>
