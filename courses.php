<?php
/**
 * Part 18 — Search & Filter System
 * Courses page with category, level and price filters + AJAX load-more
 */

include 'components/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

// ── AJAX filter endpoint ──────────────────────────────────────────────
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
    isset($_GET['action']) && $_GET['action'] === 'filter'
) {
    $offset   = max(0, (int)($_GET['offset']   ?? 0));
    $limit    = min(12, (int)($_GET['limit']    ?? 6));
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $level    = isset($_GET['level'])    ? trim($_GET['level'])    : '';
    $price    = isset($_GET['price'])    ? trim($_GET['price'])    : '';
    $search   = isset($_GET['q'])        ? trim($_GET['q'])        : '';

    $where  = ['p.status = ?'];
    $params = ['active'];

    if ($category > 0) {
        $where[]  = 'c.category_id = ?';
        $params[] = $category;
    }
    if (in_array($level, ['beginner', 'intermediate', 'advanced'])) {
        $where[]  = 'c.level = ?';
        $params[] = $level;
    }
    if ($price === 'free') {
        $where[]  = '(c.price IS NULL OR c.price = 0)';
    } elseif ($price === 'paid') {
        $where[]  = 'c.price > 0';
    }
    if ($search !== '') {
        $where[]  = '(p.title LIKE ? OR p.description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $join_course = ($category > 0 || $level !== '' || $price !== '')
        ? 'JOIN courses c ON c.playlist_id = p.id'
        : 'LEFT JOIN courses c ON c.playlist_id = p.id';

    $where_sql = implode(' AND ', $where);

    // Count
    $count_stmt = $conn->prepare("SELECT COUNT(DISTINCT p.id) FROM playlists p JOIN instructors i ON i.id = p.tutor_id $join_course WHERE $where_sql");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();

    // Rows
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id, p.title, p.thumb, p.tutor_id,
               i.name AS tutor_name, i.image AS tutor_image,
               c.level, c.price
        FROM   playlists   p
        JOIN   instructors i ON i.id = p.tutor_id
        $join_course
        WHERE  $where_sql
        ORDER BY p.id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    foreach ($rows as $course) {
        $thumb  = $course['thumb']       ? 'uploaded_files/' . htmlspecialchars($course['thumb'])       : 'images/default.png';
        $avatar = $course['tutor_image'] ? 'uploaded_files/' . htmlspecialchars($course['tutor_image']) : 'images/default.png';
        $badge  = '';
        if ($course['level']) {
            $badge = '<span class="level-badge level-' . htmlspecialchars($course['level']) . '">' . ucfirst(htmlspecialchars($course['level'])) . '</span>';
        }
        $price_tag = '';
        if ((float)($course['price'] ?? 0) > 0) {
            $price_tag = '<span class="price-tag">$' . number_format((float)$course['price'], 2) . '</span>';
        } else {
            $price_tag = '<span class="price-tag free">Free</span>';
        }
        $html .= '
        <div class="box reveal-card">
           <div class="tutor">
              <img src="' . $avatar . '" alt="' . htmlspecialchars($course['tutor_name']) . '" loading="lazy" onerror="this.src=\'images/default.png\'">
              <div>
                 <h3>' . htmlspecialchars($course['tutor_name']) . '</h3>
              </div>
           </div>
           ' . $badge . '
           <img src="' . $thumb . '" class="thumb" alt="' . htmlspecialchars($course['title']) . '" loading="lazy" onerror="this.src=\'images/default.png\'">
           <h3 class="title">' . htmlspecialchars($course['title']) . '</h3>
           ' . $price_tag . '
           <a href="playlist.php?get_id=' . $course['id'] . '" class="inline-btn"><i class="fas fa-play"></i> View Playlist</a>
        </div>';
    }

    echo json_encode([
        'status' => 'success',
        'html'   => $html,
        'total'  => $total,
        'shown'  => $offset + count($rows),
    ]);
    exit;
}

// ── Page render ───────────────────────────────────────────────────────

// Categories for filter
$cat_stmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Initial 6 courses
$select_courses = $conn->prepare("
    SELECT DISTINCT p.id, p.title, p.thumb, p.tutor_id,
           i.name AS tutor_name, i.image AS tutor_image,
           c.level, c.price
    FROM   playlists   p
    JOIN   instructors i ON i.id          = p.tutor_id
    LEFT JOIN courses  c ON c.playlist_id = p.id
    WHERE  p.status = 'active'
    ORDER BY p.id DESC
    LIMIT  6
");
$select_courses->execute();

$total_stmt = $conn->prepare("SELECT COUNT(*) FROM playlists WHERE status = 'active'");
$total_stmt->execute();
$total_courses = (int)$total_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>All Courses | Smart AI E-Learning</title>
   <meta name="description" content="Browse all available courses on the Smart AI E-Learning platform. Filter by category, level, and price.">
   <meta name="csrf_token" content="<?= csrf_token_generate() ?>">
   <!-- Open Graph -->
   <meta property="og:title" content="All Courses | Smart AI E-Learning">
   <meta property="og:description" content="Explore hundreds of courses taught by expert instructors.">
   <meta property="og:type" content="website">
   <!-- Preconnect -->
   <link rel="preconnect" href="https://cdnjs.cloudflare.com">
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <style>
      /* Filter sidebar */
      .courses-layout {
         display: flex;
         gap: 3rem;
         align-items: flex-start;
      }
      .filter-sidebar {
         width: 26rem;
         flex-shrink: 0;
         background: var(--white);
         border: var(--border);
         border-radius: 1rem;
         padding: 2rem;
         position: sticky;
         top: 9rem;
      }
      .filter-sidebar h3 {
         font-size: 1.8rem;
         margin-bottom: 1.5rem;
         padding-bottom: .8rem;
         border-bottom: var(--border);
      }
      .filter-group { margin-bottom: 2rem; }
      .filter-group label {
         font-size: 1.5rem;
         font-weight: 600;
         display: block;
         margin-bottom: .8rem;
         color: var(--light-color);
         text-transform: uppercase;
         letter-spacing: .05em;
      }
      .filter-group select,
      .filter-group input[type="text"] {
         width: 100%;
         padding: 1rem 1.2rem;
         font-size: 1.4rem;
         background: var(--light-bg);
         border: var(--border);
         border-radius: .6rem;
         color: var(--black);
      }
      .filter-radio-group { display: flex; flex-direction: column; gap: .6rem; }
      .filter-radio-group label {
         display: flex; align-items: center; gap: .8rem;
         font-size: 1.4rem; font-weight: 400;
         color: var(--black); text-transform: none; letter-spacing: 0;
         cursor: pointer;
      }
      .filter-radio-group input[type="radio"] { width: 1.6rem; height: 1.6rem; cursor: pointer; }
      .apply-filter-btn {
         width: 100%; padding: 1.2rem;
         background: var(--main-color); color: #fff;
         font-size: 1.5rem; border-radius: .6rem;
         border: none; cursor: pointer;
         transition: background .2s, transform .2s;
      }
      .apply-filter-btn:hover { background: #7d3c98; transform: translateY(-1px); }
      .clear-filter-btn {
         width: 100%; padding: .8rem;
         background: none; color: var(--light-color);
         font-size: 1.3rem; border-radius: .6rem;
         border: 1px solid var(--border); cursor: pointer;
         margin-top: .8rem; transition: color .2s;
      }
      .clear-filter-btn:hover { color: var(--red); border-color: var(--red); }

      .courses-main { flex: 1; min-width: 0; }

      /* Level & Price badges */
      .level-badge {
         display: inline-block;
         font-size: 1.1rem;
         padding: .3rem .8rem;
         border-radius: 2rem;
         font-weight: 600;
         text-transform: capitalize;
         margin-bottom: .6rem;
      }
      .level-beginner     { background: #d5f5e3; color: #1e8449; }
      .level-intermediate { background: #fef9e7; color: #b7950b; }
      .level-advanced     { background: #fadbd8; color: #922b21; }
      .dark .level-beginner     { background: #1e3a2a; color: #58d68d; }
      .dark .level-intermediate { background: #3a2e0a; color: #f1c40f; }
      .dark .level-advanced     { background: #3a0f0d; color: #f1948a; }

      .price-tag {
         display: inline-block;
         font-size: 1.3rem;
         font-weight: 700;
         color: var(--main-color);
         margin-bottom: .5rem;
      }
      .price-tag.free { color: #27ae60; }

      /* Result count */
      .result-count {
         font-size: 1.4rem;
         color: var(--light-color);
         margin-bottom: 1.5rem;
      }

      @media (max-width: 768px) {
         .courses-layout { flex-direction: column; }
         .filter-sidebar { width: 100%; position: static; }
      }
   </style>
</head>
<body>

<!-- Page Loader -->
<div id="page-loader" aria-hidden="true">
   <div class="loader-ring"></div>
   <span class="loader-text">Loading courses...</span>
</div>

<?php include 'components/user_header.php'; ?>

<section class="courses fade-in-section">
   <h1 class="heading">all courses</h1>

   <div class="courses-layout">

      <!-- ── Filter Sidebar ────────────────────────────────────── -->
      <aside class="filter-sidebar" role="search" aria-label="Course filters">
         <h3><i class="fas fa-filter" style="color:var(--main-color);"></i> Filter Courses</h3>

         <div class="filter-group">
            <label for="filter-search">Search</label>
            <input type="text" id="filter-search" placeholder="Type course name..." aria-label="Search courses" maxlength="100">
         </div>

         <div class="filter-group">
            <label for="filter-category">Category</label>
            <select id="filter-category" aria-label="Filter by category">
               <option value="0">All Categories</option>
               <?php foreach ($categories as $cat): ?>
               <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
               <?php endforeach; ?>
            </select>
         </div>

         <div class="filter-group">
            <label>Level</label>
            <div class="filter-radio-group" role="radiogroup" aria-label="Filter by level">
               <label><input type="radio" name="filter-level" value="">All Levels</label>
               <label><input type="radio" name="filter-level" value="beginner">Beginner</label>
               <label><input type="radio" name="filter-level" value="intermediate">Intermediate</label>
               <label><input type="radio" name="filter-level" value="advanced">Advanced</label>
            </div>
         </div>

         <div class="filter-group">
            <label>Price</label>
            <div class="filter-radio-group" role="radiogroup" aria-label="Filter by price">
               <label><input type="radio" name="filter-price" value="">All</label>
               <label><input type="radio" name="filter-price" value="free">Free</label>
               <label><input type="radio" name="filter-price" value="paid">Paid</label>
            </div>
         </div>

         <button class="apply-filter-btn" id="apply-filter-btn" aria-label="Apply selected filters">
            <i class="fas fa-search"></i> Apply Filters
         </button>
         <button class="clear-filter-btn" id="clear-filter-btn" aria-label="Clear all filters">
            Clear Filters
         </button>
      </aside>

      <!-- ── Courses Main ───────────────────────────────────────── -->
      <div class="courses-main">
         <p class="result-count" id="result-count">
            Showing <strong id="shown-count">
               <?= min(6, $total_courses); ?>
            </strong> of <strong id="total-count"><?= $total_courses; ?></strong> courses
         </p>

         <div class="box-container" id="courses-box-container">
            <?php
            if ($select_courses->rowCount() > 0):
               while ($c = $select_courses->fetch(PDO::FETCH_ASSOC)):
                  $thumb  = $c['thumb']       ? 'uploaded_files/' . htmlspecialchars($c['thumb'])       : 'images/default.png';
                  $avatar = $c['tutor_image'] ? 'uploaded_files/' . htmlspecialchars($c['tutor_image']) : 'images/default.png';
                  $badge  = $c['level']
                     ? '<span class="level-badge level-' . htmlspecialchars($c['level']) . '">' . ucfirst(htmlspecialchars($c['level'])) . '</span>'
                     : '';
                  $ptag   = ((float)($c['price'] ?? 0) > 0)
                     ? '<span class="price-tag">$' . number_format((float)$c['price'], 2) . '</span>'
                     : '<span class="price-tag free">Free</span>';
            ?>
            <div class="box reveal-card">
               <div class="tutor">
                  <img src="<?= $avatar; ?>" alt="<?= htmlspecialchars($c['tutor_name']); ?>" loading="lazy" onerror="this.src='images/default.png'">
                  <div><h3><?= htmlspecialchars($c['tutor_name']); ?></h3></div>
               </div>
               <?= $badge; ?>
               <img src="<?= $thumb; ?>" class="thumb" alt="<?= htmlspecialchars($c['title']); ?>" loading="lazy" onerror="this.src='images/default.png'">
               <h3 class="title"><?= htmlspecialchars($c['title']); ?></h3>
               <?= $ptag; ?>
               <a href="playlist.php?get_id=<?= $c['id']; ?>" class="inline-btn"><i class="fas fa-play"></i> View Playlist</a>
            </div>
            <?php endwhile;
            else: ?>
            <p class="empty">No courses found!</p>
            <?php endif; ?>
         </div>

         <!-- Load More / Filter load-more -->
         <div class="load-more-wrapper" id="load-more-wrapper" <?= $total_courses <= 6 ? 'style="display:none"' : ''; ?>>
            <button id="load-more-btn" data-offset="6" data-limit="6" aria-label="Load more courses">
               <i class="fas fa-plus"></i> Load More Courses
            </button>
         </div>
      </div>

   </div>
</section>

<?php include 'components/footer.php'; ?>

<script src="js/script.js" defer></script>
<script src="js/ajax.js" defer></script>
<script>
/* Part 16 — Page loader hide */
window.addEventListener('load', function () {
   const loader = document.getElementById('page-loader');
   if (loader) loader.classList.add('hidden');
});

/* Part 19 — Scroll-reveal */
function revealOnScroll() {
   document.querySelectorAll('.reveal-card:not(.visible)').forEach(el => {
      const rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight - 50) el.classList.add('visible');
   });
}
window.addEventListener('scroll', revealOnScroll, { passive: true });
revealOnScroll();

/* ── Filter & Load-More Logic (Part 18) ─────────────────────────── */
const container    = document.getElementById('courses-box-container');
const shownCount   = document.getElementById('shown-count');
const totalCount   = document.getElementById('total-count');
const loadMoreWrap = document.getElementById('load-more-wrapper');
const loadMoreBtn  = document.getElementById('load-more-btn');

let currentOffset = 6;
let currentTotal  = <?= $total_courses; ?>;
let activeFilters = { q: '', category: '0', level: '', price: '' };

function buildQuery(extra = {}) {
   const p = Object.assign({}, activeFilters, extra);
   return new URLSearchParams({
      action:   'filter',
      q:        p.q,
      category: p.category,
      level:    p.level,
      price:    p.price,
      limit:    extra.limit ?? 6,
      offset:   extra.offset ?? 0
   }).toString();
}

function renderCourses(html, total, shown, append = false) {
   if (!append) container.innerHTML = html || '<p class="empty">No courses found!</p>';
   else         container.insertAdjacentHTML('beforeend', html);

   totalCount.textContent = total;
   shownCount.textContent = shown;
   loadMoreWrap.style.display = shown < total ? '' : 'none';
   loadMoreBtn.dataset.offset = shown;
   revealOnScroll();
}

document.getElementById('apply-filter-btn').addEventListener('click', function () {
   activeFilters.q        = document.getElementById('filter-search').value.trim();
   activeFilters.category = document.getElementById('filter-category').value;
   activeFilters.level    = (document.querySelector('input[name="filter-level"]:checked') || {}).value || '';
   activeFilters.price    = (document.querySelector('input[name="filter-price"]:checked') || {}).value || '';
   currentOffset = 0;

   this.disabled = true;
   this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';

   fetch('courses.php?' + buildQuery({ offset: 0 }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
   })
   .then(r => r.json())
   .then(data => {
      if (data.status === 'success') {
         currentTotal  = data.total;
         currentOffset = data.shown;
         renderCourses(data.html, data.total, data.shown);
      }
   })
   .finally(() => {
      this.disabled = false;
      this.innerHTML = '<i class="fas fa-search"></i> Apply Filters';
   });
});

document.getElementById('clear-filter-btn').addEventListener('click', function () {
   document.getElementById('filter-search').value = '';
   document.getElementById('filter-category').value = '0';
   document.querySelectorAll('input[name="filter-level"]')[0].checked = true;
   document.querySelectorAll('input[name="filter-price"]')[0].checked = true;
   activeFilters = { q: '', category: '0', level: '', price: '' };
   document.getElementById('apply-filter-btn').click();
});

// Live search debounce
let debounceTimer;
document.getElementById('filter-search').addEventListener('input', function () {
   clearTimeout(debounceTimer);
   debounceTimer = setTimeout(() => {
      document.getElementById('apply-filter-btn').click();
   }, 400);
});

// Load More
loadMoreBtn.addEventListener('click', function () {
   const offset = parseInt(this.dataset.offset);
   this.disabled = true;
   this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

   fetch('courses.php?' + buildQuery({ offset }), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
   })
   .then(r => r.json())
   .then(data => {
      if (data.status === 'success') {
         currentOffset = data.shown;
         renderCourses(data.html, data.total, data.shown, true);
      }
   })
   .finally(() => {
      this.disabled = false;
      this.innerHTML = '<i class="fas fa-plus"></i> Load More Courses';
   });
});
</script>
</body>
</html>
