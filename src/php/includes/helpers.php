<?php
/**
 * Utility / helper functions.
 */

// ─── PHP 8 Polyfills for PHP 7.x compatibility ─────────────
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
// ────────────────────────────────────────────────────────────

/**
 * Redirect and exit.
 * Automatically strips hardcoded XAMPP prefix and prepends BASE_URL.
 */
function redirect(string $url): void {
    // Strip any hardcoded XAMPP prefix (keeps URLs portable)
    $prefix = '/test-platform/src/php/public';
    if (str_starts_with($url, $prefix)) {
        $url = substr($url, strlen($prefix));
    }
    // Prepend BASE_URL for absolute paths
    if (str_starts_with($url, '/')) {
        $url = BASE_URL . $url;
    }
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        // Headers already sent — use JS redirect as fallback
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    }
    exit;
}

/**
 * Flash message (set or get).
 */
function flash(string $key, ?string $value = null): ?string {
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

/**
 * Flash message HTML helper — Fluent 2 alert with SVG icons.
 */
function flashMessage(): string {
    $icons = [
        'success' => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg>',
        'error'   => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 1 1 0 16 8 8 0 0 1 0-16zm0 1a7 7 0 1 0 0 14 7 7 0 0 0 0-14zm0 9.5a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5zM10 6a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0v-4A.5.5 0 0 1 10 6z"/></svg>',
        'warning' => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 1 1 0 16 8 8 0 0 1 0-16zm0 1a7 7 0 1 0 0 14 7 7 0 0 0 0-14zm0 9.5a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5zM10 6a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0v-4A.5.5 0 0 1 10 6z"/></svg>',
        'info'    => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 1 1 0 16 8 8 0 0 1 0-16zm0 1a7 7 0 1 0 0 14 7 7 0 0 0 0-14zm-.5 3.5a.5.5 0 0 1 .5-.5h.01a.5.5 0 0 1 0 1H10a.5.5 0 0 1-.5-.5zM9 9a1 1 0 0 1 1-1h.25a1 1 0 0 1 1 1v2.5h.25a.5.5 0 0 1 0 1h-1.5a.5.5 0 0 1 0-1h.25V9.5H10a.5.5 0 0 1-.5.5V9z"/></svg>',
    ];
    $html = '';
    foreach ($icons as $key => $svg) {
        $msg = flash($key);
        if ($msg) {
            $clean = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="alert alert-' . $key . '">';
            $html .= $svg;
            $html .= '<span>' . $clean . '</span>';
            $html .= '</div>';
        }
    }
    return $html;
}

/**
 * Sanitize output for HTML.
 */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a secure random token (for guest links, QR codes).
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Get date/time for display.
 */
function formatDateTime(?string $datetime): string {
    if (!$datetime) return '—';
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * Get time ago string.
 */
function timeAgo(?string $datetime): string {
    if (!$datetime) return '—';
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return floor($diff / 86400) . ' days ago';
}

/**
 * Call Python analysis API.
 */
function callPythonApi(string $endpoint, array $params = []): ?array {
    $url = PYTHON_API_URL . $endpoint . '?' . http_build_query($params);
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method'  => 'GET',
        ],
    ]);
    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) return null;
    return json_decode($result, true);
}

/**
 * Get eligible tests for a student (based on batch).
 */
function getStudentTests(int $studentId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT t.*,
               s.id AS submission_id,
               s.status AS submission_status,
               s.started_at,
               s.submitted_at,
               s.timer_extended_minutes,
               s.total_marks_obtained,
               s.total_marks,
               (SELECT COUNT(*) FROM questions q WHERE q.test_id = t.id) AS total_questions
        FROM tests t
        JOIN batches b ON b.id = t.batch_id
        JOIN students st ON st.batch_id = b.id
        LEFT JOIN submissions s ON s.test_id = t.id AND s.student_id = st.id
        WHERE st.id = ?
        ORDER BY t.start_time DESC
    ");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

/**
 * Get remaining time in seconds for a submission.
 */
function getRemainingSeconds(array $submission, int $testDuration): int {
    $started = strtotime($submission['started_at']);
    $extended = ($submission['timer_extended_minutes'] ?? 0) * 60;
    $elapsed = time() - $started;
    $total = ($testDuration * 60) + $extended;
    return max(0, $total - $elapsed);
}

// ─── ADMIN NOTIFICATIONS (bell icon feed) ────────────────────

/**
 * Create an admin notification. Never throws — failures are logged silently
 * so the caller flow (signup, login, guest, test) is never interrupted.
 *
 * Types: student_account | guest_link | qr | test | system
 */
function notifyAdmin(string $type, string $title, string $message, ?string $link = null): void {
    try {
        static $pdo = null;
        $pdo = $pdo ?? getDB();
        $stmt = $pdo->prepare("INSERT INTO admin_notifications (type, title, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $title, mb_substr($message, 0, 500), $link]);
    } catch (Throwable $e) {
        error_log('notifyAdmin failed: ' . $e->getMessage());
    }
}

/**
 * Render notification items HTML for the bell panel (shared by the
 * server-rendered header and the JSON poll endpoint).
 */
function renderNotificationItems(array $items): string {
    $typeIcon = [
        'student_account' => 'student',      // graduation cap
        'guest_link'      => 'external-link',
        'qr'              => 'external-link',
        'test'            => 'test',
        'system'          => 'warning',
    ];
    $typeColor = [
        'student_account' => 'amber',
        'guest_link'      => 'blue',
        'qr'              => 'blue',
        'test'            => 'red',
        'system'          => 'red',
    ];

    if (empty($items)) {
        $icon = function_exists('icon') ? icon('notifications', 32) : '';
        return '<div class="notif-empty">' . $icon . '<p>No notifications</p></div>';
    }

    $html = '';
    foreach ($items as $n) {
        $cls   = $typeColor[$n['type']] ?? 'amber';
        $ic    = $typeIcon[$n['type']] ?? 'warning';
        $title = h($n['title'] ?? 'Notification');
        $msg   = h($n['message'] ?? '');
        $time  = timeAgo($n['created_at']);
        $dot   = empty($n['is_read']) ? '<span class="notif-unread-dot"></span>' : '';
        $readCls = empty($n['is_read']) ? '' : ' is-read';
        $tag      = !empty($n['link']) ? 'a' : 'div';
        $hrefAttr = !empty($n['link']) ? ' href="' . h($n['link']) . '"' : '';
        $iconSvg  = function_exists('icon') ? icon($ic, 16) : '';

        $html .= '<' . $tag . $hrefAttr . ' class="notif-item' . $readCls . '" data-id="' . (int)$n['id'] . '">'
               . '<span class="notif-icon ' . $cls . '">' . $iconSvg . '</span>'
               . '<span class="notif-content">'
               . '<span class="notif-text"><strong>' . $title . '</strong>' . ($msg ? ' — ' . $msg : '') . '</span>'
               . '<span class="notif-time">' . $time . $dot . '</span>'
               . '</span>'
               . '</' . $tag . '>';
    }
    return $html;
}
