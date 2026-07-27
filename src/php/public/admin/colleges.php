<?php
$pageTitle = 'Manage Colleges';
require_once __DIR__ . '/../../includes/admin_header.php';

$pdo = getDB();
$message = '';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$canManage = in_array($adminRole, ['super_admin', 'platform_admin'], true);

// Handle Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && !empty($_POST['name'])) {
        if (!$canManage) { $message = 'Permission denied.'; }
        else {
            $stmt = $pdo->prepare("INSERT INTO colleges (name, address) VALUES (?, ?)");
            $stmt->execute([trim($_POST['name']), trim($_POST['address'] ?? '')]);
            $message = 'College added successfully.';
        }
    } elseif ($action === 'edit' && !empty($_POST['id']) && !empty($_POST['name'])) {
        if (!$canManage) { $message = 'Permission denied.'; }
        else {
            $stmt = $pdo->prepare("UPDATE colleges SET name = ?, address = ? WHERE id = ?");
            $stmt->execute([trim($_POST['name']), trim($_POST['address'] ?? ''), (int)$_POST['id']]);
            $message = 'College updated successfully.';
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        if (!$canManage) { $message = 'Permission denied.'; }
        else {
            $stmt = $pdo->prepare("DELETE FROM colleges WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $message = 'College deleted successfully.';
        }
    }
}

$colleges = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM courses WHERE college_id = c.id) AS course_count FROM colleges c ORDER BY c.name")->fetchAll();
?>

<div class="dashboard-header" style="margin-bottom:var(--space-4);">
    <div class="dashboard-header-left">
        <h1>Colleges</h1>
        <p class="dashboard-subtitle">Manage all registered institutions</p>
    </div>
    <div class="dashboard-header-right">
        <?php if ($canManage): ?>
        <a href="<?= BASE_URL ?>/admin/college_create.php" class="btn btn-primary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Create College
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card-flat">

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin:0 var(--space-5) var(--space-4);">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg>
            <span><?= h($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Courses</th>
                    <th>Created</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($colleges)): ?>
                    <tr><td colspan="6" class="text-center" style="padding:32px;color:var(--gray-50);">No colleges registered yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($colleges as $c): ?>
                    <tr>
                        <td class="text-muted"><?= $c['id'] ?></td>
                        <td><strong><?= h($c['name']) ?></strong></td>
                        <td class="text-sm text-muted"><?= h($c['address'] ?: '—') ?></td>
                        <td><span class="badge badge-active"><?= $c['course_count'] ?></span></td>
                        <td class="text-sm text-muted"><?= formatDateTime($c['created_at']) ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-ghost" onclick="editCollege(<?= $c['id'] ?>, '<?= h(addslashes($c['name'])) ?>', '<?= h(addslashes($c['address'] ?? '')) ?>')">Edit</button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this college? This will also delete all courses and batches under it.')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
                <h3>Add College</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4.09 4.09a.5.5 0 0 1 .7 0L10 9.29l5.2-5.2a.5.5 0 0 1 .7.7L10.7 10l5.2 5.2a.5.5 0 0 1-.7.7L10 10.7l-5.2 5.2a.5.5 0 0 1-.7-.7L9.29 10 4.09 4.8a.5.5 0 0 1 0-.7z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_name">College Name *</label>
                    <input class="form-input" type="text" id="add_name" name="name" required placeholder="e.g. Indian Institute of Technology">
                </div>
                <div class="form-group">
                    <label for="add_address">Address</label>
                    <textarea class="form-textarea" id="add_address" name="address" placeholder="Optional address"></textarea>
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
                <h3>Edit College</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4.09 4.09a.5.5 0 0 1 .7 0L10 9.29l5.2-5.2a.5.5 0 0 1 .7.7L10.7 10l5.2 5.2a.5.5 0 0 1-.7.7L10 10.7l-5.2 5.2a.5.5 0 0 1-.7-.7L9.29 10 4.09 4.8a.5.5 0 0 1 0-.7z"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">College Name *</label>
                    <input class="form-input" type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <textarea class="form-textarea" id="edit_address" name="address"></textarea>
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
function editCollege(id, name, address) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_address').value = address;
    openModal('editModal');
}
// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
