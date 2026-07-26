<?php
$pageTitle = 'Student Profile';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/icons.php';
startSession();
requireStudent();

$pdo = getDB();
$studentId = $_SESSION['student_id'];

$stmt = $pdo->prepare("
    SELECT s.*, b.name AS batch_name, c.name AS course_name
    FROM students s
    JOIN batches b ON b.id = s.batch_id
    JOIN courses c ON c.id = b.course_id
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

$firstName = explode(' ', $student['name'])[0];
$today = new DateTime();
$formattedDate = $today->format('F j, Y');
$dayName = $today->format('l');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> | Test Platform</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/student.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,300,0,0">
    <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body>
<?= iconSprite() ?>

<div class="dashboard-layout" id="app">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <aside class="dashboard-sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-logo" style="text-decoration:none;">
            <div class="sidebar-logo-icon">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2.5a.5.5 0 0 1 .28-.46l7-3.5a.5.5 0 0 1 .44 0l7 3.5a.5.5 0 0 1 .28.46v12a.5.5 0 0 1-1 0V3.2l-6.5 3.25V15.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-13z"/></svg>
            </div>
            <span class="sidebar-logo-text">Test Platform</span>
        </a>
        <nav class="sidebar-nav">
            <div class="sidebar-nav-group">
                <a href="dashboard.php" class="sidebar-nav-item">
                    <?= icon('dashboard', 20) ?>
                    <span>Dashboard</span>
                </a>
                <a href="dashboard.php" class="sidebar-nav-item">
                    <?= icon('test', 20) ?>
                    <span>My Tests</span>
                </a>
                <a href="results.php" class="sidebar-nav-item">
                    <?= icon('chart', 20) ?>
                    <span>Results</span>
                </a>
                <a href="analytics.php" class="sidebar-nav-item">
                    <?= icon('graph', 20) ?>
                    <span>Analytics</span>
                </a>
                <a href="profile.php" class="sidebar-nav-item active">
                    <?= icon('student', 20) ?>
                    <span>Profile</span>
                </a>
            </div>
            <div class="sidebar-nav-group">
                <div class="sidebar-nav-label">Appearance</div>
                <button class="sidebar-nav-item theme-toggle" onclick="toggleTheme()" id="themeToggle">
                    <span class="material-symbols-outlined theme-icon-light">dark_mode</span>
                    <span class="material-symbols-outlined theme-icon-dark">light_mode</span>
                    <span id="themeLabel">Dark Mode</span>
                </button>
            </div>
            <div class="sidebar-nav-group" style="margin-top:auto;">
                <a href="<?= BASE_URL ?>/logout.php" class="sidebar-nav-item">
                    <?= icon('logout', 20) ?>
                    <span>Sign Out</span>
                </a>
            </div>
        </nav>
        <div class="sidebar-profile">
            <div class="sidebar-profile-avatar">
                <?= strtoupper($firstName[0]) ?>
                <span class="online-dot"></span>
            </div>
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name"><?= h($firstName) ?></div>
                <div class="sidebar-profile-role">Student</div>
            </div>
        </div>
    </aside>

    <div class="dashboard-main">
        <header class="dashboard-topnav">
            <div class="topnav-left">
                <button class="topnav-hamburger" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                    <?= icon('menu', 20) ?>
                </button>
                <div class="topnav-brand">Student Portal</div>
            </div>
            <div class="topnav-right">
                <button class="topnav-icon-btn" onclick="toggleTheme()" data-tooltip="Toggle theme">
                    <span class="material-symbols-outlined theme-icon-light">dark_mode</span>
                    <span class="material-symbols-outlined theme-icon-dark">light_mode</span>
                </button>
                <div class="topnav-profile">
                    <div class="topnav-avatar">
                        <?= strtoupper($firstName[0]) ?>
                        <span class="online-dot"></span>
                    </div>
                    <div class="topnav-profile-info">
                        <div class="topnav-profile-name"><?= h($firstName) ?></div>
                        <div class="topnav-profile-role">Student</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-content">
            <div class="dashboard-content-inner">
                <div class="welcome-section">
                    <div class="welcome-text">
                        <h1 class="welcome-heading">My Profile</h1>
                        <p class="welcome-subtitle"><?= h($student['course_name']) ?></p>
                        <p class="welcome-batch">Batch <?= h($student['batch_name']) ?></p>
                    </div>
                    <div class="date-card">
                        <div class="date-card-icon">
                            <?= icon('calendar', 24, 'var(--accent)') ?>
                        </div>
                        <div class="date-card-info">
                            <div class="date-card-date"><?= h($formattedDate) ?></div>
                            <div class="date-card-day"><?= h($dayName) ?></div>
                        </div>
                    </div>
                </div>

                <div class="profile-grid">
                    <!-- Avatar Card -->
                    <div class="profile-avatar-card">
                        <div class="profile-avatar-large">
                            <?= strtoupper($student['name'][0]) ?>
                        </div>
                        <h2 class="profile-name"><?= h($student['name']) ?></h2>
                        <div class="profile-role-badge">Student</div>
                    </div>

                    <!-- Details Card -->
                    <div class="profile-details-card">
                        <h3 class="profile-section-title">Personal Information</h3>
                        <div class="profile-details-grid">
                            <div class="profile-detail">
                                <span class="profile-detail-label">Full Name</span>
                                <span class="profile-detail-value"><?= h($student['name']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Email</span>
                                <span class="profile-detail-value"><?= h($student['email']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Phone</span>
                                <span class="profile-detail-value"><?= h($student['phone']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Roll Number</span>
                                <span class="profile-detail-value"><?= h($student['roll_number']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Gender</span>
                                <span class="profile-detail-value"><?= ucfirst(h($student['gender'])) ?></span>
                            </div>
                        </div>

                        <h3 class="profile-section-title" style="margin-top:var(--space-6);">Academic Information</h3>
                        <div class="profile-details-grid">
                            <div class="profile-detail">
                                <span class="profile-detail-label">College</span>
                                <span class="profile-detail-value"><?= h($student['college_name']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Course</span>
                                <span class="profile-detail-value"><?= h($student['course_name']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Branch</span>
                                <span class="profile-detail-value"><?= h($student['branch']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Year of Joining</span>
                                <span class="profile-detail-value"><?= h($student['year_of_joining']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Batch</span>
                                <span class="profile-detail-value"><?= h($student['batch_name']) ?></span>
                            </div>
                            <div class="profile-detail">
                                <span class="profile-detail-label">Member Since</span>
                                <span class="profile-detail-value"><?= date('F Y', strtotime($student['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function toggleSidebar(forceState) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isOpen = forceState !== undefined ? forceState : !sidebar.classList.contains('open');
    sidebar.classList.toggle('open', isOpen);
    overlay.classList.toggle('show', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen);
    sidebar.setAttribute('aria-hidden', !isOpen);
}
function closeSidebar() { toggleSidebar(false); }
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
    if (e.key === 'Tab') {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar || !sidebar.classList.contains('open')) return;
        const f = sidebar.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
        if (!f.length) return;
        if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length-1].focus(); }
        else if (!e.shiftKey && document.activeElement === f[f.length-1]) { e.preventDefault(); f[0].focus(); }
    }
});
function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    const label = document.getElementById('themeLabel');
    if (label) label.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
}
(function() {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        const label = document.getElementById('themeLabel');
        if (label) label.textContent = saved === 'dark' ? 'Light Mode' : 'Dark Mode';
    }
})();

if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
