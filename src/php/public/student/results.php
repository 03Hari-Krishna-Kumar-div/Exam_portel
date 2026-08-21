<?php
$pageTitle = 'My Results';
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

// Evaluated submissions with test details
$stmt = $pdo->prepare("
    SELECT t.title AS test_title, t.duration_minutes,
           s.total_marks_obtained, s.total_marks, s.submitted_at, s.status AS submission_status
    FROM submissions s
    JOIN tests t ON t.id = s.test_id
    WHERE s.student_id = ? AND s.status = 'evaluated'
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$studentId]);
$results = $stmt->fetchAll();

// Stats
$totalEvaluated = count($results);
$avgPercentage = 0;
$highestScore = 0;
if ($totalEvaluated > 0) {
    $totalPct = 0;
    foreach ($results as $r) {
        $pct = ($r['total_marks'] > 0) ? ($r['total_marks_obtained'] / $r['total_marks']) * 100 : 0;
        $totalPct += $pct;
        if ($pct > $highestScore) $highestScore = $pct;
    }
    $avgPercentage = round($totalPct / $totalEvaluated, 1);
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
    <script src="https://unpkg.com/lucide@latest"></script>
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
                <a href="results.php" class="sidebar-nav-item active">
                    <?= icon('chart', 20) ?>
                    <span>Results</span>
                </a>
                <a href="analytics.php" class="sidebar-nav-item">
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
                        <h1 class="welcome-heading">My Results</h1>
                        <p class="welcome-subtitle"><?= h($student['course_name']) ?></p>
                        <p class="welcome-batch">Batch <?= h($student['batch_name']) ?><?= !empty($student['section']) ? ' — Section ' . h($student['section']) : '' ?></p>
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

                <!-- Summary Stats -->
                <div class="stats-row">
                    <div class="stat-card-gradient stat-card-total">
                        <div class="stat-card-icon"><?= icon('chart', 24) ?></div>
                        <div class="stat-card-value"><?= $totalEvaluated ?></div>
                        <div class="stat-card-label">Tests Evaluated</div>
                        <div class="stat-card-desc">Completed assessments</div>
                        <div class="stat-card-arrow"><?= icon('arrow-right', 14) ?></div>
                    </div>
                    <div class="stat-card-gradient stat-card-completed">
                        <div class="stat-card-icon"><?= icon('star', 24) ?></div>
                        <div class="stat-card-value"><?= $avgPercentage ?>%</div>
                        <div class="stat-card-label">Average Score</div>
                        <div class="stat-card-desc">Across all evaluated tests</div>
                        <div class="stat-card-arrow"><?= icon('arrow-right', 14) ?></div>
                    </div>
                    <div class="stat-card-gradient stat-card-pending">
                        <div class="stat-card-icon"><?= icon('graph', 24) ?></div>
                        <div class="stat-card-value"><?= $totalEvaluated > 0 ? round($highestScore) . '%' : '—' ?></div>
                        <div class="stat-card-label">Highest Score</div>
                        <div class="stat-card-desc">Best performance</div>
                        <div class="stat-card-arrow"><?= icon('arrow-right', 14) ?></div>
                    </div>
                </div>

                <div class="tests-section">
                    <div class="tests-section-header">
                        <h2 class="tests-section-title">Assessment Results</h2>
                    </div>

                    <?php if (empty($results)): ?>
                        <div class="card-flat">
                            <div class="card-body">
                                <div class="empty-state">
                                    <div class="empty-icon"><?= icon('chart', 56) ?></div>
                                    <h3>No Results Yet</h3>
                                    <p>Your evaluated test results will appear here once assessments are graded.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="tests-table-view">
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="th-icon"><?= icon('test', 14) ?></div>
                                                Assessment
                                            </th>
                                            <th>
                                                <div class="th-icon"><?= icon('star', 14) ?></div>
                                                Score
                                            </th>
                                            <th>
                                                <div class="th-icon"><?= icon('clock', 14) ?></div>
                                                Submitted
                                            </th>
                                            <th>Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $r):
                                            $pct = ($r['total_marks'] > 0) ? round(($r['total_marks_obtained'] / $r['total_marks']) * 100) : 0;
                                            $barClass = $pct >= 70 ? 'success' : ($pct >= 40 ? 'warning' : 'danger');
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="test-name-cell">
                                                    <div class="test-icon"><?= icon('test', 16) ?></div>
                                                    <div>
                                                        <div class="test-name"><?= h($r['test_title']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= (float)$r['total_marks_obtained'] ?></strong>
                                                <span class="text-muted">/ <?= (float)$r['total_marks'] ?></span>
                                            </td>
                                            <td class="text-sm text-muted">
                                                <?= $r['submitted_at'] ? date('M j, Y', strtotime($r['submitted_at'])) : '—' ?>
                                            </td>
                                            <td>
                                                <div class="result-performance">
                                                    <div class="progress-bar" style="width:100px;">
                                                        <div class="progress-fill <?= $barClass ?>" style="width:<?= $pct ?>%;"></div>
                                                    </div>
                                                    <span class="text-sm" style="font-weight:600;color:<?= $pct >= 70 ? 'var(--green)' : ($pct >= 40 ? 'var(--yellow)' : 'var(--red)') ?>;">
                                                        <?= $pct ?>%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
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

lucide.createIcons();
</script>
</body>
</html>
