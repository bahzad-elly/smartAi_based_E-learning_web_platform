<?php
/**
 * Smart AI E-Learning - Certificate PDF Generator
 * Generates and downloads a professional PDF certificate using FPDF
 */

include 'components/connect.php';

if (empty($user_id)) { header('location: login.php'); exit; }

$cert_id = isset($_GET['cert_id']) ? sanitize_input($_GET['cert_id']) : '';
if (!$cert_id) { header('location: quiz.php'); exit; }

// Fetch certificate info
$cert_stmt = $conn->prepare("
    SELECT c.*,
           u.name AS student_name,
           u.email AS student_email,
           q.title AS quiz_title,
           p.title AS course_title
    FROM `certificates` c
    JOIN `users` u ON u.id = c.user_id
    JOIN `quizzes` q ON q.id = c.quiz_id
    LEFT JOIN `playlist` p ON p.id = q.playlist_id
    WHERE c.id = ? AND c.user_id = ?
    LIMIT 1
");
$cert_stmt->execute([$cert_id, $user_id]);
$cert = $cert_stmt->fetch();

if (!$cert) {
    header('location: quiz.php');
    exit;
}

// Show HTML preview page with download button
$issued_date = date('F j, Y', strtotime($cert['issued_at']));
$verify_url  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') .
               '://' . $_SERVER['HTTP_HOST'] .
               dirname($_SERVER['PHP_SELF']) . '/verify_certificate.php?code=' . urlencode($cert['certificate_code']);

// QR code via Google Charts API
$qr_url = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($verify_url) . '&choe=UTF-8';
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Certificate | <?= htmlspecialchars($cert['student_name']) ?></title>
   <meta name="description" content="Download and share your Smart AI E-Learning certificate.">
   <meta name="csrf_token" content="<?= csrf_token_generate() ?>">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
   <style>
      .cert-page {
         max-width: 1000px;
         margin: 0 auto;
         padding: 2rem;
      }

      /* Certificate Preview */
      .certificate-preview {
         background: #fff;
         border: 12px solid #8e44ad;
         border-radius: 4px;
         padding: 0;
         position: relative;
         box-shadow: 0 2rem 6rem rgba(0,0,0,.15);
         overflow: hidden;
         margin-bottom: 3rem;
         aspect-ratio: 1.414 / 1;  /* A4 landscape ratio */
      }

      /* Inner gold border */
      .cert-inner {
         border: 4px solid #f39c12;
         margin: 1.5rem;
         height: calc(100% - 3rem);
         display: flex;
         flex-direction: column;
         align-items: center;
         justify-content: space-between;
         padding: 3rem 4rem;
         position: relative;
         background:
            radial-gradient(ellipse at top left, rgba(142,68,173,.06) 0%, transparent 50%),
            radial-gradient(ellipse at bottom right, rgba(243,156,18,.06) 0%, transparent 50%),
            #fff;
      }

      /* Corner ornaments */
      .cert-inner::before, .cert-inner::after {
         content: '❋';
         position: absolute;
         font-size: 2.5rem;
         color: #f39c12;
      }
      .cert-inner::before { top: 1rem; left: 1rem; }
      .cert-inner::after  { bottom: 1rem; right: 1rem; }

      .cert-header { text-align: center; width: 100%; }
      .cert-logo {
         font-family: 'Cinzel', serif;
         font-size: 2.2rem;
         color: #8e44ad;
         letter-spacing: .2em;
         text-transform: uppercase;
         margin-bottom: .8rem;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 1rem;
      }
      .cert-logo i { font-size: 2.8rem; }
      .cert-subtitle {
         font-size: 1.3rem;
         color: #888;
         letter-spacing: .3em;
         text-transform: uppercase;
         font-family: 'Nunito', sans-serif;
      }

      /* Divider */
      .cert-divider {
         width: 80%;
         height: 2px;
         background: linear-gradient(90deg, transparent, #f39c12, transparent);
         margin: 1.5rem auto;
      }

      .cert-body { text-align: center; flex: 1; display:flex; flex-direction:column; align-items:center; justify-content:center; }
      .cert-presented {
         font-family: 'Cinzel', serif;
         font-size: 1.4rem;
         color: #888;
         letter-spacing: .15em;
         text-transform: uppercase;
         margin-bottom: 1rem;
      }
      .cert-name {
         font-family: 'Cinzel', serif;
         font-size: 3.5rem;
         color: #2c3e50;
         font-weight: 700;
         border-bottom: 3px solid #8e44ad;
         padding-bottom: .5rem;
         margin-bottom: 1.5rem;
         line-height: 1.2;
      }
      .cert-description {
         font-family: 'Nunito', sans-serif;
         font-size: 1.6rem;
         color: #555;
         line-height: 1.7;
         max-width: 60rem;
         margin-bottom: 1rem;
      }
      .cert-course {
         font-family: 'Cinzel', serif;
         font-size: 2rem;
         color: #8e44ad;
         font-weight: 600;
         margin-bottom: .5rem;
      }

      .cert-footer {
         display: flex;
         justify-content: space-between;
         align-items: flex-end;
         width: 100%;
         flex-wrap: wrap;
         gap: 1rem;
      }
      .cert-footer .left, .cert-footer .right { text-align: center; flex: 1; min-width: 12rem; }
      .cert-footer .center { text-align: center; }
      .cert-footer .sig-line {
         width: 15rem;
         height: 2px;
         background: #8e44ad;
         margin: 0 auto 0.5rem;
      }
      .cert-footer .sig-text { font-family: 'Nunito', sans-serif; font-size: 1.3rem; color: #888; }
      .cert-footer .date-text { font-family: 'Cinzel', serif; font-size: 1.4rem; color: #2c3e50; font-weight: 600; }

      .cert-id {
         font-size: 1.1rem;
         color: #bbb;
         font-family: 'Nunito', sans-serif;
         letter-spacing: .05em;
         text-align: center;
         margin-top: .5rem;
      }

      .qr-block { text-align: center; }
      .qr-block img { width: 7rem; height: 7rem; }
      .qr-block small { display: block; font-size: 1rem; color: #bbb; margin-top: .3rem; }

      /* Watermark */
      .cert-watermark {
         position: absolute;
         top: 50%;
         left: 50%;
         transform: translate(-50%, -50%) rotate(-30deg);
         font-family: 'Cinzel', serif;
         font-size: 8rem;
         color: rgba(142,68,173,.04);
         white-space: nowrap;
         pointer-events: none;
         user-select: none;
         letter-spacing: .3em;
      }

      /* Action Buttons */
      .cert-actions {
         display: flex;
         gap: 1.5rem;
         flex-wrap: wrap;
         margin-bottom: 2rem;
      }
      .btn-download {
         flex: 1;
         background: linear-gradient(135deg, #8e44ad, #6c2d9a);
         color: #fff;
         padding: 1.5rem 3rem;
         border-radius: .8rem;
         font-size: 1.8rem;
         font-weight: 700;
         text-align: center;
         cursor: pointer;
         border: none;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 1rem;
         text-decoration: none;
         transition: all .2s;
      }
      .btn-download:hover { opacity: .85; transform: translateY(-2px); }
      .btn-verify {
         background: var(--light-bg);
         color: var(--black);
         padding: 1.5rem 2.5rem;
         border-radius: .8rem;
         font-size: 1.7rem;
         font-weight: 600;
         text-align: center;
         text-decoration: none;
         display: flex;
         align-items: center;
         gap: 1rem;
         transition: all .2s;
      }
      .btn-verify:hover { background: var(--black); color: var(--white); }

      /* Certificate ID card */
      .cert-id-card {
         background: var(--white);
         border-radius: 1rem;
         padding: 2rem;
         border: 2px dashed var(--main-color);
         display: flex;
         align-items: center;
         gap: 2rem;
         flex-wrap: wrap;
         margin-bottom: 2rem;
      }
      .cert-id-card .id-icon { font-size: 3rem; color: var(--main-color); }
      .cert-id-card .id-info { flex: 1; }
      .cert-id-card .id-info h4 { font-size: 1.6rem; color: var(--black); margin-bottom: .3rem; }
      .cert-id-card .id-info code {
         font-size: 1.8rem;
         color: var(--main-color);
         font-weight: 700;
         background: rgba(142,68,173,.1);
         padding: .3rem 1rem;
         border-radius: .3rem;
         letter-spacing: .1em;
      }
      .cert-id-card .id-info small { display:block; font-size:1.3rem; color:var(--light-color); margin-top:.3rem; }

      @media print {
         body * { visibility: hidden; }
         .certificate-preview, .certificate-preview * { visibility: visible; }
         .certificate-preview { position: fixed; inset: 0; margin: 0; box-shadow: none; border-width: 6px; }
      }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<section class="cert-page">

   <!-- Confetti Canvas -->
   <canvas id="cert-confetti"></canvas>

   <h1 class="heading"><i class="fas fa-certificate" style="color:#f39c12;"></i> Your Certificate</h1>

   <!-- Certificate ID Card -->
   <div class="cert-id-card">
      <div class="id-icon"><i class="fas fa-fingerprint"></i></div>
      <div class="id-info">
         <h4>Certificate ID</h4>
         <code id="cert-code-display"><?= htmlspecialchars($cert['certificate_code']) ?></code>
         <button class="copy-id-btn" onclick="copyCertId()" title="Copy Certificate ID">
            <i class="fas fa-copy"></i> Copy
         </button>
         <small>Issued: <?= $issued_date ?> &nbsp;|&nbsp; Use this code to verify your certificate</small>
      </div>
   </div>

   <!-- Share Buttons -->
   <div class="share-buttons">
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($verify_url) ?>" target="_blank" class="share-btn share-linkedin">
         <i class="fab fa-linkedin"></i> Share on LinkedIn
      </a>
      <a href="https://twitter.com/intent/tweet?text=I+just+earned+a+certificate+in+<?= urlencode($cert['quiz_title']) ?>+from+Smart+AI+E-Learning!+Verify+it+here:&url=<?= urlencode($verify_url) ?>" target="_blank" class="share-btn share-twitter">
         <i class="fab fa-twitter"></i> Share on Twitter
      </a>
      <a href="https://wa.me/?text=I+earned+a+certificate+from+Smart+AI+E-Learning!+Verify+at:+<?= urlencode($verify_url) ?>" target="_blank" class="share-btn share-whatsapp">
         <i class="fab fa-whatsapp"></i> WhatsApp
      </a>
      <button class="share-btn share-copy" onclick="copyVerifyLink()">
         <i class="fas fa-link"></i> Copy Verify Link
      </button>
   </div>

   <!-- Action Buttons -->
   <div class="cert-actions">
      <a href="certificate_pdf.php?cert_id=<?= $cert_id ?>" class="btn-download" id="download-cert-btn">
         <i class="fas fa-file-pdf"></i> Download PDF Certificate
      </a>
      <button onclick="window.print()" class="btn-verify">
         <i class="fas fa-print"></i> Print
      </button>
      <a href="verify_certificate.php?code=<?= urlencode($cert['certificate_code']) ?>" class="btn-verify" target="_blank">
         <i class="fas fa-shield-alt"></i> Verify
      </a>
   </div>

   <!-- Certificate Visual Preview -->
   <div class="certificate-preview" id="certificate-preview">
      <div class="cert-inner">
         <!-- Watermark -->
         <div class="cert-watermark">CERTIFIED</div>

         <!-- Header -->
         <div class="cert-header">
            <div class="cert-logo">
               <i class="fas fa-graduation-cap"></i>
               Smart AI E-Learning
            </div>
            <div class="cert-subtitle">Certificate of Completion</div>
            <div class="cert-divider"></div>
         </div>

         <!-- Body -->
         <div class="cert-body">
            <div class="cert-presented">This certifies that</div>
            <div class="cert-name"><?= htmlspecialchars($cert['student_name']) ?></div>
            <div class="cert-description">
               has successfully completed the quiz and demonstrated proficiency in
            </div>
            <div class="cert-course"><?= htmlspecialchars($cert['quiz_title']) ?></div>
            <div class="cert-description" style="font-size:1.4rem; color:#888; margin-top:.5rem;">
               Course: <?= htmlspecialchars($cert['course_title'] ?? '') ?>
            </div>
         </div>

         <div class="cert-divider"></div>

         <!-- Footer -->
         <div class="cert-footer">
            <div class="left">
               <div class="date-text"><?= $issued_date ?></div>
               <div class="sig-line"></div>
               <div class="sig-text">Date of Completion</div>
            </div>

            <div class="center">
               <div class="qr-block">
                  <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Verify" loading="lazy">
                  <small>Scan to Verify</small>
               </div>
               <div class="cert-id"><?= htmlspecialchars($cert['certificate_code']) ?></div>
            </div>

            <div class="right">
               <div class="date-text" style="color:#8e44ad;">Smart AI Platform</div>
               <div class="sig-line"></div>
               <div class="sig-text">Authorized Signature</div>
            </div>
         </div>

      </div>
   </div>

   <div class="flex-btn" style="flex-wrap:wrap; gap:1rem;">
      <a href="quiz.php" class="inline-btn"><i class="fas fa-list"></i> All Quizzes</a>
      <a href="exam_history.php" class="inline-option-btn"><i class="fas fa-history"></i> Exam History</a>
   </div>

</section>

<?php include 'components/footer.php'; ?>
<script src="js/script.js"></script>
<script src="js/ajax.js"></script>
<script>
/* ── Copy helpers ── */
function copyCertId() {
   const code = document.getElementById('cert-code-display').textContent.trim();
   navigator.clipboard.writeText(code).then(() => {
      showToast('Certificate ID copied to clipboard!', 'success', 2500);
   }).catch(() => {
      showToast('Copy failed. Please copy manually.', 'error');
   });
}
function copyVerifyLink() {
   navigator.clipboard.writeText('<?= addslashes($verify_url) ?>').then(() => {
      showToast('Verification link copied!', 'success', 2500);
   }).catch(() => {
      showToast('Copy failed. Please copy manually.', 'error');
   });
}

/* ── Simple Confetti ── */
(function() {
   const canvas = document.getElementById('cert-confetti');
   const ctx    = canvas.getContext('2d');
   canvas.width  = window.innerWidth;
   canvas.height = window.innerHeight;

   const colors = ['#8e44ad','#f39c12','#27ae60','#3498db','#e74c3c','#f1c40f'];
   const pieces = Array.from({length: 120}, () => ({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height - canvas.height,
      w: Math.random() * 12 + 5,
      h: Math.random() * 8 + 4,
      color: colors[Math.floor(Math.random() * colors.length)],
      vx: (Math.random() - 0.5) * 2,
      vy: Math.random() * 3 + 2,
      angle: Math.random() * Math.PI * 2,
      spin: (Math.random() - 0.5) * 0.2
   }));

   let frame = 0;
   function draw() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      pieces.forEach(p => {
         ctx.save();
         ctx.translate(p.x, p.y);
         ctx.rotate(p.angle);
         ctx.fillStyle = p.color;
         ctx.globalAlpha = 0.85;
         ctx.fillRect(-p.w/2, -p.h/2, p.w, p.h);
         ctx.restore();
         p.x += p.vx; p.y += p.vy; p.angle += p.spin;
      });
      frame++;
      if (frame < 200) requestAnimationFrame(draw);
      else { ctx.clearRect(0,0,canvas.width,canvas.height); }
   }
   draw();
})();
</script>
</body>
</html>
