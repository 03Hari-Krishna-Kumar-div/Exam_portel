<?php
$pageTitle = 'Assessment Studio';
require_once __DIR__ . '/../../includes/admin_header.php';

$pdo = getDB();
$message = '';
$currentStep = isset($_GET['step']) ? min(3, max(1, (int)$_GET['step'])) : 1;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    // ─── Create Draft Assessment ────────────────────────────────
    if ($action === 'create_draft') {
        $stmt = $pdo->prepare("
            INSERT INTO tests (batch_id, title, description, duration_minutes, status, shuffle_questions, created_by,
                               passing_marks, negative_marking, instructions)
            VALUES (?, ?, ?, ?, 'upcoming', ?, ?, ?, ?, ?)
        ");
        $shuffle = !empty($_POST['shuffle_questions']) ? 1 : 0;
        $stmt->execute([
            (int)$_POST['batch_id'],
            trim($_POST['title']),
            trim($_POST['description'] ?? ''),
            (int)$_POST['duration_minutes'],
            $shuffle,
            $_SESSION['admin_id'],
            (int)($_POST['passing_marks'] ?? 0),
            (float)($_POST['negative_marking'] ?? 0),
            trim($_POST['instructions'] ?? ''),
        ]);
        $testId = $pdo->lastInsertId();
        $_SESSION['last_test_id'] = $testId;
        $message = 'Draft assessment created successfully.';
        // Redirect to continue editing
        redirect('/admin/assessment_studio.php?edit_test=' . $testId);
    }

    // ─── Update Draft ───────────────────────────────────────────
    elseif ($action === 'update_draft' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("
            UPDATE tests SET batch_id=?, title=?, description=?, duration_minutes=?,
                             shuffle_questions=?, passing_marks=?, negative_marking=?, instructions=?
            WHERE id=? AND status='upcoming'
        ");
        $stmt->execute([
            (int)$_POST['batch_id'], trim($_POST['title']), trim($_POST['description'] ?? ''),
            (int)$_POST['duration_minutes'], !empty($_POST['shuffle_questions']) ? 1 : 0,
            (int)($_POST['passing_marks'] ?? 0), (float)($_POST['negative_marking'] ?? 0),
            trim($_POST['instructions'] ?? ''),
            (int)$_POST['id'],
        ]);
        $message = 'Draft updated.';
    }

    // ─── Add Question ───────────────────────────────────────────
    elseif ($action === 'add_question' && !empty($_POST['test_id']) && !empty($_POST['question_text'])) {
        $testId = (int)$_POST['test_id'];
        $type = $_POST['question_type'] ?? 'mcq';
        $optionsJson = null;
        $correctAnswer = null;

        if ($type === 'mcq') {
            $options = [];
            $rawOptions = $_POST['options'] ?? '';
            if (is_string($rawOptions) && str_starts_with(trim($rawOptions), '[')) {
                $decoded = json_decode($rawOptions, true);
                if (is_array($decoded)) $options = $decoded;
            } elseif (is_string($rawOptions) && trim($rawOptions)) {
                $lines = explode("\n", $rawOptions);
                foreach ($lines as $i => $line) {
                    $line = trim($line);
                    if ($line) {
                        $options[] = ['key' => chr(65 + $i), 'text' => $line];
                    }
                }
            }
            if (!empty($options)) {
                $optionsJson = json_encode($options);
                $correctAnswer = $_POST['correct_answer'] ?? '';
            }
        }

        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $marks = (int)($_POST['marks'] ?? 1);

        $stmt = $pdo->prepare("
            INSERT INTO questions (test_id, type, question_text, options_json, correct_answer, marks, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$testId, $type, trim($_POST['question_text']), $optionsJson, $correctAnswer, $marks, $sortOrder]);
        $message = 'Question added successfully.';
    }

    // ─── Delete Question ────────────────────────────────────────
    elseif ($action === 'delete_question' && !empty($_POST['id'])) {
        $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([(int)$_POST['id']]);
        $message = 'Question deleted.';
    }

    // ─── CSV Import ─────────────────────────────────────────────
    elseif ($action === 'import_csv' && !empty($_POST['test_id']) && !empty($_FILES['csv_file']['tmp_name'])) {
        $testId = (int)$_POST['test_id'];
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle) {
            $imported = 0; $errors = []; $lineNum = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;
                if ($lineNum === 1 && preg_match('/question/i', $row[0] ?? '')) continue;
                $qText = trim($row[0] ?? '');
                $optA  = trim($row[1] ?? ''); $optB = trim($row[2] ?? '');
                $optC  = trim($row[3] ?? ''); $optD = trim($row[4] ?? '');
                $answer = strtoupper(trim($row[5] ?? ''));
                $marks = max(1, (int)($row[6] ?? 1));
                if (empty($qText)) { $errors[] = "Line $lineNum: empty question text"; continue; }
                if (empty($optA) || empty($optB)) { $errors[] = "Line $lineNum: need >=2 options"; continue; }
                if (!in_array($answer, ['A','B','C','D'])) { $errors[] = "Line $lineNum: invalid answer '$answer'"; continue; }
                $options = [];
                if ($optA) $options[] = ['key' => 'A', 'text' => $optA];
                if ($optB) $options[] = ['key' => 'B', 'text' => $optB];
                if ($optC) $options[] = ['key' => 'C', 'text' => $optC];
                if ($optD) $options[] = ['key' => 'D', 'text' => $optD];
                $pdo->prepare("INSERT INTO questions (test_id, type, question_text, options_json, correct_answer, marks, sort_order) VALUES (?, 'mcq', ?, ?, ?, ?, ?)")
                    ->execute([$testId, $qText, json_encode($options), $answer, $marks, $lineNum]);
                $imported++;
            }
            fclose($handle);
            $msg = "Imported $imported questions successfully.";
            if (!empty($errors)) $msg .= ' Warnings: ' . implode('; ', array_slice($errors, 0, 3));
            $message = $msg;
        } else {
            $message = 'Failed to open uploaded file.';
        }
    }

    // ─── Publish Assessment ─────────────────────────────────────
    elseif ($action === 'publish_now' && !empty($_POST['id'])) {
        $pdo->prepare("UPDATE tests SET status = 'active' WHERE id = ? AND status = 'upcoming'")->execute([(int)$_POST['id']]);
        $message = 'Assessment published and is now Live.';
        redirect('/admin/assessment_management.php?tab=live');
    }
    elseif ($action === 'schedule_publish' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE tests SET status = 'upcoming', start_time = ?, end_time = ? WHERE id = ? AND status = 'upcoming'");
        $stmt->execute([
            $_POST['start_time'] ?: null,
            $_POST['end_time'] ?: null,
            (int)$_POST['id'],
        ]);
        $message = 'Assessment scheduled.';
        redirect('/admin/assessment_management.php?tab=upcoming');
    }

    // ─── Delete Draft ───────────────────────────────────────────
    elseif ($action === 'delete_draft' && !empty($_POST['id'])) {
        $pdo->prepare("DELETE FROM tests WHERE id = ? AND status = 'upcoming'")->execute([(int)$_POST['id']]);
        $message = 'Draft deleted.';
    }
}

// ─── Get data ──────────────────────────────────────────────────
$batches = $pdo->query("
    SELECT b.id, b.name AS batch_name, c.name AS course_name, cl.name AS college_name
    FROM batches b
    JOIN courses c ON c.id = b.course_id
    JOIN colleges cl ON cl.id = c.college_id
    ORDER BY cl.name, c.name, b.name
")->fetchAll();

// Editing a draft?
$editTestId = (int)($_GET['edit_test'] ?? 0);
$editTest = null;
if ($editTestId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ? AND status = 'upcoming'");
    $stmt->execute([$editTestId]);
    $editTest = $stmt->fetch();
    if ($editTest) $currentStep = 2;
}

// Questions for editing
$questions = [];
if ($editTestId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY sort_order, id");
    $stmt->execute([$editTestId]);
    $questions = $stmt->fetchAll();
}

// Get draft assessments list
$drafts = $pdo->query("
    SELECT t.*, b.name AS batch_name, c.name AS course_name, cl.name AS college_name,
           (SELECT COUNT(*) FROM questions WHERE test_id = t.id) AS question_count
    FROM tests t
    JOIN batches b ON b.id = t.batch_id
    JOIN courses c ON c.id = b.course_id
    JOIN colleges cl ON cl.id = c.college_id
    WHERE t.status = 'upcoming' AND t.start_time IS NULL AND t.end_time IS NULL
    ORDER BY t.created_at DESC
")->fetchAll();

$showDrafts = isset($_GET['tab']) && $_GET['tab'] === 'drafts';
?>

<div style="max-width:960px;margin:0 auto;">

    <?php if ($message): ?>
        <div class="alert alert-success">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg>
            <span><?= h($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($showDrafts): ?>
        <!-- ═══════════════ DRAFTS LIST ═══════════════ -->
        <div style="margin-bottom:var(--space-4);">
            <h1 style="font-size:var(--fs-24);font-weight:600;margin-bottom:var(--space-1);">Draft Assessments</h1>
            <p class="text-muted" style="font-size:var(--fs-14);">Continue editing, preview, or publish your drafts.</p>
        </div>

        <?php if (empty($drafts)): ?>
            <div class="panel">
                <div class="panel-body">
                    <div class="empty-state">
                        <div class="empty-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1V3zm0 1H4a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-1H9a2 2 0 0 1-2-2V4zm2-1v5a1 1 0 0 0 1 1h5V4a1 1 0 0 0-1-1h-4z"/></svg></div>
                        <h3>No Draft Assessments</h3>
                        <p>Create a new assessment to get started.</p>
                        <a href="assessment_studio.php" class="btn btn-primary">Create Assessment</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="panel">
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Batch</th>
                                <th>Questions</th>
                                <th>Duration</th>
                                <th>Created</th>
                                <th class="actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($drafts as $d): ?>
                            <tr>
                                <td><strong><?= h($d['title']) ?></strong></td>
                                <td class="text-sm"><?= h($d['batch_name']) ?></td>
                                <td><span class="badge badge-active"><?= $d['question_count'] ?></span></td>
                                <td class="text-sm"><?= $d['duration_minutes'] ?> min</td>
                                <td class="text-sm text-muted"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="assessment_studio.php?edit_test=<?= $d['id'] ?>" class="btn btn-sm btn-ghost">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.5 2.5a1.5 1.5 0 0 1 2.12 0l1.88 1.88a1.5 1.5 0 0 1 0 2.12l-9.5 9.5a1.5 1.5 0 0 1-.7.4l-4.4 1.1a.5.5 0 0 1-.6-.6l1.1-4.4a1.5 1.5 0 0 1 .4-.7l9.5-9.5z"/></svg>
                                        Edit
                                    </a>
                                    <button class="btn btn-sm btn-primary" onclick="openPublishModal(<?= $d['id'] ?>, '<?= h($d['title']) ?>')">
                                        Publish
                                    </button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this draft?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_draft">
                                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($editTestId > 0 && $editTest): ?>
        <!-- ═══════════════ EDITING DRAFT — STEP 2 & 3 ═══════════════ -->

        <!-- Step indicator -->
        <div style="margin-bottom:var(--space-4);">
            <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-4);">
                <a href="assessment_studio.php" class="btn btn-sm btn-ghost">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.65 3.15a.5.5 0 0 0-.7-.7l-6.5 6.5a.5.5 0 0 0 0 .7l6.5 6.5a.5.5 0 0 0 .7-.7L3.2 10H17.5a.5.5 0 0 0 0-1H3.2l5.45-5.85z"/></svg>
                    Back to Studio
                </a>
                <span class="text-muted">/</span>
                <span style="font-weight:500;color:var(--gray-90);"><?= h($editTest['title']) ?></span>
            </div>

            <div class="wizard">
                <div class="wizard-steps">
                    <div class="wizard-step completed">
                        <span class="step-num"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg></span>
                        <span>Basic Information</span>
                    </div>
                    <div class="wizard-step active">
                        <span class="step-num">2</span>
                        <span>Question Builder</span>
                    </div>
                    <div class="wizard-step">
                        <span class="step-num">3</span>
                        <span>Review & Publish</span>
                    </div>
                </div>

                <div class="wizard-content">
                    <!-- Step 2: Question Builder -->
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-5);">
                            <div>
                                <h2 style="font-size:var(--fs-18);">Question Builder</h2>
                                <p class="text-muted text-sm">Add questions manually, import from CSV, or both.</p>
                            </div>
                            <div class="flex-center gap-2">
                                <span class="badge badge-active"><?= count($questions) ?> questions</span>
                            </div>
                        </div>

                        <!-- Manual Question Form -->
                        <div class="panel panel-flat" style="margin-bottom:var(--space-4);">
                            <div class="panel-header">
                                <h2 style="font-size:var(--fs-15);">Add Question Manually</h2>
                            </div>
                            <div class="panel-body">
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="add_question">
                                    <input type="hidden" name="test_id" value="<?= $editTestId ?>">

                                    <div class="form-row" style="margin-bottom:var(--space-3);">
                                        <div class="form-group">
                                            <label>Question Type</label>
                                            <select class="form-select" name="question_type" onchange="toggleQuestionType(this)">
                                                <option value="mcq">MCQ (Multiple Choice)</option>
                                                <option value="coding">Coding</option>
                                                <option value="explanation">Explanation</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Marks</label>
                                            <input class="form-input" type="number" name="marks" value="1" min="1" max="100">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Question Text</label>
                                        <textarea class="form-textarea" name="question_text" required placeholder="Type your question here..." style="min-height:80px;"></textarea>
                                    </div>

                                    <div id="mcqFields">
                                        <div class="form-group">
                                            <label>Options (one per line)</label>
                                            <textarea class="form-textarea" name="options_plain" id="optionsPlain" placeholder="Option A&#10;Option B&#10;Option C&#10;Option D" style="min-height:80px;"></textarea>
                                            <input type="hidden" name="options" id="optionsHidden">
                                        </div>
                                        <div class="form-group">
                                            <label>Correct Answer</label>
                                            <select class="form-select" name="correct_answer" id="correctAnswer">
                                                <option value="">Select correct answer</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                                <option value="C">C</option>
                                                <option value="D">D</option>
                                            </select>
                                        </div>
                                    </div>

                                    <input type="hidden" name="sort_order" value="<?= count($questions) + 1 ?>">

                                    <button type="submit" class="btn btn-primary">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a.5.5 0 0 1 .5.5v6h6a.5.5 0 0 1 0 1h-6v6a.5.5 0 0 1-1 0v-6h-6a.5.5 0 0 1 0-1h6v-6A.5.5 0 0 1 10 3z"/></svg>
                                        Add Question
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- CSV Import -->
                        <div class="panel panel-flat" style="margin-bottom:var(--space-4);">
                            <div class="panel-header">
                                <h2 style="font-size:var(--fs-15);">Bulk Import from CSV</h2>
                            </div>
                            <div class="panel-body">
                                <p class="text-sm text-muted" style="margin-bottom:var(--space-3);">
                                    CSV columns: <code>question_text, option_a, option_b, option_c, option_d, correct_answer (A-D), marks</code>
                                </p>
                                <form method="POST" enctype="multipart/form-data">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="import_csv">
                                    <input type="hidden" name="test_id" value="<?= $editTestId ?>">
                                    <div class="form-inline">
                                        <input type="file" name="csv_file" accept=".csv,.txt" required
                                               style="padding:6px 10px;border:1px solid var(--gray-20);border-radius:var(--radius-md);font-size:var(--fs-13);">
                                        <button type="submit" class="btn btn-secondary">Import CSV</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="panel panel-flat" style="margin-bottom:var(--space-4);">
                            <div class="panel-header">
                                <h2 style="font-size:var(--fs-15);">Question Settings</h2>
                            </div>
                            <div class="panel-body">
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_draft">
                                    <input type="hidden" name="id" value="<?= $editTestId ?>">
                                    <input type="hidden" name="batch_id" value="<?= $editTest['batch_id'] ?>">
                                    <input type="hidden" name="title" value="<?= h($editTest['title']) ?>">
                                    <input type="hidden" name="description" value="<?= h($editTest['description'] ?? '') ?>">
                                    <input type="hidden" name="duration_minutes" value="<?= $editTest['duration_minutes'] ?>">
                                    <input type="hidden" name="passing_marks" value="<?= $editTest['passing_marks'] ?? 0 ?>">
                                    <input type="hidden" name="negative_marking" value="<?= $editTest['negative_marking'] ?? 0 ?>">
                                    <input type="hidden" name="instructions" value="<?= h($editTest['instructions'] ?? '') ?>">
                                    <div class="form-checkbox">
                                        <input type="checkbox" name="shuffle_questions" value="1" <?= !empty($editTest['shuffle_questions']) ? 'checked' : '' ?>>
                                        <span>Shuffle questions for students</span>
                                    </div>
                                    <div class="form-hint" style="margin-top:var(--space-2);">When enabled, question order will be randomized for each student.</div>
                                    <button type="submit" class="btn btn-secondary mt-3">Save Settings</button>
                                </form>
                            </div>
                        </div>

                        <!-- Questions List -->
                        <div class="panel panel-flat">
                            <div class="panel-header">
                                <h2 style="font-size:var(--fs-15);">Questions (<?= count($questions) ?>)</h2>
                            </div>
                            <?php if (empty($questions)): ?>
                                <div class="panel-body">
                                    <div class="empty-state" style="padding:var(--space-8);">
                                        <div class="empty-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1V3zm0 1H4a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-1H9a2 2 0 0 1-2-2V4zm2-1v5a1 1 0 0 0 1 1h5V4a1 1 0 0 0-1-1h-4z"/></svg></div>
                                        <h3>No Questions Yet</h3>
                                        <p>Add questions manually or import from CSV.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="padding:var(--space-3);">
                                    <?php foreach ($questions as $i => $q): ?>
                                    <div class="question-item">
                                        <div class="q-header">
                                            <div class="q-number"><?= $i + 1 ?></div>
                                            <div class="q-text"><?= h(mb_substr($q['question_text'], 0, 120)) ?><?= mb_strlen($q['question_text']) > 120 ? '...' : '' ?></div>
                                            <div class="q-meta">
                                                <span class="badge <?= $q['type'] === 'mcq' ? 'badge-active' : ($q['type'] === 'coding' ? 'badge-pending' : 'badge-success') ?>">
                                                    <?= ucfirst($q['type']) ?>
                                                </span>
                                                <span class="badge badge-neutral"><?= $q['marks'] ?> pts</span>
                                                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this question?')">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete_question">
                                                    <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--red);" data-tooltip="Delete">
                                                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.5 2.5a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1V3h3.5a.5.5 0 0 1 0 1h-.55l-.77 11.57A2 2 0 0 1 11.7 17H8.3a2 2 0 0 1-2-1.93L5.55 4H5a.5.5 0 0 1 0-1h3.5V2.5z"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex;justify-content:space-between;margin-top:var(--space-5);">
                            <a href="assessment_studio.php?edit_test=<?= $editTestId ?>&step=1" class="btn btn-secondary">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.65 3.15a.5.5 0 0 0-.7-.7l-6.5 6.5a.5.5 0 0 0 0 .7l6.5 6.5a.5.5 0 0 0 .7-.7L3.2 10H17.5a.5.5 0 0 0 0-1H3.2l5.45-5.85z"/></svg>
                                Back
                            </a>
                            <a href="assessment_studio.php?edit_test=<?= $editTestId ?>&step=3" class="btn btn-primary">
                                Review Assessment
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M11.35 3.15a.5.5 0 0 1 .7-.7l6.5 6.5a.5.5 0 0 1 0 .7l-6.5 6.5a.5.5 0 0 1-.7-.7L16.8 10H2.5a.5.5 0 0 1 0-1h14.3l-5.45-5.85z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($editTestId > 0 && isset($_GET['step']) && $_GET['step'] == 3 && $editTest): ?>
        <!-- ═══════════════ STEP 3: REVIEW ═══════════════ -->
        <div style="margin-bottom:var(--space-4);">
            <div style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-4);">
                <a href="assessment_studio.php?edit_test=<?= $editTestId ?>" class="btn btn-sm btn-ghost">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.65 3.15a.5.5 0 0 0-.7-.7l-6.5 6.5a.5.5 0 0 0 0 .7l6.5 6.5a.5.5 0 0 0 .7-.7L3.2 10H17.5a.5.5 0 0 0 0-1H3.2l5.45-5.85z"/></svg>
                    Back to Questions
                </a>
            </div>

            <div class="wizard">
                <div class="wizard-steps">
                    <div class="wizard-step completed">
                        <span class="step-num"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg></span>
                        <span>Basic Information</span>
                    </div>
                    <div class="wizard-step completed">
                        <span class="step-num"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg></span>
                        <span>Question Builder</span>
                    </div>
                    <div class="wizard-step active">
                        <span class="step-num">3</span>
                        <span>Review & Publish</span>
                    </div>
                </div>

                <div class="wizard-content">
                    <h2 style="font-size:var(--fs-18);margin-bottom:var(--space-4);">Review Assessment</h2>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-5);">
                        <div class="panel panel-flat">
                            <div class="panel-body">
                                <div class="flex-between" style="margin-bottom:var(--space-3);">
                                    <span class="text-muted text-sm">Title</span>
                                    <span style="font-weight:500;"><?= h($editTest['title']) ?></span>
                                </div>
                                <div class="flex-between" style="margin-bottom:var(--space-3);">
                                    <span class="text-muted text-sm">Duration</span>
                                    <span style="font-weight:500;"><?= $editTest['duration_minutes'] ?> minutes</span>
                                </div>
                                <div class="flex-between" style="margin-bottom:var(--space-3);">
                                    <span class="text-muted text-sm">Question Count</span>
                                    <span style="font-weight:500;"><?= count($questions) ?></span>
                                </div>
                                <div class="flex-between" style="margin-bottom:var(--space-3);">
                                    <span class="text-muted text-sm">MCQ Questions</span>
                                    <span><?= count(array_filter($questions, fn($q) => $q['type'] === 'mcq')) ?></span>
                                </div>
                                <div class="flex-between" style="margin-bottom:var(--space-3);">
                                    <span class="text-muted text-sm">Coding Questions</span>
                                    <span><?= count(array_filter($questions, fn($q) => $q['type'] === 'coding')) ?></span>
                                </div>
                                <div class="flex-between" style="margin-bottom:var(--space-3);">
                                    <span class="text-muted text-sm">Explanation Questions</span>
                                    <span><?= count(array_filter($questions, fn($q) => $q['type'] === 'explanation')) ?></span>
                                </div>
                                <div class="flex-between" style="margin-bottom:var(--space-3);">
                                    <span class="text-muted text-sm">Shuffle Enabled</span>
                                    <span><?= !empty($editTest['shuffle_questions']) ? 'Yes' : 'No' ?></span>
                                </div>
                                <div class="flex-between">
                                    <span class="text-muted text-sm">Passing Marks</span>
                                    <span><?= (int)($editTest['passing_marks'] ?? 0) > 0 ? (int)$editTest['passing_marks'] : 'Not set' ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="panel panel-flat">
                            <div class="panel-header">
                                <h2 style="font-size:var(--fs-15);">Actions</h2>
                            </div>
                            <div class="panel-body">
                                <p class="text-sm text-muted" style="margin-bottom:var(--space-4);">
                                    This assessment is currently a draft. Students cannot see it until published.
                                </p>
                                <button class="btn btn-primary btn-lg w-full" onclick="openPublishModal(<?= $editTestId ?>, '<?= h($editTest['title']) ?>')">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3.5a.5.5 0 0 1 .75-.43l12.5 7a.5.5 0 0 1 0 .86l-12.5 7A.5.5 0 0 1 4 17.5v-14z"/></svg>
                                    Publish Assessment
                                </button>
                                <div style="margin-top:var(--space-3);">
                                    <a href="assessment_studio.php?edit_test=<?= $editTestId ?>" class="btn btn-secondary w-full">Continue Editing</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ═══════════════ STEP 1: BASIC INFORMATION ═══════════════ -->
        <div style="margin-bottom:var(--space-4);">
            <h1 style="font-size:var(--fs-24);font-weight:600;margin-bottom:var(--space-1);">Create Assessment</h1>
            <p class="text-muted" style="font-size:var(--fs-14);">Set up your assessment details. You can add questions in the next step.</p>
        </div>

        <div class="wizard">
            <div class="wizard-steps">
                <div class="wizard-step active">
                    <span class="step-num">1</span>
                    <span>Basic Information</span>
                </div>
                <div class="wizard-step">
                    <span class="step-num">2</span>
                    <span>Question Builder</span>
                </div>
                <div class="wizard-step">
                    <span class="step-num">3</span>
                    <span>Review & Publish</span>
                </div>
            </div>

            <div class="wizard-content">
                <form method="POST" id="createAssessmentForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create_draft">

                    <div class="form-group">
                        <label>Assessment Title *</label>
                        <input class="form-input" type="text" name="title" required placeholder="e.g. Midterm Examination 2024" style="font-size:var(--fs-16);padding:10px 14px;">
                    </div>

                    <div class="form-group">
                        <label>Target Batch *</label>
                        <select class="form-select" name="batch_id" required>
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= $b['id'] ?>">
                                    <?= h($b['college_name']) ?> → <?= h($b['course_name']) ?> → <?= h($b['batch_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Duration (minutes) *</label>
                            <input class="form-input" type="number" name="duration_minutes" required min="1" max="480" value="30">
                        </div>
                        <div class="form-group">
                            <label>Passing Marks (optional)</label>
                            <input class="form-input" type="number" name="passing_marks" min="0" value="0" placeholder="0 = not set">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Negative Marking (optional)</label>
                            <input class="form-input" type="number" name="negative_marking" min="0" max="10" step="0.5" value="0" placeholder="0 = none">
                            <div class="form-hint">Marks deducted for incorrect answers per question.</div>
                        </div>
                        <div class="form-group">
                            <label>Shuffle Questions</label>
                            <div class="form-checkbox" style="margin-top:6px;">
                                <input type="checkbox" name="shuffle_questions" value="1">
                                <span>Randomize question order for students</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description / Instructions</label>
                        <textarea class="form-textarea" name="description" placeholder="Optional description for the assessment" style="min-height:60px;"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Detailed Instructions</label>
                        <textarea class="form-textarea" name="instructions" placeholder="Detailed instructions shown to students before starting the assessment" style="min-height:80px;"></textarea>
                    </div>

                    <div class="wizard-footer" style="padding:var(--space-5) 0 0 0;border-top:1px solid var(--gray-10);margin-top:var(--space-4);">
                        <div></div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            Next — Question Builder
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M11.35 3.15a.5.5 0 0 1 .7-.7l6.5 6.5a.5.5 0 0 1 0 .7l-6.5 6.5a.5.5 0 0 1-.7-.7L16.8 10H2.5a.5.5 0 0 1 0-1h14.3l-5.45-5.85z"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick link to drafts -->
        <div style="text-align:center;margin-top:var(--space-4);">
            <a href="assessment_studio.php?tab=drafts" class="btn btn-ghost">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 3a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-1v1a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1V3z"/></svg>
                View Draft Assessments
            </a>
        </div>

    <?php endif; ?>
</div>

<!-- Publish Modal -->
<div class="modal-overlay" id="publishModal" style="display:none;">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Publish Assessment</h3>
            <button type="button" class="modal-close" onclick="closePublishModal()">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4.09 4.09a.5.5 0 0 1 .7 0L10 9.29l5.2-5.2a.5.5 0 0 1 .7.7L10.7 10l5.2 5.2a.5.5 0 0 1-.7.7L10 10.7l-5.2 5.2a.5.5 0 0 1-.7-.7L9.29 10 4.09 4.8a.5.5 0 0 1 0-.7z"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-muted" style="margin-bottom:var(--space-4);" id="publishTitle">Publish assessment</p>
            <div style="display:flex;flex-direction:column;gap:var(--space-3);">
                <form method="POST" id="publishNowForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="publish_now">
                    <input type="hidden" name="id" id="publishTestId" value="">
                    <button type="submit" class="btn btn-primary w-full btn-lg">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3.5a.5.5 0 0 1 .75-.43l12.5 7a.5.5 0 0 1 0 .86l-12.5 7A.5.5 0 0 1 4 17.5v-14z"/></svg>
                        Publish Now — Make Live
                    </button>
                </form>
                <div style="position:relative;text-align:center;">
                    <span style="background:var(--white);padding:0 var(--space-3);color:var(--gray-40);font-size:var(--fs-11);position:relative;z-index:1;">or</span>
                    <hr style="border:none;border-top:1px solid var(--gray-15);margin:-9px auto 0;width:100%;">
                </div>
                <button class="btn btn-secondary w-full" onclick="showScheduleForm()">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M5.5 2a.5.5 0 0 1 .5.5V3h8v-.5a.5.5 0 0 1 1 0V3h1a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h1v-.5a.5.5 0 0 1 .5-.5zM4 4a1 1 0 0 0-1 1v1h14V5a1 1 0 0 0-1-1H4z"/></svg>
                    Schedule for Later
                </button>
            </div>

            <form method="POST" id="scheduleForm" style="display:none;margin-top:var(--space-4);padding-top:var(--space-4);border-top:1px solid var(--gray-10);">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="schedule_publish">
                <input type="hidden" name="id" id="scheduleTestId" value="">
                <div class="form-group">
                    <label>Start Date & Time</label>
                    <input class="form-input" type="datetime-local" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>End Date & Time</label>
                    <input class="form-input" type="datetime-local" name="end_time" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Schedule Publication</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleQuestionType(sel) {
    document.getElementById('mcqFields').style.display = sel.value === 'mcq' ? 'block' : 'none';
}

// Convert plain text options to JSON array before form submit
document.querySelector('form[action*="add_question"]')?.addEventListener('submit', function() {
    const plain = document.getElementById('optionsPlain');
    if (plain) {
        const lines = plain.value.split('\n').filter(l => l.trim());
        const options = lines.map((text, i) => ({
            key: String.fromCharCode(65 + i),
            text: text.trim()
        }));
        document.getElementById('optionsHidden').value = JSON.stringify(options);
        const sel = document.getElementById('correctAnswer');
        const currentVal = sel.value;
        sel.innerHTML = '<option value="">Select correct answer</option>';
        options.forEach(o => {
            sel.innerHTML += '<option value="' + o.key + '" ' + (currentVal === o.key ? 'selected' : '') + '>' + o.key + '</option>';
        });
    }
});

function openPublishModal(id, title) {
    document.getElementById('publishTestId').value = id;
    document.getElementById('scheduleTestId').value = id;
    document.getElementById('publishTitle').textContent = 'Publish "' + title + '" — students will immediately be able to see and start this assessment.';
    document.getElementById('publishModal').style.display = 'flex';
    document.getElementById('scheduleForm').style.display = 'none';
}

function closePublishModal() {
    document.getElementById('publishModal').style.display = 'none';
}

function showScheduleForm() {
    document.getElementById('scheduleForm').style.display = 'block';
}

// Close modal on overlay click
document.getElementById('publishModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePublishModal();
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>