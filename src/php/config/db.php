<?php
/**
 * Fluent 2 Test Platform - Database Configuration
 * 
 * Switch between local and production by changing DB_ENV.
 */

define('DB_ENV', 'local'); // 'local' or 'production'

// Set PHP timezone to match MySQL (Asia/Kolkata = UTC+5:30).
// Change to your local timezone if different.
date_default_timezone_set('Asia/Kolkata');

if (DB_ENV === 'production') {
    // Northflank / Production settings
    define('DB_HOST', getenv('DB_HOST') ?: 'your-northflank-mysql-host');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'test_platform');
    define('DB_USER', getenv('DB_USER') ?: 'your_user');
    define('DB_PASS', getenv('DB_PASS') ?: 'your_password');
} else {
    // XAMPP Local settings
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'test_platform');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

define('PYTHON_API_URL', 'http://127.0.0.1:5000');

/*
 * BASE_URL: URL prefix for all redirects and links.
 * - PHP built-in server (cli-server): empty string — doc root is public/
 * - XAMPP / Apache: '/test-platform/src/php/public'
 */
if (php_sapi_name() === 'cli-server') {
    define('BASE_URL', '');
    define('ASSETS_URL', '/assets');
} else {
    define('BASE_URL', '/test-platform/src/php/public');
    define('ASSETS_URL', '/test-platform/assets');
}

/**
 * Get PDO database connection.
 * All queries MUST use this with prepared statements.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
