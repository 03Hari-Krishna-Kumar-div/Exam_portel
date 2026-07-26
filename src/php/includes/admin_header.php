<?php
/**
 * Admin layout header — Fluent 2 Enterprise with Neumorphism.
 * Matches admin.png reference design.
 * Call requireAdmin() before including this.
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/icons.php';
startSession();
requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF']);
$currentTab  = $_GET['tab'] ?? '';
$adminEmail  = $_SESSION['admin_email'] ?? 'Admin';
$adminName   = $_SESSION['admin_name'] ?? $adminEmail;
$adminInitial = strtoupper(substr($adminName, 0, 1));

// Navigation Tree
$navSections = [
    'overview' => [
        'label' => 'Overview',
        'items' => [
            ['url' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ],
    ],
    'institution' => [
        'label' => 'Institution Management',
        'items' => [
            ['url' => 'colleges.php',  'label' => 'Colleges', 'icon' => 'college'],
            ['url' => 'courses.php',   'label' => 'Courses',  'icon' => 'course'],
            ['url' => 'batches.php',   'label' => 'Batches',  'icon' => 'batch'],
        ],
    ],
    'students' => [
        'label' => 'Student Management',
        'items' => [
            ['url' => 'students.php',              'label' => 'Students',             'icon' => 'student'],
            ['url' => 'pending_verifications.php', 'label' => 'Pending Verifications','icon' => 'clock'],
            ['url' => 'students.php',                'label' => 'Guest Access',         'icon' => 'external-link'],
        ],
    ],
    'studio' => [
        'label' => 'Assessment Studio',
        'items' => [
            ['url' => 'assessment_studio.php',              'label' => 'Create Assessment',  'icon' => 'plus'],
            ['url' => 'assessment_studio.php?tab=drafts',   'label' => 'Draft Assessments',  'icon' => 'document'],
            ['url' => 'question_library.php',                'label' => 'Question Library',   'icon' => 'database'],
        ],
    ],
    'management' => [
        'label' => 'Assessment Management',
        'items' => [
            ['url' => 'assessment_management.php',           'label' => 'All Assessments',    'icon' => 'status'],
            ['url' => 'live_monitor.php',                    'label' => 'Live Monitor',       'icon' => 'pulse'],
            ['url' => 'grading.php',                         'label' => 'Grading',            'icon' => 'grading'],
        ],
    ],
    'reports' => [
        'label' => 'Reports & Analytics',
        'items' => [
            ['url' => 'reports.php',       'label' => 'Reports',       'icon' => 'chart'],
            ['url' => 'tab_switcher.php',  'label' => 'Tab Activity',  'icon' => 'activity'],
            ['url' => 'failed_logins.php', 'label' => 'Failed Logins', 'icon' => 'warning'],
        ],
    ],
    'system' => [
        'label' => 'System',
        'items' => [
            ['url' => 'settings.php',     'label' => 'Settings',     'icon' => 'settings'],
            ['url' => 'activity_logs.php', 'label' => 'Activity Logs', 'icon' => 'clock'],
        ],
    ],
    'support' => [
        'label' => 'Support',
        'items' => [
            ['url' => 'help.php', 'label' => 'Help & Documentation', 'icon' => 'help'],
        ],
    ],
];

function isNavActive(string $itemUrl, string $currentPage, string $currentTab): bool {
    $urlPath = parse_url($itemUrl, PHP_URL_PATH);
    $urlQuery = parse_url($itemUrl, PHP_URL_QUERY);
    parse_str($urlQuery ?? '', $urlParams);
    $tabParam = $urlParams['tab'] ?? '';
    if ($urlPath === $currentPage) {
        if (!$tabParam && !$currentTab) return true;
        if ($tabParam && $tabParam === $currentTab) return true;
    }
    return false;
}

$sidebarCollapsed = $_COOKIE['sidebar_collapsed'] ?? '' === '1';
?><!DOCTYPE html>
<html lang="en" data-compact="true"<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? ' data-theme="dark"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — <?= h($pageTitle ?? 'Dashboard') ?> | Test Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,300,0,0">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin.css">
    <style>
        @font-face {
            font-family: 'Segoe UI Variable Display';
            src: local('Segoe UI Variable Display'), local('Segoe UI');
            font-display: swap;
        }
        @font-face {
            font-family: 'Segoe UI Variable Text';
            src: local('Segoe UI Variable Text'), local('Segoe UI');
            font-display: swap;
        }
    </style>
    <script src="https://unpkg.com/lucide@latest" defer></script>
</head>
<body>
<?= iconSprite() ?>
<div class="admin-layout<?= $sidebarCollapsed ? ' sidebar-collapsed' : '' ?>" id="appLayout">

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-mark">T</div>
            <span class="sidebar-logo-text">Test Platform</span>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navSections as $section): ?>
                <div class="nav-section"><?= h($section['label']) ?></div>
                <?php foreach ($section['items'] as $item):
                    $active = isNavActive($item['url'], $currentPage, $currentTab);
                ?>
                    <a href="<?= BASE_URL ?>/admin/<?= $item['url'] ?>"
                       class="nav-item<?= $active ? ' active' : '' ?>"
                       <?= $active ? 'aria-current="page"' : '' ?>
                       data-tooltip="<?= h($item['label']) ?>">
                        <span class="nav-icon"><?= icon($item['icon'], 24) ?></span>
                        <span class="nav-label"><?= h($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <div class="sidebar-divider"></div>
            <a href="<?= BASE_URL ?>/logout.php" class="nav-item" style="margin-top:auto;" data-tooltip="Sign Out">
                <span class="nav-icon"><?= icon('logout', 24) ?></span>
                <span class="nav-label">Sign Out</span>
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">

        <!-- Top Navigation Bar -->
        <header class="topnav">
            <div class="topnav-left">
                <button class="topnav-toggle" id="sidebarToggle" onclick="toggleCollapse()" aria-label="Toggle sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div class="topnav-search search-wrapper">
                    <span class="search-icon"><?= icon('search', 16) ?></span>
                    <input type="text" id="globalSearch" placeholder="Search assessments, students, courses..." aria-label="Global search" autocomplete="off">
                    <span class="search-kbd"><kbd>Ctrl</kbd> <kbd>K</kbd></span>
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>
            </div>
            <div class="topnav-right">
                <button class="topnav-quick-action" onclick="window.location.href='<?= BASE_URL ?>/admin/assessment_studio.php'">
                    <?= icon('plus', 16) ?>
                    <span class="qa-label-text">Create Assessment</span>
                </button>
                <button class="topnav-btn" onclick="toggleTheme()" data-tooltip="Toggle theme">
                    <span class="material-symbols-outlined theme-icon-light">dark_mode</span>
                    <span class="material-symbols-outlined theme-icon-dark">light_mode</span>
                </button>
                <button class="topnav-btn" id="notifBtn" onclick="toggleNotifications()" data-tooltip="Notifications" aria-label="Notifications">
                    <?= icon('notifications', 18) ?>
                    <span class="badge-dot"></span>
                </button>
                <!-- Notification Panel -->
                <div class="notif-panel" id="notifPanel">
                    <div class="notif-panel-header">
                        <h3>Notifications</h3>
                        <button class="btn btn-sm btn-ghost" onclick="toggleNotifications()"><?= icon('x', 14) ?></button>
                    </div>
                    <div class="notif-panel-body" id="notifPanelBody">
                        <div class="notif-empty">
                            <?= icon('notifications', 32) ?>
                            <p>No new notifications</p>
                        </div>
                    </div>
                </div>
                <!-- Admin Profile Dropdown -->
                <div class="topnav-profile" id="profileMenu">
                    <div class="topnav-avatar">
                        <?= $adminInitial ?>
                        <span class="online-dot"></span>
                    </div>
                    <div class="topnav-profile-info">
                        <span class="topnav-profile-name"><?= h($adminName) ?></span>
                        <span class="topnav-profile-role">Administrator</span>
                    </div>
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="<?= BASE_URL ?>/admin/settings.php" class="profile-dropdown-item">
                            <?= icon('settings', 16) ?> Account Settings
                        </a>
                        <div class="profile-dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/logout.php" class="profile-dropdown-item profile-dropdown-danger">
                            <?= icon('logout', 16) ?> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="content-area">