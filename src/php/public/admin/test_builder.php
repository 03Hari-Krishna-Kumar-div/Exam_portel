<?php
$pageTitle = 'Test Builder';
require_once __DIR__ . '/../../includes/admin_header.php';

$pdo = getDB();
$message = '';
$editTest = null;
$showQuestions = false;

// Handle create/edit test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    // ─── Test CRUD ──────────────────────────────────────
    if ($action === 'create_test' && !empty($_POST['title'])) {
        $stmt = $pdo->prepare("
            INSERT INTO tests (batch_id, title, description, duration_minutes, start_time, end_time, status, shuffle_questions, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$_POST['batch_id'],
            trim($_POST['title']),
            trim($_POST['description'] ?? ''),
            (int)$_POST['duration_minutes'],
            !empty($_POST['start_time']) ? $_POST['start_time'] : null,
            !empty($_POST['end_time']) ? $_POST['end_time'] : null,
            $_POST['status'] ?? 'upcoming',
            !empty($_POST['shuffle_questions']) ? 1 : 0,
            $_SESSION['admin_id'],
        ]);
        $message = 'Test created successfully.';
    } elseif ($action === 'update_test' && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("
            UPDATE tests SET batch_id=?, title=?, description=?, duration_minutes=?, start_time=?, end_time=?, status=?, shuffle_questions=?
            WHERE id=?
        ");
        $stmt->execute([
            (int)$_POST['batch_id'], trim($_POST['title']), trim($_POST['description'] ?? ''),
            (int)$_POST['duration_minutes'], $_POST['start_time'] ?: null, $_POST['end_time'] ?: null,
            $_POST['status'] ?? 'upcoming', !empty($_POST['shuffle_questions']) ? 1 : 0,
            (int)$_POST['id'],
        ]);
        $message = 'Test updated successfully.';
    } elseif ($action === 'activate_test' && !empty($_POST['id'])) {
        $pdo->prepare("UPDATE tests SET status = 'active' WHERE id = ?")->execute([(int)$_POST['id']]);
        $message = 'Test activated. Students can now start it.';
    } elseif ($action === 'delete_test' && !empty($_POST['id'])) {
        $pdo->prepare("DELETE FROM tests WHERE id = ?")->execute([(int)$_POST['id']]);
        $message = 'Test deleted.';
    } elseif ($action === 'pause_test' && !empty($_POST['id'])) {
        $pdo->prepare("UPDATE tests SET status = 'paused' WHERE id = ?")->execute([(int)$_POST['id']]);
        $message = 'Test paused. Students cannot access it until resumed.';
    } elseif ($action === 'resume_test' && !empty($_POST['id'])) {
        $pdo->prepare("UPDATE tests SET status = 'active' WHERE id = ?")->execute([(int)$_POST['id']]);
        $message = 'Test resumed. Students can now access it.';
    } elseif ($action === 'stop_test' && !empty($_POST['id'])) {
        $testId = (int)$_POST['id'];
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE tests SET status = 'completed' WHERE id = ?")->execute([$testId]);
        // Auto-submit all in-progress submissions
        $pdo->prepare("UPDATE submissions SET status = 'submitted', submitted_at = NOW() WHERE test_id = ? AND status = 'in_progress'")->execute([$testId]);
        $pdo->commit();
        $message = 'Test stopped. All in-progress submissions have been auto-submitted.';
    }

    // ─── CSV Import ─────────────────────────────────────
    elseif ($action === 'import_csv' && !empty($_POST['test_id']) && !empty($_FILES['csv_file']['tmp_name'])) {
        $testId = (int)$_POST['test_id'];
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if ($handle) {
            $imported = 0;
            $errors = [];
            $lineNum = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;
                // Skip header row if first line looks like a header
                if ($lineNum === 1 && preg_match('/question/i', $row[0] ?? '')) continue;

                $qText = trim($row[0] ?? '');
                $optA  = trim($row[1] ?? '');
                $optB  = trim($row[2] ?? '');
                $optC  = trim($row[3] ?? '');
                $optD  = trim($row[4] ?? '');
                $answer = strtoupper(trim($row[5] ?? ''));
                $marks = (int)($row[6] ?? 1);
                if ($marks < 1) $marks = 1;

                if (empty($qText)) {
                    $errors[] = "Line $lineNum: empty question text, skipped.";
                    continue;
                }
                if (empty($optA) || empty($optB)) {
                    $errors[] = "Line $lineNum: need at least 2 options.";
                    continue;
                }
                if (!in_array($answer, ['A','B','C','D'])) {
                    $errors[] = "Line $lineNum: invalid answer '$answer'. Use A, B, C, or D.";
                    continue;
                }

                $options = [];
                if ($optA) $options[] = ['key' => 'A', 'text' => $optA];
                if ($optB) $options[] = ['key' => 'B', 'text' => $optB];
                if ($optC) $options[] = ['key' => 'C', 'text' => $optC];
                if ($optD) $options[] = ['key' => 'D', 'text' => $optD];

                $stmt = $pdo->prepare("
                    INSERT INTO questions (test_id, type, question_text, options_json, correct_answer, marks, sort_order)
                    VALUES (?, 'mcq', ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $testId, $qText, json_encode($options), $answer, $marks, $lineNum
                ]);
                $imported++;
            }
            fclose($handle);
            $msg = "Imported $imported questions successfully.";
            if (!empty($errors)) {
                $msg .= ' Warnings: ' . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) $msg .= ' (and ' . (count($errors)-5) . ' more)';
            }
            $message = $msg;
        } else {
            $message = 'Failed to open uploaded file.';
        }
    }

    // ─── Question CRUD ──────────────────────────────────
    elseif ($action === 'add_question' && !empty($_POST['test_id']) && !empty($_POST['question_text'])) {
        $testId = (int)$_POST['test_id'];
        $type = $_POST['question_type'] ?? 'mcq';
        $optionsJson = null;
        $correctAnswer = null;

        if ($type === 'mcq') {
            $options = [];
            $rawOptions = $_POST['options'] ?? '';

            // Case 1: Already a JSON string from JS conversion
            if (is_string($rawOptions) && str_starts_with(trim($rawOptions), '[')) {
                $decoded = json_decode($rawOptions, true);
                if (is_array($decoded)) {
                    $options = $decoded;
                }
            }
            // Case 2: Plain text lines (fallback when JS disabled)
            elseif (is_string($rawOptions) && trim($rawOptions)) {
                $lines = explode("\n", $rawOptions);
                foreach ($lines as $i => $line) {
                    $line = trim($line);
                    if ($line) {
                        $key = chr(65 + $i);
                        $options[] = ['key' => $key, 'text' => $line];
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
        $message = 'Question added.';
        $showQuestions = true;
    } elseif ($action === 'delete_question' && !empty($_POST['id'])) {
        $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([(int)$_POST['id']]);
        $message = 'Question deleted.';
    }

    // ─── Timer Extension ────────────────────────────────
    elseif ($action === 'extend_timer' && !empty($_POST['submission_id'])) {
        $extendMinutes = (int)($_POST['extend_minutes'] ?? 5);
        $stmt = $pdo->prepare("UPDATE submissions SET timer_extended_minutes = timer_extended_minutes + ? WHERE id = ?");
        $stmt->execute([$extendMinutes, (int)$_POST['submission_id']]);
        // Also log the extension
        $stmt = $pdo->prepare("INSERT INTO tab_switch_logs (submission_id, switch_count, type, metadata) VALUES (?, ?, 'timer_extend', ?)");
        $stmt->execute([(int)$_POST['submission_id'], 0, json_encode(['extended_by' => $extendMinutes])]);
        $message = 'Timer extended by ' . $extendMinutes . ' minutes.';
    }
}

// Get edit test ID from URL
$editTestId = (int)($_GET['edit_test'] ?? 0);
if ($editTestId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
    $stmt->execute([$editTestId]);
    $editTest = $stmt->fetch();
}

// Get viewing test ID for questions
$viewTestId = (int)($_GET['view_test'] ?? ($editTestId ?: 0));
if ($viewTestId > 0) $showQuestions = true;

// Get colleges, batches for filters
$batches = $pdo->query("
    SELECT b.id, b.name AS batch_name, c.name AS course_name, cl.name AS college_name
    FROM batches b
    JOIN courses c ON c.id = b.course_id
    JOIN colleges cl ON cl.id = c.college_id
    ORDER BY cl.name, c.name, b.name
")->fetchAll();

// List tests
$tests = $pdo->query("
    SELECT t.*, b.name AS batch_name, c.name AS course_name, cl.name AS college_name,
           (SELECT COUNT(*) FROM questions WHERE test_id = t.id) AS question_count,
           (SELECT COUNT(*) FROM submissions WHERE test_id = t.id AND status = 'submitted') AS submitted_count
    FROM tests t
    JOIN batches b ON b.id = t.batch_id
    JOIN courses c ON c.id = b.course_id
    JOIN colleges cl ON cl.id = c.college_id
    ORDER BY t.created_at DESC
")->fetchAll();

// Questions for viewing
$questions = [];
if ($viewTestId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE test_id = ? ORDER BY sort_order, id");
    $stmt->execute([$viewTestId]);
    $questions = $stmt->fetchAll();
}

// Submissions for timer extension (in-progress tests)
$stmt = $pdo->prepare("
    SELECT s.id, s.started_at, s.timer_extended_minutes,
           st.name AS student_name, st.email, t.duration_minutes
    FROM submissions s
    JOIN students st ON st.id = s.student_id
    JOIN tests t ON t.id = s.test_id
    WHERE s.test_id = ? AND s.status = 'in_progress'
    ORDER BY s.started_at
");
$runningSubmissions = [];
if ($viewTestId > 0) {
    $stmt->execute([$viewTestId]);
    $runningSubmissions = $stmt->fetchAll();
}
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:16px;">
    <div class="panel">
        <div class="panel-header">
            <h2><?= $editTest ? 'Edit Test' : 'Create Test' ?></h2>
        </div>
        <div class="panel-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="<?= $editTest ? 'update_test' : 'create_test' ?>">
                <?php if ($editTest): ?>
                    <input type="hidden" name="id" value="<?= $editTest['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Batch *</label>
                    <select class="form-select" name="batch_id" required>
                        <option value="">Select Batch</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($editTest['batch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                                <?= h($b['college_name']) ?> → <?= h($b['course_name']) ?> → <?= h($b['batch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Test Title *</label>
                    <input class="form-input" type="text" name="title" required
                           value="<?= h($editTest['title'] ?? '') ?>" placeholder="e.g. Midterm Exam 2024">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-textarea" name="description" placeholder="Optional description/instructions"><?= h($editTest['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Duration (minutes) *</label>
                        <input class="form-input" type="number" name="duration_minutes" required min="1" max="480"
                               value="<?= h($editTest['duration_minutes'] ?? '30') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-select" name="status">
                            <option value="upcoming" <?= ($editTest['status'] ?? 'upcoming') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="active" <?= ($editTest['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="paused" <?= ($editTest['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Paused</option>
                            <option value="completed" <?= ($editTest['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input class="form-input" type="datetime-local" name="start_time"
                               value="<?= h($editTest['start_time'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input class="form-input" type="datetime-local" name="end_time"
                               value="<?= h($editTest['end_time'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="flex-center" style="gap:8px;cursor:pointer;text-transform:none;letter-spacing:0;">
                        <input type="checkbox" name="shuffle_questions" value="1"
                               <?= !empty($editTest['shuffle_questions']) ? 'checked' : '' ?>>
                        Shuffle questions for students
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $editTest ? 'Update Test' : 'Create Test' ?>
                </button>
                <?php if ($editTest): ?>
                    <a href="test_builder.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Add Question -->
    <?php if ($viewTestId > 0): ?>
    <div class="panel">
        <div class="panel-header">
            <h2>Add Question to Test #<?= $viewTestId ?></h2>
        </div>
        <div class="panel-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="test_id" value="<?= $viewTestId ?>">

                <div class="form-group">
                    <label>Question Type</label>
                    <select class="form-select" id="qType" name="question_type" onchange="toggleOptions()">
                        <option value="mcq">MCQ (Multiple Choice)</option>
                        <option value="coding">Coding</option>
                        <option value="explanation">Explanation</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Question Text *</label>
                    <textarea class="form-textarea" name="question_text" required placeholder="Type your question here..." style="min-height:80px;"></textarea>
                </div>

                <div id="mcqOptions">
                    <div class="form-group">
                        <label>Options (one per line)</label>
                        <textarea class="form-textarea" name="options_plain" id="optionsPlain"
                                  placeholder="Option A&#10;Option B&#10;Option C&#10;Option D" style="min-height:80px;"></textarea>
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
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Marks</label>
                        <input class="form-input" type="number" name="marks" value="1" min="1" max="100">
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input class="form-input" type="number" name="sort_order" value="0" min="0">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Add Question</button>
            </form>
        </div>
    </div>

    <!-- CSV Upload -->
    <div class="panel mt-4">
        <div class="panel-header">
            <h2>📥 Bulk Import Questions (CSV)</h2>
            <span class="text-muted text-sm">MCQ only</span>
        </div>
        <div class="panel-body">
            <p style="font-size:13px;color:#666;margin-bottom:12px;">
                CSV columns: <code>question_text, option_a, option_b, option_c, option_d, correct_answer (A-D), marks</code>
            </p>
            <form method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="import_csv">
                <input type="hidden" name="test_id" value="<?= $viewTestId ?>">
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                           style="padding:8px;border:1px solid #d0d0d0;border-radius:4px;font-size:14px;">
                    <button type="submit" class="btn btn-primary btn-sm">Import CSV</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($message): ?>
        <div class="alert alert-success">
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px;flex-shrink:0;"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg>
        <span><?= h($message) ?></span>
    </div>
<?php endif; ?>

<!-- Tests List -->
<div class="panel">
    <div class="panel-header">
        <h2>All Tests</h2>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Batch</th>
                    <th>Duration</th>
                    <th>Questions</th>
                    <th>Submissions</th>
                    <th>Status</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tests)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-50);">No tests created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($tests as $t): ?>
                    <tr>
                        <td><strong><?= h($t['title']) ?></strong></td>
                        <td class="text-sm"><?= h($t['batch_name']) ?></td>
                        <td class="text-sm"><?= $t['duration_minutes'] ?> min</td>
                        <td><span class="badge badge-active"><?= $t['question_count'] ?></span></td>
                        <td class="text-sm"><?= $t['submitted_count'] ?></td>
                        <td>
                            <?php
                                $tStatusClass = 'pending';
                                if ($t['status'] === 'active') $tStatusClass = 'active';
                                elseif ($t['status'] === 'paused') $tStatusClass = 'warning';
                                elseif ($t['status'] === 'completed') $tStatusClass = 'success';
                            ?>
                            <span class="badge badge-<?= $tStatusClass ?>">
                                <?= ucfirst($t['status']) ?>
                            </span>
                        </td>
                        <td class="actions" style="white-space:nowrap;">
                            <a href="test_builder.php?view_test=<?= $t['id'] ?>" class="btn btn-sm btn-ghost">Questions</a>
                            <a href="test_builder.php?edit_test=<?= $t['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
                            <?php if ($t['status'] === 'upcoming'): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="activate_test">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Activate Now</button>
                                </form>
                            <?php elseif ($t['status'] === 'active'): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="pause_test">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">Pause</button>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Stop this test? Students currently taking it will be affected.')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="stop_test">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Stop</button>
                                </form>
                            <?php elseif ($t['status'] === 'paused'): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="resume_test">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Resume</button>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Stop this test?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="stop_test">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Stop</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this test and all its questions?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete_test">
                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
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

<!-- Questions List -->
<?php if ($viewTestId > 0): ?>
<div class="panel mt-4">
    <div class="panel-header">
        <h2>Questions — Test #<?= $viewTestId ?></h2>
        <span class="text-muted text-sm"><?= count($questions) ?> questions</span>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Question</th>
                    <th>Marks</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--gray-50);">No questions added yet. Use the form above.</td></tr>
                <?php else: ?>
                    <?php foreach ($questions as $i => $q): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td>
                            <span class="badge <?= $q['type'] === 'mcq' ? 'badge-active' : ($q['type'] === 'coding' ? 'badge-pending' : 'badge-success') ?>">
                                <?= ucfirst($q['type']) ?>
                            </span>
                        </td>
                        <td style="max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= h(mb_substr($q['question_text'], 0, 80)) ?><?= mb_strlen($q['question_text']) > 80 ? '...' : '' ?>
                        </td>
                        <td><?= $q['marks'] ?></td>
                        <td class="actions">
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this question?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete_question">
                                <input type="hidden" name="id" value="<?= $q['id'] ?>">
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

<!-- Timer Extension -->
<?php if (!empty($runningSubmissions)): ?>
<div class="panel mt-4">
    <div class="panel-header">
        <h2>Active Submissions — Extend Timer</h2>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Started</th>
                    <th>Elapsed</th>
                    <th>Extended</th>
                    <th class="actions">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($runningSubmissions as $rs):
                    $elapsed = time() - strtotime($rs['started_at']);
                    $totalMins = ($rs['duration_minutes'] * 60) + ($rs['timer_extended_minutes'] * 60);
                    $remaining = max(0, $totalMins - $elapsed);
                ?>
                <tr>
                    <td><strong><?= h($rs['student_name']) ?></strong><br><span class="text-sm text-muted"><?= h($rs['email']) ?></span></td>
                    <td class="text-sm"><?= timeAgo($rs['started_at']) ?></td>
                    <td class="text-sm"><?= gmdate('H:i:s', $elapsed) ?> / <?= gmdate('H:i:s', $totalMins) ?></td>
                    <td>+<?= $rs['timer_extended_minutes'] ?> min</td>
                    <td class="actions">
                        <form method="POST" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="extend_timer">
                            <input type="hidden" name="submission_id" value="<?= $rs['id'] ?>">
                            <select class="form-select" name="extend_minutes" style="width:auto;display:inline;max-width:80px;">
                                <option value="5">+5</option>
                                <option value="10">+10</option>
                                <option value="15">+15</option>
                                <option value="30">+30</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Extend</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
function toggleOptions() {
    const type = document.getElementById('qType').value;
    document.getElementById('mcqOptions').style.display = type === 'mcq' ? 'block' : 'none';
}
toggleOptions();

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

        // Update correct answer dropdown with the right number of options
        const sel = document.getElementById('correctAnswer');
        const currentVal = sel.value;
        sel.innerHTML = '<option value="">Select correct answer</option>';
        options.forEach(o => {
            sel.innerHTML += '<option value="' + o.key + '" ' + (currentVal === o.key ? 'selected' : '') + '>' + o.key + '</option>';
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
