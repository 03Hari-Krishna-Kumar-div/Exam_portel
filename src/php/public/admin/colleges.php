<?php
$pageTitle = 'Manage Colleges';
require_once __DIR__ . '/../../includes/admin_header.php';

$pdo = getDB();
$message = '';
$error = '';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
$canManage = in_array($adminRole, ['super_admin', 'platform_admin'], true);

$NAAC_GRADES = ['None', 'A++', 'A+', 'A', 'B+', 'B', 'C'];

/** Sanitize a comma-separated stream input into a unique list. */
function parseStreamsInput($raw): array {
    $out = [];
    foreach (explode(',', (string)$raw) as $s) {
        $s = trim($s);
        if ($s !== '' && !in_array($s, $out, true)) $out[] = $s;
    }
    return $out;
}

// Handle Add / Edit / Delete(archive) / Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if (in_array($action, ['add', 'edit'], true)) {
        if (!$canManage) { $error = 'Permission denied.'; }
        else {
            $name  = trim($_POST['name'] ?? '');
            $nick  = trim($_POST['nick_name'] ?? '');
            if ($name === '' || $nick === '') {
                $error = 'College name and nick name are required.';
            } else {
                $streams = parseStreamsInput($_POST['streams'] ?? '');
                $naacChecked = isset($_POST['accreditation_naac']) ? 1 : 0;
                $naacGrade = $naacChecked ? (trim($_POST['naac_grade'] ?? '') ?: null) : null;
                $autonomous = trim($_POST['autonomous'] ?? '');
                $autonomous = ($autonomous === '') ? null : $autonomous;

                $fields = [
                    'established_year'      => !empty($_POST['established_year']) ? (int)$_POST['established_year'] : null,
                    'website'               => trim($_POST['website'] ?? '') ?: null,
                    'email'                 => trim($_POST['email'] ?? '') ?: null,
                    'phone'                 => trim($_POST['phone'] ?? '') ?: null,
                    'address'               => trim($_POST['address'] ?? '') ?: null,
                    'country'               => trim($_POST['country'] ?? '') ?: null,
                    'state'                 => trim($_POST['state'] ?? '') ?: null,
                    'district'              => trim($_POST['district'] ?? '') ?: null,
                    'city'                  => trim($_POST['city'] ?? '') ?: null,
                    'pincode'               => trim($_POST['pincode'] ?? '') ?: null,
                    'logo'                  => trim($_POST['logo'] ?? '') ?: null,
                    'description'           => trim($_POST['description'] ?? '') ?: null,
                    'recognized_university' => trim($_POST['recognized_university'] ?? '') ?: null,
                    'affiliated_university' => trim($_POST['affiliated_university'] ?? '') ?: null,
                    'autonomous'            => $autonomous,
                    'accreditation_naac'    => $naacChecked,
                    'naac_grade'            => $naacGrade,
                    'accreditation_nba'     => isset($_POST['accreditation_nba']) ? 1 : 0,
                    'accreditation_aicte'   => isset($_POST['accreditation_aicte']) ? 1 : 0,
                    'accreditation_ugc'     => isset($_POST['accreditation_ugc']) ? 1 : 0,
                ];

                try {
                    if ($action === 'add') {
                        // Nick name uniqueness
                        $dup = $pdo->prepare("SELECT id FROM colleges WHERE nick_name = ?");
                        $dup->execute([$nick]);
                        if ($dup->fetch()) {
                            $error = 'A college with nick name "' . h($nick) . '" already exists.';
                        } else {
                            // college_code: manual or auto-generated (readonly field keeps it stable)
                            $code = trim($_POST['college_code'] ?? '');
                            if ($code === '') {
                                $maxId = (int)$pdo->query("SELECT MAX(id) AS max_id FROM colleges")->fetchColumn();
                                $code = 'COL' . str_pad((string)($maxId + 1), 6, '0', STR_PAD_LEFT);
                            }
                            $stmt = $pdo->prepare("
                                INSERT INTO colleges (
                                    college_code, name, nick_name, established_year,
                                    website, email, phone, address, country, state,
                                    district, city, pincode, logo, description,
                                    recognized_university, affiliated_university, autonomous,
                                    accreditation_naac, naac_grade, accreditation_nba,
                                    accreditation_aicte, accreditation_ugc, status
                                ) VALUES (
                                    :college_code, :name, :nick_name, :established_year,
                                    :website, :email, :phone, :address, :country, :state,
                                    :district, :city, :pincode, :logo, :description,
                                    :recognized_university, :affiliated_university, :autonomous,
                                    :accreditation_naac, :naac_grade, :accreditation_nba,
                                    :accreditation_aicte, :accreditation_ugc, 'active'
                                )
                            ");
                            $stmt->execute(['college_code' => $code, 'name' => $name, 'nick_name' => $nick] + $fields);
                            $collegeId = (int)$pdo->lastInsertId();

                            if (!empty($streams)) {
                                $si = $pdo->prepare("INSERT INTO college_streams (college_id, stream_name) VALUES (?, ?)");
                                foreach ($streams as $s) $si->execute([$collegeId, $s]);
                            }
                            $message = 'College "' . h($name) . '" added successfully.';
                        }
                    } else {
                        $id = (int)($_POST['id'] ?? 0);
                        if ($id <= 0) { $error = 'Invalid college id.'; }
                        else {
                            // Nick name uniqueness (excluding self)
                            $dup = $pdo->prepare("SELECT id FROM colleges WHERE nick_name = ? AND id != ?");
                            $dup->execute([$nick, $id]);
                            if ($dup->fetch()) {
                                $error = 'A college with nick name "' . h($nick) . '" already exists.';
                            } else {
                                $pdo->beginTransaction();
                                $stmt = $pdo->prepare("
                                    UPDATE colleges SET
                                        name = :name, nick_name = :nick_name, established_year = :established_year,
                                        website = :website, email = :email, phone = :phone, address = :address,
                                        country = :country, state = :state, district = :district, city = :city,
                                        pincode = :pincode, logo = :logo, description = :description,
                                        recognized_university = :recognized_university,
                                        affiliated_university = :affiliated_university, autonomous = :autonomous,
                                        accreditation_naac = :accreditation_naac, naac_grade = :naac_grade,
                                        accreditation_nba = :accreditation_nba,
                                        accreditation_aicte = :accreditation_aicte,
                                        accreditation_ugc = :accreditation_ugc
                                    WHERE id = :id
                                ");
                                $stmt->execute(['id' => $id, 'name' => $name, 'nick_name' => $nick] + $fields);

                                // Sync streams (replace)
                                $pdo->prepare("DELETE FROM college_streams WHERE college_id = ?")->execute([$id]);
                                if (!empty($streams)) {
                                    $si = $pdo->prepare("INSERT INTO college_streams (college_id, stream_name) VALUES (?, ?)");
                                    foreach ($streams as $s) $si->execute([$id, $s]);
                                }
                                $pdo->commit();
                                $message = 'College "' . h($name) . '" updated successfully.';
                            }
                        }
                    }
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    error_log('College save failed: ' . $e->getMessage());
                    $error = 'Failed to save college: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        // Archive instead of hard delete — courses, batches and students are retained.
        if (!$canManage) { $error = 'Permission denied.'; }
        else {
            $stmt = $pdo->prepare("UPDATE colleges SET status = 'archived' WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $message = 'College archived. Its courses, batches and students are retained.';
        }
    } elseif ($action === 'restore' && !empty($_POST['id'])) {
        if (!$canManage) { $error = 'Permission denied.'; }
        else {
            $stmt = $pdo->prepare("UPDATE colleges SET status = 'active' WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $message = 'College restored successfully.';
        }
    }
}

// Search filter (GET) + archived toggle
$search = trim($_GET['search'] ?? '');
$showArchived = (($_GET['show'] ?? '') === 'archived');
$archivedCount = (int)$pdo->query("SELECT COUNT(*) FROM colleges WHERE status = 'archived'")->fetchColumn();

$sql = "SELECT c.*,
           (SELECT COUNT(*) FROM courses WHERE college_id = c.id) AS course_count,
           (SELECT COUNT(*) FROM batches b JOIN courses co ON co.id = b.course_id WHERE co.college_id = c.id) AS batch_count,
           (SELECT COUNT(*) FROM students s JOIN batches b ON b.id = s.batch_id JOIN courses co ON co.id = b.course_id WHERE co.college_id = c.id) AS student_count,
           (SELECT GROUP_CONCAT(stream_name ORDER BY stream_name SEPARATOR ', ') FROM college_streams cs WHERE cs.college_id = c.id) AS streams_csv
        FROM colleges c";
$where = [];
$params = [];
if ($showArchived) {
    $where[] = "c.status = 'archived'";
} else {
    $where[] = "c.status = 'active'";
}
if ($search !== '') {
    $where[] = "(c.name LIKE ? OR c.nick_name LIKE ? OR c.address LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}
if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY c.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$colleges = $stmt->fetchAll();

// Next auto college code for the Add modal
$addCode = (function () use ($pdo) {
    $maxId = (int)$pdo->query("SELECT MAX(id) AS max_id FROM colleges")->fetchColumn();
    return 'COL' . str_pad((string)($maxId + 1), 6, '0', STR_PAD_LEFT);
})();
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

    <?php if ($error): ?>
        <div class="alert alert-error" style="margin:0 var(--space-5) var(--space-4);">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path d="M10 2a8 8 0 1 1 0 16 8 8 0 0 1 0-16zm0 1a7 7 0 1 0 0 14 7 7 0 0 0 0-14zm0 9.5a.75.75 0 1 1 0 1.5.75.75 0 0 1 0-1.5zM10 6a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0v-4A.5.5 0 0 1 10 6z"/></svg>
            <span><?= h($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success" style="margin:0 var(--space-5) var(--space-4);">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg>
            <span><?= h($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($showArchived): ?>
        <div class="alert" style="margin:0 var(--space-5) var(--space-4);display:flex;gap:var(--space-2);align-items:center;">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path d="M3 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4zm1.5 4.5h11l-.7 8.1a1.5 1.5 0 0 1-1.5 1.4H6.7a1.5 1.5 0 0 1-1.5-1.4l-.7-8.1z"/></svg>
            <span style="font-size:var(--fs-13);">Archive keeps all courses, batches and students intact. You can restore archived colleges any time.</span>
        </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="filter-bar">
        <form method="GET" class="form-inline">
            <?php if ($showArchived): ?><input type="hidden" name="show" value="archived"><?php endif; ?>
            <div class="search-box-wrapper">
                <span class="search-box-icon"><?= icon('search', 15) ?></span>
                <input class="form-input search-box-input" type="text" name="search"
                       placeholder="Search college names, nick names, addresses..." value="<?= h($search) ?>" style="max-width:300px;padding-left:32px;">
            </div>
            <button class="btn btn-sm btn-secondary" type="submit">Search</button>
            <?php if ($search !== ''): ?>
                <a href="colleges.php<?= $showArchived ? '?show=archived' : '' ?>" class="btn btn-sm btn-ghost">Clear</a>
            <?php endif; ?>
            <?php if ($showArchived): ?>
                <a href="colleges.php" class="btn btn-sm btn-ghost">Show Active</a>
            <?php elseif ($archivedCount > 0): ?>
                <a href="colleges.php?show=archived" class="btn btn-sm btn-ghost">Show Archived (<?= $archivedCount ?>)</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Streams</th>
                    <th>Courses</th>
                    <th>Created</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($colleges)): ?>
                    <tr><td colspan="7" class="text-center" style="padding:32px;color:var(--gray-50);">
                        <?php if ($search !== ''): ?>No colleges match your search.<?php elseif ($showArchived): ?>No archived colleges.<?php else: ?>No colleges registered yet.<?php endif; ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($colleges as $c):
                        $editData = json_encode([
                            'id' => (int)$c['id'],
                            'name' => $c['name'],
                            'nick_name' => $c['nick_name'] ?? '',
                            'college_code' => $c['college_code'] ?? '',
                            'established_year' => $c['established_year'] ?? '',
                            'website' => $c['website'] ?? '',
                            'email' => $c['email'] ?? '',
                            'phone' => $c['phone'] ?? '',
                            'address' => $c['address'] ?? '',
                            'country' => $c['country'] ?? '',
                            'state' => $c['state'] ?? '',
                            'district' => $c['district'] ?? '',
                            'city' => $c['city'] ?? '',
                            'pincode' => $c['pincode'] ?? '',
                            'logo' => $c['logo'] ?? '',
                            'description' => $c['description'] ?? '',
                            'recognized_university' => $c['recognized_university'] ?? '',
                            'affiliated_university' => $c['affiliated_university'] ?? '',
                            'autonomous' => $c['autonomous'] ?? '',
                            'accreditation_naac' => (int)($c['accreditation_naac'] ?? 0),
                            'naac_grade' => $c['naac_grade'] ?? '',
                            'accreditation_nba' => (int)($c['accreditation_nba'] ?? 0),
                            'accreditation_aicte' => (int)($c['accreditation_aicte'] ?? 0),
                            'accreditation_ugc' => (int)($c['accreditation_ugc'] ?? 0),
                            'streams' => $c['streams_csv'] ?? '',
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
                    ?>
                    <tr>
                        <td class="text-muted"><?= $c['id'] ?></td>
                        <td>
                            <strong><?= h($c['name']) ?></strong>
                            <?php if (!empty($c['nick_name'])): ?><span class="text-sm text-muted" style="margin-left:6px;">(<?= h($c['nick_name']) ?>)</span><?php endif; ?>
                            <?php if (($c['status'] ?? '') === 'archived'): ?><span class="badge badge-warning" style="margin-left:6px;">Archived</span><?php endif; ?>
                        </td>
                        <td class="text-sm text-muted"><?= h($c['address'] ?: '—') ?></td>
                        <td class="text-sm"><?= h($c['streams_csv'] ?: '—') ?></td>
                        <td><span class="badge badge-active"><?= $c['course_count'] ?></span></td>
                        <td class="text-sm text-muted"><?= formatDateTime($c['created_at']) ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-ghost" data-json='<?= h($editData) ?>' onclick="editCollege(this)">Edit</button>
                            <?php if (($c['status'] ?? '') === 'archived'): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Restore this college to active status?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-ghost">Restore</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Archive this college? Its courses, batches and students will be retained (not deleted).')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Archive</button>
                            </form>
                            <?php endif; ?>
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
    <div class="modal" style="max-width:760px;">
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
                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin-bottom:var(--space-3);">Basic Information</div>
                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label class="form-label" for="add_name">College Name <span class="text-warning">*</span></label>
                        <input class="form-input" type="text" id="add_name" name="name" required placeholder="e.g. Indian Institute of Technology" maxlength="255">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_college_code">College ID</label>
                        <input class="form-input" type="text" id="add_college_code" name="college_code" readonly value="<?= h($addCode) ?>" style="background:var(--gray-5);color:var(--gray-60);cursor:not-allowed;">
                        <div class="form-hint">Auto-generated, unique identifier</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_nick_name">College Nick Name <span class="text-warning">*</span></label>
                        <input class="form-input" type="text" id="add_nick_name" name="nick_name" required placeholder="e.g. IIT Madras" maxlength="100">
                        <div class="form-hint">Unique short name. Used for batch codes.</div>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_established_year">Established Year</label>
                        <input class="form-input" type="number" id="add_established_year" name="established_year" min="1800" max="<?= date('Y') ?>" placeholder="e.g. 2005">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_website">Website</label>
                        <input class="form-input" type="url" id="add_website" name="website" placeholder="https://www.example.edu">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_email">Email</label>
                        <input class="form-input" type="email" id="add_email" name="email" placeholder="admin@college.edu">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_phone">Phone</label>
                        <input class="form-input" type="tel" id="add_phone" name="phone" placeholder="+91-XXXXXXXXXX">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_address">Address</label>
                    <textarea class="form-textarea" id="add_address" name="address" rows="2" placeholder="Street, building, area"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_country">Country</label>
                        <input class="form-input" type="text" id="add_country" name="country" value="India" placeholder="India">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_state">State</label>
                        <input class="form-input" type="text" id="add_state" name="state" placeholder="e.g. Karnataka">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_district">District</label>
                        <input class="form-input" type="text" id="add_district" name="district" placeholder="e.g. Bangalore Urban">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_city">City</label>
                        <input class="form-input" type="text" id="add_city" name="city" placeholder="e.g. Bangalore">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_pincode">Pincode</label>
                        <input class="form-input" type="text" id="add_pincode" name="pincode" placeholder="e.g. 560001" maxlength="10">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_logo">Logo URL</label>
                        <input class="form-input" type="text" id="add_logo" name="logo" placeholder="URL to college logo image">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_description">Description</label>
                    <textarea class="form-textarea" id="add_description" name="description" rows="2" placeholder="Brief description about the college"></textarea>
                </div>

                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin:var(--space-4) 0 var(--space-3);">University &amp; Accreditation</div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_recognized_university">Recognized University</label>
                        <input class="form-input" type="text" id="add_recognized_university" name="recognized_university" placeholder="e.g. Visvesvaraya Technological University">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_affiliated_university">Affiliated University</label>
                        <input class="form-input" type="text" id="add_affiliated_university" name="affiliated_university" placeholder="e.g. Bangalore University">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="add_autonomous">Autonomous</label>
                        <select class="form-select" id="add_autonomous" name="autonomous">
                            <option value="">—</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:0 0 auto;">
                        <label class="form-checkbox">
                            <input type="checkbox" name="accreditation_naac" value="1" id="add_accreditation_naac" onchange="toggleNaacGrade('add_')">
                            <span>NAAC Accredited</span>
                        </label>
                    </div>
                    <div class="form-group" id="add_naac_grade_group" style="flex:1;display:none;">
                        <label class="form-label" for="add_naac_grade">NAAC Grade</label>
                        <select class="form-select" id="add_naac_grade" name="naac_grade">
                            <option value="">Select NAAC Grade</option>
                            <?php foreach ($NAAC_GRADES as $g): ?>
                            <option value="<?= h($g) ?>"><?= h($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top:var(--space-3);">
                    <label class="form-checkbox">
                        <input type="checkbox" name="accreditation_nba" value="1" id="add_accreditation_nba">
                        <span>NBA Accredited</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="checkbox" name="accreditation_aicte" value="1" id="add_accreditation_aicte">
                        <span>AICTE Approved</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="checkbox" name="accreditation_ugc" value="1" id="add_accreditation_ugc">
                        <span>UGC Recognized</span>
                    </label>
                </div>

                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin:var(--space-4) 0 var(--space-3);">Streams</div>
                <div class="form-group">
                    <label class="form-label" for="add_streams">Streams</label>
                    <input class="form-input" type="text" id="add_streams" name="streams" placeholder="e.g. Engineering, Management, Computer Applications">
                    <div class="form-hint">Separate multiple streams with commas. The full wizard also lets you configure streams per batch.</div>
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
    <div class="modal" style="max-width:760px;">
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
                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin-bottom:var(--space-3);">Basic Information</div>
                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label class="form-label" for="edit_name">College Name <span class="text-warning">*</span></label>
                        <input class="form-input" type="text" id="edit_name" name="name" required maxlength="255">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_college_code">College ID</label>
                        <input class="form-input" type="text" id="edit_college_code" readonly style="background:var(--gray-5);color:var(--gray-60);cursor:not-allowed;">
                        <div class="form-hint">Auto-generated, unique identifier</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_nick_name">College Nick Name <span class="text-warning">*</span></label>
                        <input class="form-input" type="text" id="edit_nick_name" name="nick_name" required maxlength="100">
                        <div class="form-hint">Unique short name. Used for batch codes.</div>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_established_year">Established Year</label>
                        <input class="form-input" type="number" id="edit_established_year" name="established_year" min="1800" max="<?= date('Y') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_website">Website</label>
                        <input class="form-input" type="url" id="edit_website" name="website">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_email">Email</label>
                        <input class="form-input" type="email" id="edit_email" name="email">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_phone">Phone</label>
                        <input class="form-input" type="tel" id="edit_phone" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_address">Address</label>
                    <textarea class="form-textarea" id="edit_address" name="address" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_country">Country</label>
                        <input class="form-input" type="text" id="edit_country" name="country">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_state">State</label>
                        <input class="form-input" type="text" id="edit_state" name="state">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_district">District</label>
                        <input class="form-input" type="text" id="edit_district" name="district">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_city">City</label>
                        <input class="form-input" type="text" id="edit_city" name="city">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_pincode">Pincode</label>
                        <input class="form-input" type="text" id="edit_pincode" name="pincode" maxlength="10">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_logo">Logo URL</label>
                        <input class="form-input" type="text" id="edit_logo" name="logo">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_description">Description</label>
                    <textarea class="form-textarea" id="edit_description" name="description" rows="2"></textarea>
                </div>

                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin:var(--space-4) 0 var(--space-3);">University &amp; Accreditation</div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_recognized_university">Recognized University</label>
                        <input class="form-input" type="text" id="edit_recognized_university" name="recognized_university">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_affiliated_university">Affiliated University</label>
                        <input class="form-input" type="text" id="edit_affiliated_university" name="affiliated_university">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="edit_autonomous">Autonomous</label>
                        <select class="form-select" id="edit_autonomous" name="autonomous">
                            <option value="">—</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:0 0 auto;">
                        <label class="form-checkbox">
                            <input type="checkbox" name="accreditation_naac" value="1" id="edit_accreditation_naac" onchange="toggleNaacGrade('edit_')">
                            <span>NAAC Accredited</span>
                        </label>
                    </div>
                    <div class="form-group" id="edit_naac_grade_group" style="flex:1;display:none;">
                        <label class="form-label" for="edit_naac_grade">NAAC Grade</label>
                        <select class="form-select" id="edit_naac_grade" name="naac_grade">
                            <option value="">Select NAAC Grade</option>
                            <?php foreach ($NAAC_GRADES as $g): ?>
                            <option value="<?= h($g) ?>"><?= h($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top:var(--space-3);">
                    <label class="form-checkbox">
                        <input type="checkbox" name="accreditation_nba" value="1" id="edit_accreditation_nba">
                        <span>NBA Accredited</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="checkbox" name="accreditation_aicte" value="1" id="edit_accreditation_aicte">
                        <span>AICTE Approved</span>
                    </label>
                    <label class="form-checkbox">
                        <input type="checkbox" name="accreditation_ugc" value="1" id="edit_accreditation_ugc">
                        <span>UGC Recognized</span>
                    </label>
                </div>

                <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin:var(--space-4) 0 var(--space-3);">Streams</div>
                <div class="form-group">
                    <label class="form-label" for="edit_streams">Streams</label>
                    <input class="form-input" type="text" id="edit_streams" name="streams" placeholder="e.g. Engineering, Management, Computer Applications">
                    <div class="form-hint">Separate multiple streams with commas. Saving replaces the current stream list.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Add Button (opens modal) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var headerRight = document.querySelector('.dashboard-header-right');
    if (headerRight) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-secondary btn-sm';
        btn.id = 'quickAddBtn';
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Quick Add';
        btn.style.marginRight = '8px';
        btn.onclick = function () { openModal('addModal'); };
        headerRight.insertBefore(btn, headerRight.firstChild);
    }
});
</script>
<?php endif; ?>

<script>
function openModal(id) { var el = document.getElementById(id); if (!el) return; el.style.display = 'flex'; el.classList.add('open'); }
function closeModal(id) { var el = document.getElementById(id); if (!el) return; el.classList.remove('open'); el.style.display = 'none'; }
function toggleNaacGrade(prefix) {
    var cb = document.getElementById(prefix + 'accreditation_naac');
    var g = document.getElementById(prefix + 'naac_grade_group');
    if (cb && g) g.style.display = cb.checked ? 'block' : 'none';
}
function editCollege(btn) {
    var d = JSON.parse(btn.getAttribute('data-json'));
    function setVal(id, val) { var el = document.getElementById(id); if (el) el.value = (val === null || val === undefined) ? '' : val; }
    function setChecked(id, val) { var el = document.getElementById(id); if (el) el.checked = !!val; }
    setVal('edit_id', d.id);
    setVal('edit_name', d.name);
    setVal('edit_nick_name', d.nick_name);
    setVal('edit_college_code', d.college_code);
    setVal('edit_established_year', d.established_year);
    setVal('edit_website', d.website);
    setVal('edit_email', d.email);
    setVal('edit_phone', d.phone);
    setVal('edit_address', d.address);
    setVal('edit_country', d.country);
    setVal('edit_state', d.state);
    setVal('edit_district', d.district);
    setVal('edit_city', d.city);
    setVal('edit_pincode', d.pincode);
    setVal('edit_logo', d.logo);
    setVal('edit_description', d.description);
    setVal('edit_recognized_university', d.recognized_university);
    setVal('edit_affiliated_university', d.affiliated_university);
    setVal('edit_autonomous', d.autonomous);
    setChecked('edit_accreditation_naac', d.accreditation_naac);
    setVal('edit_naac_grade', d.naac_grade);
    setChecked('edit_accreditation_nba', d.accreditation_nba);
    setChecked('edit_accreditation_aicte', d.accreditation_aicte);
    setChecked('edit_accreditation_ugc', d.accreditation_ugc);
    setVal('edit_streams', d.streams);
    toggleNaacGrade('edit_');
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