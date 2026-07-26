<?php
$pageTitle = 'Manage Students';
require_once __DIR__ . '/../../includes/admin_header.php';

$pdo = getDB();
$message = '';

// Handle guest link generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_guest' || $action === 'generate_qr') {
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $testId = !empty($_POST['test_id']) ? (int)$_POST['test_id'] : null;

        if ($batchId > 0) {
            $token = generateToken();
            $type = ($action === 'generate_qr') ? 'qr' : 'guest';
            $expires = date('Y-m-d H:i:s', strtotime('+30 days')); // Default expiry

            // If a test is selected, use its end_time
            if ($testId) {
                $tStmt = $pdo->prepare("SELECT end_time FROM tests WHERE id = ?");
                $tStmt->execute([$testId]);
                $test = $tStmt->fetch();
                if ($test && $test['end_time']) {
                    $expires = $test['end_time'];
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO guest_entries (batch_id, test_id, token, type, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$batchId, $testId, $token, $type, $expires]);

            $fullUrl = BASE_URL . '/guest.php?token=' . $token;
            $message = ($type === 'qr' ? 'QR code' : 'Guest link') . ' generated successfully!';
            $generatedUrl = $fullUrl;
            $generatedToken = $token;
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        $message = 'Student removed.';
    }
}

// Filters
$filterCollege = (int)($_GET['college_id'] ?? 0);
$filterCourse = (int)($_GET['course_id'] ?? 0);
$filterBatch = (int)($_GET['batch_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

// Build query
$sql = "
    SELECT s.*, b.name AS batch_name, c.name AS course_name, cl.name AS college_name
    FROM students s
    JOIN batches b ON b.id = s.batch_id
    JOIN courses c ON c.id = b.course_id
    JOIN colleges cl ON cl.id = c.college_id
";
$params = [];
$where = [];
if ($filterBatch > 0) {
    $where[] = "s.batch_id = ?"; $params[] = $filterBatch;
} elseif ($filterCourse > 0) {
    $where[] = "b.course_id = ?"; $params[] = $filterCourse;
} elseif ($filterCollege > 0) {
    $where[] = "c.college_id = ?"; $params[] = $filterCollege;
}
if ($search) {
    $where[] = "(s.name LIKE ? OR s.email LIKE ? OR s.roll_number LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// For filters
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name")->fetchAll();

// Guest entries for linking
$guestStmt = $pdo->query("
    SELECT g.*, b.name AS batch_name, t.title AS test_title
    FROM guest_entries g
    LEFT JOIN batches b ON b.id = g.batch_id
    LEFT JOIN tests t ON t.id = g.test_id
    WHERE g.status = 'pending'
    ORDER BY g.created_at DESC LIMIT 20
");
$guestEntries = $guestStmt->fetchAll();

// Tests for guest link generation
$tests = $pdo->query("SELECT id, title FROM tests ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>

<div class="stats-row">
    <div class="stat-box">
        <div class="stat-num"><?= count($students) ?></div>
        <div class="stat-label">Filtered Students</div>
    </div>
    <div class="stat-box">
        <div class="stat-num"><?= count($guestEntries) ?></div>
        <div class="stat-label">Pending Guest Links</div>
    </div>
</div>

<div class="dashboard-header" style="margin-bottom:var(--space-4);">
    <div class="dashboard-header-left">
        <h1>Students</h1>
        <p class="dashboard-subtitle">Manage student accounts and guest access</p>
    </div>
    <div class="dashboard-header-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('guestModal')">+ Generate Guest Link</button>
    </div>
</div>

<div class="card-flat">

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin:0 var(--space-5) var(--space-4);">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg>
            <span>
                <?= h($message) ?>
                <?php if (isset($generatedUrl)): ?>
                    <div style="margin-top:var(--space-3);padding:var(--space-4);background:var(--accent-light);border-radius:var(--radius-lg);word-break:break-all;">
                        <div style="display:flex;gap:var(--space-4);align-items:start;flex-wrap:wrap;">
                            <div style="flex:1;min-width:200px;">
                                <div style="font-size:var(--fs-12);font-weight:600;color:var(--gray-60);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:var(--space-2);">Guest Link</div>
                                <a href="<?= h($generatedUrl) ?>" target="_blank" style="font-size:var(--fs-13);"><?= h($generatedUrl) ?></a>
                                <div style="margin-top:var(--space-2);font-size:var(--fs-12);color:var(--gray-50);">
                                    Token: <code style="font-size:var(--fs-11);"><?= h($generatedToken) ?></code>
                                </div>
                                <button class="btn btn-sm btn-secondary mt-2" onclick="copyToClipboard('<?= h($generatedUrl) ?>')">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path d="M6 4V3a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1zm0 1H5a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1v-1H8a2 2 0 0 1-2-2V5zm9-1H8a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1z"/></svg>
                                    Copy Link
                                </button>
                            </div>
                            <?php if (($_POST['action'] ?? '') === 'generate_qr'): ?>
                            <div style="text-align:center;padding:var(--space-3);background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--gray-15);">
                                <div style="font-size:var(--fs-12);font-weight:600;color:var(--gray-60);margin-bottom:var(--space-2);">QR Code</div>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= urlencode($generatedUrl) ?>"
                                     alt="QR Code" style="border-radius:var(--radius-md);display:block;" width="140" height="140">
                                <a href="<?= h($generatedUrl) ?>" target="_blank" style="font-size:var(--fs-12);display:inline-block;margin-top:var(--space-2);">Open Link <?= icon('external-link', 12) ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </span>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="form-inline">
            <select class="form-select" name="college_id" onchange="this.form.submit()">
                <option value="">All Colleges</option>
                <?php foreach ($colleges as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= $filterCollege === $cl['id'] ? 'selected' : '' ?>><?= h($cl['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($filterCollege > 0): ?>
            <select class="form-select" name="course_id" onchange="this.form.submit()">
                <option value="">All Courses</option>
                <?php
                $cStmt = $pdo->prepare("SELECT id, name FROM courses WHERE college_id = ? ORDER BY name");
                $cStmt->execute([$filterCollege]);
                foreach ($cStmt->fetchAll() as $co):
                ?>
                    <option value="<?= $co['id'] ?>" <?= $filterCourse === $co['id'] ? 'selected' : '' ?>><?= h($co['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <?php if ($filterCourse > 0): ?>
            <select class="form-select" name="batch_id" onchange="this.form.submit()">
                <option value="">All Batches</option>
                <?php
                $bStmt = $pdo->prepare("SELECT id, name FROM batches WHERE course_id = ? ORDER BY name");
                $bStmt->execute([$filterCourse]);
                foreach ($bStmt->fetchAll() as $ba):
                ?>
                    <option value="<?= $ba['id'] ?>" <?= $filterBatch === $ba['id'] ? 'selected' : '' ?>><?= h($ba['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <input class="form-input" type="text" name="search" placeholder="Search name, email, roll..." value="<?= h($search) ?>" style="max-width:200px;">
            <button class="btn btn-sm btn-secondary" type="submit">Search</button>
            <?php if ($filterCollege || $filterCourse || $filterBatch || $search): ?>
                <a href="students.php" class="btn btn-sm btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email / Phone</th>
                    <th>College</th>
                    <th>Course / Batch</th>
                    <th>Roll</th>
                    <th>Year</th>
                    <th>Joined</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="8" class="text-center" style="padding:32px;color:var(--gray-50);">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><strong><?= h($s['name']) ?></strong></td>
                        <td>
                            <div><?= h($s['email']) ?></div>
                            <div class="text-sm text-muted"><?= h($s['phone']) ?></div>
                        </td>
                        <td class="text-sm"><?= h($s['college_name']) ?></td>
                        <td>
                            <div><?= h($s['course_name']) ?></div>
                            <div class="text-sm text-muted"><?= h($s['batch_name']) ?></div>
                        </td>
                        <td class="text-sm"><?= h($s['roll_number']) ?></td>
                        <td class="text-sm"><?= h($s['year_of_joining']) ?></td>
                        <td class="text-sm text-muted"><?= timeAgo($s['created_at']) ?></td>
                        <td class="actions" style="white-space:nowrap;">
                            <a href="reports.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost"><?= icon('chart', 14) ?> Reports</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Remove this student? Their submissions will be preserved.')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Guest Link Generation Modal -->
<div class="modal-overlay" id="guestModal" style="display:none;">
    <div class="modal">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="guestAction" value="generate_guest">
            <div class="modal-header">
                <h3 id="guestModalTitle">Generate Guest Link</h3>
                <button type="button" class="modal-close" onclick="closeModal('guestModal')">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4.09 4.09a.5.5 0 0 1 .7 0L10 9.29l5.2-5.2a.5.5 0 0 1 .7.7L10.7 10l5.2 5.2a.5.5 0 0 1-.7.7L10 10.7l-5.2 5.2a.5.5 0 0 1-.7-.7L9.29 10 4.09 4.8a.5.5 0 0 1 0-.7z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Type</label>
                    <div class="flex gap-4" style="margin-top:4px;">
                        <label class="flex-center" style="cursor:pointer;gap:6px;">
                            <input type="radio" name="link_type" value="guest" checked onchange="setGuestType('guest')"> Guest Link
                        </label>
                        <label class="flex-center" style="cursor:pointer;gap:6px;">
                            <input type="radio" name="link_type" value="qr" onchange="setGuestType('qr')"> QR Code
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="guest_college">College</label>
                    <select class="form-select" id="guest_college" onchange="loadGuestCourses()">
                        <option value="">Select College</option>
                        <?php foreach ($colleges as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= h($cl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guest_course">Course</label>
                    <select class="form-select" id="guest_course" onchange="loadGuestBatches()" disabled>
                        <option value="">Select College first</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guest_batch_id">Batch *</label>
                    <select class="form-select" id="guest_batch_id" name="batch_id" required disabled>
                        <option value="">Select Course first</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guest_test_id">Link to Test (optional)</label>
                    <select class="form-select" id="guest_test_id" name="test_id">
                        <option value="">No specific test</option>
                        <?php foreach ($tests as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= h($t['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">If selected, the link expires when the test ends.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('guestModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate</button>
            </div>
        </form>
    </div>
</div>

<script>
// API URL — works on both dev server and XAMPP
const API_URL = <?= json_encode(php_sapi_name() === 'cli-server' ? '/api' : '/test-platform/src/php/api') ?>;

function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function setGuestType(type) {
    document.getElementById('guestAction').value = type === 'qr' ? 'generate_qr' : 'generate_guest';
    document.getElementById('guestModalTitle').textContent = type === 'qr' ? 'Generate QR Code' : 'Generate Guest Link';
}

function loadGuestCourses() {
    const collegeId = document.getElementById('guest_college').value;
    const select = document.getElementById('guest_course');
    select.innerHTML = '<option value="">Loading...</option>'; select.disabled = true;
    document.getElementById('guest_batch_id').innerHTML = '<option value="">Select Course first</option>'; document.getElementById('guest_batch_id').disabled = true;
    if (!collegeId) { select.innerHTML = '<option value="">Select College</option>'; return; }
    fetch(API_URL + '/get_courses.php?college_id=' + collegeId)
        .then(r => r.json()).then(data => {
            select.innerHTML = '<option value="">Select Course</option>';
            data.forEach(c => { select.innerHTML += '<option value="' + c.id + '">' + c.name + '</option>'; });
            select.disabled = false;
        });
}

function loadGuestBatches() {
    const courseId = document.getElementById('guest_course').value;
    const select = document.getElementById('guest_batch_id');
    select.innerHTML = '<option value="">Loading...</option>'; select.disabled = true;
    if (!courseId) { select.innerHTML = '<option value="">Select Course first</option>'; return; }
    fetch(API_URL + '/get_batches.php?course_id=' + courseId)
        .then(r => r.json()).then(data => {
            select.innerHTML = '<option value="">Select Batch</option>';
            data.forEach(b => { select.innerHTML += '<option value="' + b.id + '">' + b.name + '</option>'; });
            select.disabled = false;
        });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
