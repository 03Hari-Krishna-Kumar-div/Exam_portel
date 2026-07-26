<?php
$pageTitle = 'My Analytics';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/icons.php';
startSession();
requireStudent();

$pdo = getDB();
$studentId = $_SESSION['student_id'];

// Student info
$stmt = $pdo->prepare("SELECT s.*, b.name AS batch_name, c.name AS course_name FROM students s JOIN batches b ON b.id = s.batch_id JOIN courses c ON c.id = b.course_id WHERE s.id = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch();

// All tests with submissions
$stmt = $pdo->prepare("
    SELECT t.title, t.duration_minutes, t.status AS test_status,
           s.status AS submission_status,
           s.total_marks_obtained, s.total_marks, s.submitted_at, s.started_at
    FROM tests t
    LEFT JOIN submissions s ON s.test_id = t.id AND s.student_id = ?
    JOIN batches b ON b.id = t.batch_id
    JOIN students st ON st.batch_id = b.id
    WHERE st.id = ?
    ORDER BY t.start_time DESC
");
$stmt->execute([$studentId, $studentId]);
$allData = $stmt->fetchAll();

// Compute analytics
$totalTests = count($allData);
$evaluated = array_filter($allData, fn($r) => $r['submission_status'] === 'evaluated');
$submitted = array_filter($allData, fn($r) => $r['submission_status'] === 'submitted');
$inProgress = array_filter($allData, fn($r) => $r['submission_status'] === 'in_progress');
$notStarted = array_filter($allData, fn($r) => !$r['submission_status']);

$evaluatedCount = count($evaluated);
$submittedCount = count($submitted);
$inProgressCount = count($inProgress);
$notStartedCount = count($notStarted);

$avgPercentage = 0;
$bestPercentage = 0;
$lowestPercentage = 100;
$scoreDistribution = ['excellent' => 0, 'good' => 0, 'average' => 0, 'poor' => 0];

if ($evaluatedCount > 0) {
    $totalPct = 0;
    foreach ($evaluated as $r) {
        $pct = ($r['total_marks'] > 0) ? ($r['total_marks_obtained'] / $r['total_marks']) * 100 : 0;
        $totalPct += $pct;
        if ($pct > $bestPercentage) $bestPercentage = $pct;
        if ($pct < $lowestPercentage) $lowestPercentage = $pct;
        if ($pct >= 80) $scoreDistribution['excellent']++;
        elseif ($pct >= 60) $scoreDistribution['good']++;
        elseif ($pct >= 40) $scoreDistribution['average']++;
        else $scoreDistribution['poor']++;
    }
    $avgPercentage = round($totalPct / $evaluatedCount, 1);
    $bestPercentage = round($bestPercentage);
    $lowestPercentage = round($lowestPercentage);
} else {
    $lowestPercentage = 0;
}

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
                <a href="analytics.php" class="sidebar-nav-item active">
                    <?= icon('graph', 20) ?>
                    <span>Analytics</span>
                </a>
                <a href="profile.php" class="sidebar-nav-item">
                    <?= icon('student', 20) ?>
                    <span>Profile</span>
                </a>
            </div>
            <div class="sidebar-nav-group">
                <div class="sidebar-nav-label">Appearance</div>
                <button class="sidebar-nav-item theme-toggle" onclick="toggleTheme()" id="themeToggle">
                    <span class="material-symbols-outlined theme-icon">dark_mode</span>
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
                    <span class="material-symbols-outlined theme-icon">dark_mode</span>
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
                        <h1 class="welcome-heading">My Analytics</h1>
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

                <!-- Overview Stats -->
                <div class="stats-row">
                    <div class="stat-card-gradient stat-card-total">
                        <div class="stat-card-icon"><?= icon('test', 24) ?></div>
                        <div class="stat-card-value"><?= $totalTests ?></div>
                        <div class="stat-card-label">Total Tests</div>
                        <div class="stat-card-desc">All assigned assessments</div>
                        <div class="stat-card-arrow"><?= icon('arrow-right', 14) ?></div>
                    </div>
                    <div class="stat-card-gradient stat-card-completed">
                        <div class="stat-card-icon"><?= icon('check-circle', 24) ?></div>
                        <div class="stat-card-value"><?= $evaluatedCount ?></div>
                        <div class="stat-card-label">Evaluated</div>
                        <div class="stat-card-desc">Graded assessments</div>
                        <div class="stat-card-arrow"><?= icon('arrow-right', 14) ?></div>
                    </div>
                    <div class="stat-card-gradient stat-card-pending">
                        <div class="stat-card-icon"><?= icon('graph', 24) ?></div>
                        <div class="stat-card-value"><?= $avgPercentage ?>%</div>
                        <div class="stat-card-label">Avg Score</div>
                        <div class="stat-card-desc">Average performance</div>
                        <div class="stat-card-arrow"><?= icon('arrow-right', 14) ?></div>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="analytics-grid">
                    <!-- Test Status -->
                    <div class="card-flat">
                        <div class="card-header">
                            <h3><?= icon('status', 16) ?> Test Status Overview</h3>
                        </div>
                        <div class="card-body">
                            <div class="analytics-bar-list">
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">Evaluated</span>
                                    <span class="analytics-bar-value"><?= $evaluatedCount ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill success" style="width:<?= $totalTests > 0 ? ($evaluatedCount / $totalTests) * 100 : 0 ?>%;"></div>
                                    </div>
                                </div>
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">Submitted</span>
                                    <span class="analytics-bar-value"><?= $submittedCount ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill pending" style="width:<?= $totalTests > 0 ? ($submittedCount / $totalTests) * 100 : 0 ?>%;"></div>
                                    </div>
                                </div>
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">In Progress</span>
                                    <span class="analytics-bar-value"><?= $inProgressCount ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill active" style="width:<?= $totalTests > 0 ? ($inProgressCount / $totalTests) * 100 : 0 ?>%;"></div>
                                    </div>
                                </div>
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">Not Started</span>
                                    <span class="analytics-bar-value"><?= $notStartedCount ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill" style="background:var(--gray-20);width:<?= $totalTests > 0 ? ($notStartedCount / $totalTests) * 100 : 0 ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Score Distribution -->
                    <div class="card-flat">
                        <div class="card-header">
                            <h3><?= icon('chart', 16) ?> Score Distribution</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($evaluatedCount > 0): ?>
                            <div class="analytics-bar-list">
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">Excellent (80-100%)</span>
                                    <span class="analytics-bar-value"><?= $scoreDistribution['excellent'] ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill success" style="width:<?= ($scoreDistribution['excellent'] / $evaluatedCount) * 100 ?>%;"></div>
                                    </div>
                                </div>
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">Good (60-79%)</span>
                                    <span class="analytics-bar-value"><?= $scoreDistribution['good'] ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill active" style="width:<?= ($scoreDistribution['good'] / $evaluatedCount) * 100 ?>%;"></div>
                                    </div>
                                </div>
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">Average (40-59%)</span>
                                    <span class="analytics-bar-value"><?= $scoreDistribution['average'] ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill pending" style="width:<?= ($scoreDistribution['average'] / $evaluatedCount) * 100 ?>%;"></div>
                                    </div>
                                </div>
                                <div class="analytics-bar-item">
                                    <span class="analytics-bar-label">Poor (0-39%)</span>
                                    <span class="analytics-bar-value"><?= $scoreDistribution['poor'] ?></span>
                                    <div class="analytics-bar-track">
                                        <div class="analytics-bar-fill danger" style="width:<?= ($scoreDistribution['poor'] / $evaluatedCount) * 100 ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="empty-state" style="padding:var(--space-8) var(--space-4);">
                                <div class="empty-icon"><?= icon('graph', 48) ?></div>
                                <p>No evaluated tests yet to show distribution.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Performance Summary -->
                    <div class="card-flat" style="grid-column:1/-1;">
                        <div class="card-header">
                            <h3><?= icon('graph', 16) ?> Performance Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="analytics-summary-grid">
                                <div class="analytics-summary-item">
                                    <span class="analytics-summary-label">Best Score</span>
                                    <span class="analytics-summary-value" style="color:var(--green);"><?= $bestPercentage ?>%</span>
                                </div>
                                <div class="analytics-summary-item">
                                    <span class="analytics-summary-label">Average Score</span>
                                    <span class="analytics-summary-value" style="color:var(--accent);"><?= $avgPercentage ?>%</span>
                                </div>
                                <div class="analytics-summary-item">
                                    <span class="analytics-summary-label">Lowest Score</span>
                                    <span class="analytics-summary-value" style="color:var(--red);"><?= $lowestPercentage ?>%</span>
                                </div>
                                <div class="analytics-summary-item">
                                    <span class="analytics-summary-label">Tests Completed</span>
                                    <span class="analytics-summary-value"><?= $evaluatedCount ?>/<?= $totalTests ?></span>
                                </div>
                                <div class="analytics-summary-item">
                                    <span class="analytics-summary-label">Completion Rate</span>
                                    <span class="analytics-summary-value"><?= $totalTests > 0 ? round(($evaluatedCount / $totalTests) * 100) : 0 ?>%</span>
                                </div>
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
    updateThemeUI(newTheme);
}
function updateThemeUI(theme) {
    const label = document.getElementById('themeLabel');
    if (label) label.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
    document.querySelectorAll('.theme-icon').forEach(el => {
        el.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    });
}
(function() {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
        updateThemeUI(saved);
    }
})();

if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
