<?php
$pageTitle = 'Manage Batches';
require_once __DIR__ . '/../../includes/admin_header.php';

$pdo = getDB();
$message = '';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && !empty($_POST['name']) && !empty($_POST['course_id'])) {
        $stmt = $pdo->prepare("INSERT INTO batches (course_id, name) VALUES (?, ?)");
        $stmt->execute([(int)$_POST['course_id'], trim($_POST['name'])]);
        $message = 'Batch added successfully.';
    } elseif ($action === 'edit' && !empty($_POST['id']) && !empty($_POST['name'])) {
        $stmt = $pdo->prepare("UPDATE batches SET name = ?, course_id = ? WHERE id = ?");
        $stmt->execute([trim($_POST['name']), (int)$_POST['course_id'], (int)$_POST['id']]);
        $message = 'Batch updated successfully.';
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM batches WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        $message = 'Batch deleted successfully.';
    }
}

// Get colleges for filter
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name")->fetchAll();

// Get selected filter
$filterCollege = (int)($_GET['college_id'] ?? 0);
$filterCourse = (int)($_GET['course_id'] ?? 0);

// Build query
$sql = "
    SELECT b.*, c.name AS course_name, cl.name AS college_name,
           (SELECT COUNT(*) FROM students WHERE batch_id = b.id) AS student_count
    FROM batches b
    JOIN courses c ON c.id = b.course_id
    JOIN colleges cl ON cl.id = c.college_id
";
$params = [];
$where = [];
if ($filterCourse > 0) {
    $where[] = "b.course_id = ?";
    $params[] = $filterCourse;
} elseif ($filterCollege > 0) {
    $where[] = "c.college_id = ?";
    $params[] = $filterCollege;
}
if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY cl.name, c.name, b.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$batches = $stmt->fetchAll();
?>

<div class="dashboard-header" style="margin-bottom:var(--space-4);">
    <div class="dashboard-header-left">
        <h1>Batches</h1>
        <p class="dashboard-subtitle">Manage student groups by course</p>
    </div>
    <div class="dashboard-header-right">
        <button class="btn btn-primary btn-sm" onclick="openModal('addModal')">+ Add Batch</button>
    </div>
</div>

<div class="card-flat">

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin:0 var(--space-5) var(--space-4);">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg>
            <span><?= h($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Filter -->
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
            <?php if ($filterCollege > 0 || $filterCourse > 0): ?>
                <a href="batches.php" class="btn btn-sm btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Batch Name</th>
                    <th>Course</th>
                    <th>College</th>
                    <th>Students</th>
                    <th>Created</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr><td colspan="7" class="text-center" style="padding:32px;color:var(--gray-50);">No batches found.</td></tr>
                <?php else: ?>
                    <?php foreach ($batches as $b): ?>
                    <tr>
                        <td class="text-muted"><?= $b['id'] ?></td>
                        <td><strong><?= h($b['name']) ?></strong></td>
                        <td><?= h($b['course_name']) ?></td>
                        <td class="text-sm text-muted"><?= h($b['college_name']) ?></td>
                        <td><span class="badge badge-active"><?= $b['student_count'] ?></span></td>
                        <td class="text-sm text-muted"><?= formatDateTime($b['created_at']) ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-ghost"
                                onclick="editBatch(<?= $b['id'] ?>, <?= $b['course_id'] ?>, '<?= h(addslashes($b['name'])) ?>')">Edit</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this batch?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal" style="display:none;">
    <div class="modal">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
                <h3>Add Batch</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4.09 4.09a.5.5 0 0 1 .7 0L10 9.29l5.2-5.2a.5.5 0 0 1 .7.7L10.7 10l5.2 5.2a.5.5 0 0 1-.7.7L10 10.7l-5.2 5.2a.5.5 0 0 1-.7-.7L9.29 10 4.09 4.8a.5.5 0 0 1 0-.7z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>College *</label>
                    <select class="form-select" id="add_college" onchange="loadAddCourses()" required>
                        <option value="">Select College</option>
                        <?php foreach ($colleges as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= h($cl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_course_id">Course *</label>
                    <select class="form-select" id="add_course_id" name="course_id" required disabled>
                        <option value="">Select College first</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_name">Batch Name *</label>
                    <input class="form-input" type="text" id="add_name" name="name" required placeholder="e.g. 2024 Batch A">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal" style="display:none;">
    <div class="modal">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h3>Edit Batch</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4.09 4.09a.5.5 0 0 1 .7 0L10 9.29l5.2-5.2a.5.5 0 0 1 .7.7L10.7 10l5.2 5.2a.5.5 0 0 1-.7.7L10 10.7l-5.2 5.2a.5.5 0 0 1-.7-.7L9.29 10 4.09 4.8a.5.5 0 0 1 0-.7z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>College *</label>
                    <select class="form-select" id="edit_college" onchange="loadEditCourses()" required>
                        <?php foreach ($colleges as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= h($cl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_course_id">Course *</label>
                    <select class="form-select" id="edit_course_id" name="course_id" required>
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_name">Batch Name *</label>
                    <input class="form-input" type="text" id="edit_name" name="name" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function loadAddCourses() {
    const collegeId = document.getElementById('add_college').value;
    const select = document.getElementById('add_course_id');
    select.innerHTML = '<option value="">Loading...</option>';
    select.disabled = true;
    if (!collegeId) { select.innerHTML = '<option value="">Select College first</option>'; return; }
    fetch('/test-platform/src/php/api/get_courses.php?college_id=' + collegeId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Select Course</option>';
            data.forEach(c => { select.innerHTML += '<option value="' + c.id + '">' + c.name + '</option>'; });
            select.disabled = false;
        })
        .catch(() => { select.innerHTML = '<option value="">Error</option>'; });
}

function loadEditCourses() {
    const collegeId = document.getElementById('edit_college').value;
    const select = document.getElementById('edit_course_id');
    select.innerHTML = '<option value="">Loading...</option>';
    select.disabled = true;
    if (!collegeId) { select.innerHTML = '<option value="">Select College first</option>'; return; }
    fetch('/test-platform/src/php/api/get_courses.php?college_id=' + collegeId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Select Course</option>';
            data.forEach(c => { select.innerHTML += '<option value="' + c.id + '">' + c.name + '</option>'; });
            select.disabled = false;
        })
        .catch(() => { select.innerHTML = '<option value="">Error</option>'; });
}

function editBatch(id, courseId, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    // Load the college and course for this batch
    fetch('/test-platform/src/php/api/get_course_college.php?course_id=' + courseId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('edit_college').value = data.college_id;
            loadEditCourses();
            setTimeout(() => { document.getElementById('edit_course_id').value = courseId; }, 300);
        });
    openModal('editModal');
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
