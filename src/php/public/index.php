<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSession();

if (isAdmin()) {
    redirect('/test-platform/src/php/public/admin/dashboard.php');
} elseif (isStudent()) {
    redirect('/test-platform/src/php/public/student/dashboard.php');
} elseif (isset($_SESSION['guest_token'])) {
    redirect('/test-platform/src/php/public/student/test.php');
}
// Otherwise show login
redirect('/test-platform/src/php/public/login.php');
