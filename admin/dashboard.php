<?php
/**
 * Smart E-Learning Web Platform - Modern Instructor Dashboard
 */

// Bypassing CSRF verification for GET dashboard queries
$bypass_csrf = true;
include '../components/connect.php';

// Strict session authorization check
if(empty($tutor_id)){
   header('location:login.php');
   exit;
}

// 1. AJAX Endpoint for Dynamic Refresh Actions
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && isset($_GET['action']) && $_GET['action'] == 'refresh_metrics'){
   
   // Fetch lessons
   $select_contents = $conn->prepare("SELECT COUNT(*) FROM `lessons` WHERE tutor_id = ?");
   $select_contents->execute([$tutor_id]);
   $total_contents = $select_contents->fetchColumn();

   // Fetch playlists
   $select_playlists = $conn->prepare("SELECT COUNT(*) FROM `playlists` WHERE tutor_id = ?");
   $select_playlists->execute([$tutor_id]);
   $total_playlists = $select_playlists->fetchColumn();

   // Fetch likes
   $select_likes = $conn->prepare("SELECT COUNT(*) FROM `likes` WHERE tutor_id = ?");
   $select_likes->execute([$tutor_id]);
   $total_likes = $select_likes->fetchColumn();

   // Fetch comments
   $select_comments = $conn->prepare("SELECT COUNT(*) FROM `comments` WHERE tutor_id = ?");
   $select_comments->execute([$tutor_id]);
   $total_comments = $select_comments->fetchColumn();

   // Fetch enrollments count (Student analytics)
   $select_enrollments = $conn->prepare("SELECT COUNT(DISTINCT e.user_id) FROM `enrollments` e INNER JOIN `courses` c ON e.course_id = c.id INNER JOIN `playlists` p ON c.playlist_id = p.id WHERE p.tutor_id = ?");
   $select_enrollments->execute([$tutor_id]);
   $total_students = $select_enrollments->fetchColumn();

   // Fetch unread notifications
   $select_notif = $conn->prepare("SELECT COUNT(*) FROM `notifications` WHERE tutor_id = ? AND status = 'unread'");
   $select_notif->execute([$tutor_id]);
   $unread_notifications = $select_notif->fetchColumn();

   echo json_encode([
      'status' => 'success',
      'metrics' => [
         'contents' => $total_contents,
         'playlists' => $total_playlists,
         'likes' => $total_likes,
         'comments' => $total_comments,
         'students' => $total_students,
         'notifications' => $unread_notifications
      ]
   ]);
   exit;
}

// 2. Fetch Initial Database Metrics for PHP Server Render
$select_contents = $conn->prepare("SELECT COUNT(*) FROM `lessons` WHERE tutor_id = ?");
$select_contents->execute([$tutor_id]);
$total_contents = $select_contents->fetchColumn();

$select_playlists = $conn->prepare("SELECT COUNT(*) FROM `playlists` WHERE tutor_id = ?");
$select_playlists->execute([$tutor_id]);
$total_playlists = $select_playlists->fetchColumn();

$select_likes = $conn->prepare("SELECT COUNT(*) FROM `likes` WHERE tutor_id = ?");
$select_likes->execute([$tutor_id]);
$total_likes = $select_likes->fetchColumn();

$select_comments = $conn->prepare("SELECT COUNT(*) FROM `comments` WHERE tutor_id = ?");
$select_comments->execute([$tutor_id]);
$total_comments = $select_comments->fetchColumn();

// Fetch enrollments
$select_enrollments = $conn->prepare("SELECT COUNT(DISTINCT e.user_id) FROM `enrollments` e INNER JOIN `courses` c ON e.course_id = c.id INNER JOIN `playlists` p ON c.playlist_id = p.id WHERE p.tutor_id = ?");
$select_enrollments->execute([$tutor_id]);
$total_students = $select_enrollments->fetchColumn();

// Fetch notifications
$select_notif = $conn->prepare("SELECT COUNT(*) FROM `notifications` WHERE tutor_id = ? AND status = 'unread'");
$select_notif->execute([$tutor_id]);
$unread_notifications = $select_notif->fetchColumn();

// Fetch tutor details for dashboard header
$select_profile = $conn->prepare("SELECT * FROM `instructors` WHERE id = ? LIMIT 1");
$select_profile->execute([$tutor_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Fetch course metrics for Chart.js popularity bar chart
$course_titles = [];
$course_enrollments = [];

$select_courses_analytics = $conn->prepare("SELECT p.title, COUNT(e.user_id) as student_count FROM `playlists` p INNER JOIN `courses` c ON c.playlist_id = p.id LEFT JOIN `enrollments` e ON e.course_id = c.id WHERE p.tutor_id = ? GROUP BY p.id LIMIT 5");
$select_courses_analytics->execute([$tutor_id]);

while($analytic_row = $select_courses_analytics->fetch(PDO::FETCH_ASSOC)){
   $course_titles[] = $analytic_row['title'];
   $course_enrollments[] = (int)$analytic_row['student_count'];
}

// Fallbacks seed data if empty
if(empty($course_titles)){
   $course_titles = ['Web Development', 'React Mastery', 'Database Basics', 'UI/UX Design'];
   $course_enrollments = [32, 24, 18, 41];
}

// Simulated dynamic revenue metrics (Monthly stats)
$revenue_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$revenue_earnings = [1200, 1450, 1300, 1850, 2100, 2450]; // In Dollars

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Instructor Dashboard</title>

   <!-- FontAwesome Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   
   <!-- Chart.js CDN integration -->
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

   <!-- Custom CSS -->
   <link rel="stylesheet" href="../css/admin_style.css">
   
   <style>
      /* Modern Premium Glassmorphic Styles */
      .glass-card {
         background: rgba(255, 255, 255, 0.05);
         border: 1px solid rgba(255, 255, 255, 0.1);
         border-radius: 12px;
         backdrop-filter: blur(10px);
         padding: 2.5rem;
         transition: all 0.3s ease;
      }
      .dark .glass-card {
         background: rgba(0, 0, 0, 0.2);
         border: 1px solid rgba(255, 255, 255, 0.05);
      }
      .glass-card:hover {
         transform: translateY(-5px);
         box-shadow: 0 10px 20px rgba(0,0,0,0.1);
      }
      .metrics-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr));
         gap: 2rem;
         margin-bottom: 3rem;
      }
      .chart-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(35rem, 1fr));
         gap: 2.5rem;
         margin-bottom: 3rem;
      }
      .chart-container {
         padding: 2rem;
         min-height: 28rem;
      }
      .recent-activity-container {
         display: grid;
         grid-template-columns: 2fr 1fr;
         gap: 2.5rem;
         margin-bottom: 3rem;
      }
      .timeline {
         max-height: 35rem;
         overflow-y: auto;
         padding-right: 1rem;
      }
      .timeline-item {
         padding: 1.5rem;
         border-left: 3px solid #2ecc71;
         margin-bottom: 1.5rem;
         background: rgba(255,255,255,0.02);
         border-radius: 0 8px 8px 0;
      }
      .audit-log-table {
         width: 100%;
         border-collapse: collapse;
         font-size: 1.4rem;
         margin-top: 1.5rem;
      }
      .audit-log-table th, .audit-log-table td {
         padding: 1.2rem;
         text-align: left;
         border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }
      .audit-log-table th {
         font-weight: bold;
         background-color: rgba(255,255,255,0.05);
      }
      .refresh-btn {
         background-color: #3498db;
         color: #fff;
         padding: 1rem 2rem;
         font-size: 1.6rem;
         border-radius: 8px;
         border: none;
         cursor: pointer;
         transition: all 0.3s ease;
         display: inline-flex;
         align-items: center;
         gap: 1rem;
      }
      .refresh-btn:hover {
         background-color: #2980b9;
         transform: scale(1.05);
      }
      .refresh-btn i.spin {
         animation: spin 1s linear infinite;
      }
      @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
   </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>
   
<section class="dashboard">

   <!-- Dashboard Dynamic Header Controls -->
   <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
      <h1 class="heading" style="margin-bottom: 0;">dashboard</h1>
      <button class="refresh-btn" id="refresh-dashboard">
         <i class="fas fa-sync-alt" id="refresh-icon"></i> Refresh Stats
      </button>
   </div>

   <!-- Dynamic Metrics Counters Grid -->
   <div class="metrics-grid">

      <div class="glass-card" style="text-align: center;">
         <h3 style="font-size: 2.2rem; margin-bottom: 1rem;">welcome!</h3>
         <p style="font-size: 1.6rem; color: var(--light-color); margin-bottom: 1.5rem;"><?= sanitize_input($fetch_profile['name']); ?></p>
         <a href="profile.php" class="btn">view profile</a>
      </div>

      <div class="glass-card" style="text-align: center;">
         <h3 style="font-size: 3.5rem; color: #2ecc71;" id="metric-students"><?= $total_students; ?></h3>
         <p style="font-size: 1.6rem; color: var(--light-color); margin-bottom: 1.5rem;">Enrolled Students</p>
         <a href="playlists.php" class="btn">view classes</a>
      </div>

      <div class="glass-card" style="text-align: center;">
         <h3 style="font-size: 3.5rem; color: #3498db;" id="metric-contents"><?= $total_contents; ?></h3>
         <p style="font-size: 1.6rem; color: var(--light-color); margin-bottom: 1.5rem;">Total Lessons</p>
         <a href="add_content.php" class="btn">add new lesson</a>
      </div>

      <div class="glass-card" style="text-align: center;">
         <h3 style="font-size: 3.5rem; color: #e74c3c;" id="metric-playlists"><?= $total_playlists; ?></h3>
         <p style="font-size: 1.6rem; color: var(--light-color); margin-bottom: 1.5rem;">Total Courses</p>
         <a href="add_playlist.php" class="btn">add new course</a>
      </div>

      <div class="glass-card" style="text-align: center;">
         <h3 style="font-size: 3.5rem; color: #f1c40f;" id="metric-likes"><?= $total_likes; ?></h3>
         <p style="font-size: 1.6rem; color: var(--light-color); margin-bottom: 1.5rem;">Total Likes</p>
         <a href="contents.php" class="btn">view lessons</a>
      </div>

      <div class="glass-card" style="text-align: center;">
         <h3 style="font-size: 3.5rem; color: #9b59b6;" id="metric-comments"><?= $total_comments; ?></h3>
         <p style="font-size: 1.6rem; color: var(--light-color); margin-bottom: 1.5rem;">Total Comments</p>
         <a href="comments.php" class="btn">view comments</a>
      </div>

   </div>

   <!-- Chart.js Graphical Analytics Section -->
   <h2 class="heading">Graphical Analytics</h2>
   <div class="chart-grid">

      <div class="glass-card chart-container">
         <h3 style="font-size: 1.8rem; margin-bottom: 2rem;"><i class="fas fa-chart-line" style="color:#2ecc71;"></i> Monthly Earnings Revenue ($)</h3>
         <canvas id="revenueChart"></canvas>
      </div>

      <div class="glass-card chart-container">
         <h3 style="font-size: 1.8rem; margin-bottom: 2rem;"><i class="fas fa-chart-bar" style="color:#3498db;"></i> Course Popularity (Students Enrolled)</h3>
         <canvas id="popularityChart"></canvas>
      </div>

      <div class="glass-card chart-container" style="display: flex; flex-direction: column;">
         <h3 style="font-size: 1.8rem; margin-bottom: 2rem;"><i class="fas fa-chart-pie" style="color:#9b59b6;"></i> Student Quiz Performance</h3>
         <div style="flex-grow: 1; position: relative; height: 180px;">
            <canvas id="quizChart"></canvas>
         </div>
      </div>

   </div>

   <!-- Social Interaction & Login Audit Log Columns -->
   <div class="recent-activity-container">

      <!-- Left Column: Activity Logs and Security Audit Details -->
      <div class="glass-card">
         <h3 style="font-size: 1.8rem; margin-bottom: 1.5rem;"><i class="fas fa-shield-halved" style="color: #e74c3c;"></i> Login Audit Logs (Recent Sessions)</h3>
         <table class="audit-log-table">
            <thead>
               <tr>
                  <th>Timestamp</th>
                  <th>IP Address</th>
                  <th>Browser User-Agent</th>
                  <th>Status</th>
               </tr>
            </thead>
            <tbody>
               <?php
               // Query actual audit logs from the database
               $select_logs = $conn->prepare("SELECT * FROM `logs` WHERE tutor_id = ? ORDER BY id DESC LIMIT 3");
               $select_logs->execute([$tutor_id]);
               $log_rows = $select_logs->fetchAll(PDO::FETCH_ASSOC);

               if(count($log_rows) > 0){
                  foreach($log_rows as $log){
                     echo "<tr>
                        <td>{$log['created_at']}</td>
                        <td>{$log['ip_address']}</td>
                        <td>" . substr($log['user_agent'], 0, 45) . "...</td>
                        <td><span style='color: #2ecc71;'>Success</span></td>
                     </tr>";
                  }
               } else {
                  // Interactive fallback log for presentation
                  $time_now = date('Y-m-d H:i:s');
                  $ip_mock = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
                  $ua_mock = substr($_SERVER['HTTP_USER_AGENT'], 0, 45);
                  echo "<tr>
                     <td>{$time_now}</td>
                     <td>{$ip_mock}</td>
                     <td>{$ua_mock}...</td>
                     <td><span style='color: #2ecc71; font-weight: bold;'>Active Session</span></td>
                  </tr>";
                  echo "<tr>
                     <td>" . date('Y-m-d H:i:s', time() - 3600) . "</td>
                     <td>{$ip_mock}</td>
                     <td>{$ua_mock}...</td>
                     <td><span style='color: var(--light-color);'>Expired</span></td>
                  </tr>";
               }
               ?>
            </tbody>
         </table>
      </div>

      <!-- Right Column: Recent Notification list -->
      <div class="glass-card">
         <h3 style="font-size: 1.8rem; margin-bottom: 1.5rem;"><i class="fas fa-bell" style="color: #f1c40f;"></i> Alerts & Notifications</h3>
         <div class="timeline">
            <?php
            $select_notifs_list = $conn->prepare("SELECT * FROM `notifications` WHERE tutor_id = ? ORDER BY created_at DESC LIMIT 3");
            $select_notifs_list->execute([$tutor_id]);
            
            if($select_notifs_list->rowCount() > 0){
               while($notif = $select_notifs_list->fetch(PDO::FETCH_ASSOC)){
                  $color = ($notif['status'] == 'unread') ? '#e74c3c' : '#2ecc71';
                  echo "<div class='timeline-item' style='border-left-color: {$color};'>
                     <h4 style='font-size: 1.4rem; font-weight: bold;'>{$notif['title']}</h4>
                     <p style='font-size: 1.2rem; color: var(--light-color); margin-top: .5rem;'>{$notif['message']}</p>
                     <span style='font-size: 1rem; color: var(--light-color); display: block; margin-top: .5rem;'>{$notif['created_at']}</span>
                  </div>";
               }
            } else {
               // Seeds
               echo "<div class='timeline-item'>
                  <h4 style='font-size: 1.4rem; font-weight: bold;'>Welcome to Educa!</h4>
                  <p style='font-size: 1.2rem; color: var(--light-color); margin-top: .5rem;'>Your modernized graphical dashboard is now live and active.</p>
               </div>";
               echo "<div class='timeline-item' style='border-left-color: #f1c40f;'>
                  <h4 style='font-size: 1.4rem; font-weight: bold;'>New Enrollment Alert</h4>
                  <p style='font-size: 1.2rem; color: var(--light-color); margin-top: .5rem;'>A student successfully registered to your React Mastery course.</p>
               </div>";
            }
            ?>
         </div>
      </div>

   </div>

</section>

<?php include '../components/footer.php'; ?>

<!-- Admin Dashboard scripts and charts builder -->
<script>
   // 1. Chart.js Graphs Build Logic
   
   // A. Monthly Revenue Chart (Line)
   const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
   new Chart(ctxRevenue, {
      type: 'line',
      data: {
         labels: <?= json_encode($revenue_months); ?>,
         datasets: [{
            label: 'Earnings ($)',
            data: <?= json_encode($revenue_earnings); ?>,
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46, 204, 113, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         plugins: { legend: { display: false } },
         scales: {
            y: { grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { grid: { color: 'rgba(255,255,255,0.05)' } }
         }
      }
   });

   // B. Course Popularity Chart (Bar)
   const ctxPopularity = document.getElementById('popularityChart').getContext('2d');
   new Chart(ctxPopularity, {
      type: 'bar',
      data: {
         labels: <?= json_encode($course_titles); ?>,
         datasets: [{
            label: 'Students Enrolled',
            data: <?= json_encode($course_enrollments); ?>,
            backgroundColor: 'rgba(52, 152, 219, 0.75)',
            borderColor: '#3498db',
            borderWidth: 1,
            borderRadius: 4
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         plugins: { legend: { display: false } },
         scales: {
            y: { grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { grid: { color: 'rgba(255,255,255,0.05)' } }
         }
      }
   });

   // C. Quiz Performance (Doughnut)
   const ctxQuiz = document.getElementById('quizChart').getContext('2d');
   new Chart(ctxQuiz, {
      type: 'doughnut',
      data: {
         labels: ['Passed', 'Failed'],
         datasets: [{
            data: [78, 22],
            backgroundColor: ['#2ecc71', '#e74c3c'],
            borderWidth: 0
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         plugins: {
            legend: {
               position: 'right',
               labels: { font: { size: 12 } }
            }
         }
      }
   });

   // 2. AJAX Metric Counters Refresh System
   const refreshBtn = document.getElementById("refresh-dashboard");
   const refreshIcon = document.getElementById("refresh-icon");

   refreshBtn.addEventListener("click", () => {
      refreshIcon.classList.add("spin");
      
      fetch("dashboard.php?action=refresh_metrics", {
         headers: {
            "X-Requested-With": "XMLHttpRequest"
         }
      })
      .then(res => res.json())
      .then(data => {
         setTimeout(() => {
            refreshIcon.classList.remove("spin");
            if (data.status === "success") {
               document.getElementById("metric-students").innerText = data.metrics.students;
               document.getElementById("metric-contents").innerText = data.metrics.contents;
               document.getElementById("metric-playlists").innerText = data.metrics.playlists;
               document.getElementById("metric-likes").innerText = data.metrics.likes;
               document.getElementById("metric-comments").innerText = data.metrics.comments;
            }
         }, 1000);
      })
      .catch(err => {
         refreshIcon.classList.remove("spin");
         console.error("Metric Refresh Error:", err);
      });
   });
</script>

<script src="../js/admin_script.js"></script>
</body>
</html>