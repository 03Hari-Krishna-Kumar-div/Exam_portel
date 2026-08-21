<?php
$pageTitle = 'Grading';
require_once __DIR__ . '/../../includes/admin_header.php';

$pdo = getDB();
$message = '';
$currentTestId = (int)($_GET['test_id'] ?? 0);
$currentStudentId = (int)($_GET['student_id'] ?? 0);

// ─── Save marks ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    if (isset($_POST['save_grades'])) {
        $submissionId = (int)$_POST['submission_id'];
        $answers = $_POST['marks'] ?? [];

        $pdo->beginTransaction();
        try {
            $totalObtained = 0;
            $totalPossible = 0;

            foreach ($answers as $answerId => $marks) {
                $marks = (float)$marks;
                $stmt = $pdo->prepare("
                    UPDATE student_answers
                    SET marks_obtained = ?, evaluated_at = NOW()
                    WHERE id = ? AND submission_id = ?
                ");
                $stmt->execute([$marks, (int)$answerId, $submissionId]);

                // Get question marks
                $stmt = $pdo->prepare("
                    SELECT q.marks FROM student_answers sa
                    JOIN questions q ON q.id = sa.question_id
                    WHERE sa.id = ?
                ");
                $stmt->execute([(int)$answerId]);
                $qMarks = $stmt->fetchColumn();

                $totalObtained += $marks;
                $totalPossible += (float)$qMarks;
            }

            // Update submission totals + mark evaluated
            $stmt = $pdo->prepare("
                UPDATE submissions
                SET status = 'evaluated',
                    total_marks_obtained = ?,
                    total_marks = ?,
                    submitted_at = COALESCE(submitted_at, NOW())
                WHERE id = ?
            ");
            $stmt->execute([$totalObtained, $totalPossible, $submissionId]);

            $pdo->commit();
            $message = 'Grades saved. Submission marked as evaluated.';

            // Answers are committed above — safe to refresh PCI analytics now.
            // Python service first (retries + error boundary inside), PHP fallback otherwise.
            recalculatePciForSubmission($submissionId);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = 'Error saving grades: ' . h($e->getMessage());
        }
    }

    // ─── Save individual marks via inline form ────────────────
    elseif (isset($_POST['save_individual'])) {
        $answerId = (int)$_POST['answer_id'];
        $marks = (float)$_POST['marks'];
        $submissionId = (int)$_POST['submission_id'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE student_answers
                SET marks_obtained = ?, evaluated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$marks, $answerId]);

            // Recalculate submission totals
            $stmt = $pdo->prepare("
                SELECT SUM(sa.marks_obtained), SUM(q.marks)
                FROM student_answers sa
                JOIN questions q ON q.id = sa.question_id
                WHERE sa.submission_id = ?
            ");
            $stmt->execute([$submissionId]);
            $row = $stmt->fetch();

            if ($row[0] !== null) {
                $stmt = $pdo->prepare("
                    UPDATE submissions
                    SET status = 'evaluated',
                        total_marks_obtained = ?,
                        total_marks = ?,
                        submitted_at = COALESCE(submitted_at, NOW())
                    WHERE id = ?
                ");
                $stmt->execute([(float)$row[0], (float)$row[1], $submissionId]);
            }

            $pdo->commit();

            // Answers are committed — refresh PCI analytics (Python → PHP fallback)
            recalculatePciForSubmission($submissionId);

            $message = 'Marks saved.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = 'Error saving marks: ' . h($e->getMessage());
        }
    }
}

// ─── Get all tests ───────────────────────────────────────────
$tests = $pdo->query("
    SELECT t.id, t.title, b.name AS batch_name, b.section AS batch_section,
           (SELECT COUNT(*) FROM submissions s WHERE s.test_id = t.id AND s.status IN ('submitted','evaluated')) AS pending_count
    FROM tests t
    JOIN batches b ON b.id = t.batch_id
    ORDER BY t.created_at DESC
")->fetchAll();

// ─── Get submissions for selected test ───────────────────────
$submissions = [];
if ($currentTestId > 0) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_id, s.status, s.started_at, s.submitted_at,
               s.total_marks_obtained, s.total_marks,
               st.name AS student_name, st.email, st.roll_number,
               (SELECT COUNT(*) FROM student_answers sa
                JOIN questions q ON q.id = sa.question_id
                WHERE sa.submission_id = s.id AND q.type IN ('coding', 'explanation')
                AND sa.marks_obtained IS NULL) AS ungraded_count
        FROM submissions s
        JOIN students st ON st.id = s.student_id
        WHERE s.test_id = ? AND s.status IN ('submitted', 'evaluated')
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$currentTestId]);
    $submissions = $stmt->fetchAll();
}

// ─── Get answers for selected student submission ─────────────
$answers = [];
$submissionInfo = null;
if ($currentStudentId > 0 && $currentTestId > 0) {
    $stmt = $pdo->prepare("
        SELECT s.*, st.name AS student_name, st.email, st.roll_number,
               t.title AS test_title, t.duration_minutes
        FROM submissions s
        JOIN students st ON st.id = s.student_id
        JOIN tests t ON t.id = s.test_id
        WHERE s.student_id = ? AND s.test_id = ?
    ");
    $stmt->execute([$currentStudentId, $currentTestId]);
    $submissionInfo = $stmt->fetch();

    if ($submissionInfo) {
        $stmt = $pdo->prepare("
            SELECT sa.*, q.type, q.question_text, q.options_json, q.correct_answer, q.marks,
                   q.sort_order, q.id AS question_id
            FROM student_answers sa
            JOIN questions q ON q.id = sa.question_id
            WHERE sa.submission_id = ?
            ORDER BY q.sort_order, q.id
        ");
        $stmt->execute([$submissionInfo['id']]);
        $answers = $stmt->fetchAll();
    }
}
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:16px;">
    <div class="panel">
        <div class="panel-header">
            <h2>Select Test</h2>
        </div>
        <div class="panel-body">
            <form method="GET" action="grading.php">
                <div class="form-group">
                    <label>Test</label>
                    <select class="form-select" name="test_id" onchange="this.form.submit()">
                        <option value="">— Select Test —</option>
                        <?php foreach ($tests as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $currentTestId === (int)$t['id'] ? 'selected' : '' ?>>
                                <?= h($t['title']) ?> (<?= h($t['batch_name']) ?><?= $t['batch_section'] ? ' — ' . h($t['batch_section']) : '' ?>) — <?= $t['pending_count'] ?> pending
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($currentStudentId > 0): ?>
                    <input type="hidden" name="student_id" value="<?= $currentStudentId ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($submissionInfo): ?>
    <div class="panel">
        <div class="panel-header">
            <h2>Student: <?= h($submissionInfo['student_name']) ?></h2>
        </div>
        <div class="panel-body">
            <div class="flex-between">
                <span class="text-muted text-sm"><?= h($submissionInfo['email']) ?> · <?= h($submissionInfo['roll_number']) ?></span>
                <span>
                    <span class="badge <?= $submissionInfo['status'] === 'evaluated' ? 'badge-success' : 'badge-pending' ?>">
                        <?= ucfirst($submissionInfo['status']) ?>
                    </span>
                </span>
            </div>
            <div class="flex-between mt-2">
                <span class="text-sm text-muted">Score: <?= $submissionInfo['total_marks_obtained'] ?? '—' ?> / <?= $submissionInfo['total_marks'] ?? '—' ?></span>
                <a href="grading.php?test_id=<?= $currentTestId ?>" class="btn btn-sm btn-ghost">← Back to list</a>
            </div>
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

<!-- ─── Submission List ─────────────────────────────────────── -->
<?php if ($currentTestId > 0 && !$submissionInfo): ?>
<div class="panel">
    <div class="panel-header">
        <h2>Submissions — Pending Grading</h2>
        <span class="text-muted text-sm"><?= count($submissions) ?> submissions</span>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Roll #</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Ungraded</th>
                    <th class="actions">Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-50);">No submitted or evaluated submissions for this test.</td></tr>
                <?php else: ?>
                    <?php foreach ($submissions as $s): ?>
                    <tr>
                        <td><strong><?= h($s['student_name']) ?></strong><br><span class="text-sm text-muted"><?= h($s['email']) ?></span></td>
                        <td class="text-sm"><?= h($s['roll_number']) ?></td>
                        <td class="text-sm"><?= timeAgo($s['submitted_at']) ?></td>
                        <td>
                            <span class="badge <?= $s['status'] === 'evaluated' ? 'badge-success' : 'badge-pending' ?>">
                                <?= ucfirst($s['status']) ?>
                            </span>
                        </td>
                        <td class="text-sm"><?= $s['total_marks_obtained'] ?? '—' ?> / <?= $s['total_marks'] ?? '—' ?></td>
                        <td>
                            <?php if ((int)$s['ungraded_count'] > 0): ?>
                                <span class="badge badge-danger"><?= $s['ungraded_count'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-success">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="grading.php?test_id=<?= $currentTestId ?>&student_id=<?= $s['student_id'] ?>" class="btn btn-sm btn-primary">
                                <?= (int)$s['ungraded_count'] > 0 ? 'Grade' : 'Review' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ─── Grading Interface ───────────────────────────────────── -->
<?php if ($submissionInfo && !empty($answers)): ?>
<div class="panel">
    <div class="panel-header">
        <h2>Grading: <?= h($submissionInfo['test_title']) ?></h2>
    </div>
    <div class="panel-body">
        <form method="POST" action="grading.php?test_id=<?= $currentTestId ?>&student_id=<?= $currentStudentId ?>">
            <?= csrfField() ?>
            <input type="hidden" name="save_grades" value="1">
            <input type="hidden" name="submission_id" value="<?= $submissionInfo['id'] ?>">

            <?php foreach ($answers as $i => $a): ?>
            <div style="border:1px solid var(--gray-20);border-radius:var(--radius-md);margin-bottom:var(--space-4);overflow:hidden;">
                <div style="padding:var(--space-3) var(--space-4);background:var(--gray-5);border-bottom:1px solid var(--gray-20);display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <strong>Q<?= $i + 1 ?></strong>
                        <span class="badge <?= $a['type'] === 'mcq' ? 'badge-active' : ($a['type'] === 'coding' ? 'badge-pending' : 'badge-success') ?>" style="margin-left:8px;">
                            <?= ucfirst($a['type']) ?>
                        </span>
                    </div>
                    <div class="text-sm text-muted">
                        Max: <?= (int)$a['marks'] ?> pts
                        <?php if ($a['marks_obtained'] !== null): ?>
                            · Score: <strong><?= (float)$a['marks_obtained'] ?></strong>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="padding:var(--space-4);">
                    <p style="margin-bottom:var(--space-3);font-size:0.875rem;line-height:1.6;">
                        <?= nl2br(h($a['question_text'])) ?>
                    </p>

                    <?php if ($a['type'] === 'mcq'): ?>
                        <div style="background:var(--gray-5);padding:var(--space-3) var(--space-4);border-radius:var(--radius-sm);font-size:0.8125rem;">
                            <?php
                            $options = json_decode($a['options_json'], true) ?? [];
                            $selected = json_decode($a['answer_json'], true)['selected'] ?? '';
                            ?>
                            <strong>Options:</strong>
                            <ul style="margin:var(--space-2) 0 0 var(--space-4);">
                                <?php foreach ($options as $opt): ?>
                                    <li style="<?= $opt['key'] === $a['correct_answer'] ? 'color:var(--green);font-weight:600;' : ($opt['key'] === $selected ? 'color:var(--accent);font-weight:600;' : '') ?>">
                                        <?= h($opt['key']) ?>. <?= h($opt['text']) ?>
                                        <?php if ($opt['key'] === $a['correct_answer']): ?> <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;vertical-align:middle;"><path d="M16.7 5.3a1 1 0 0 0-1.4 0L8 12.6 4.7 9.3a1 1 0 0 0-1.4 1.4l4 4a1 1 0 0 0 1.4 0l8-8a1 1 0 0 0 0-1.4z"/></svg><?php endif; ?>
                                        <?php if ($opt['key'] === $selected): ?> <span class="badge badge-active" style="font-size:var(--fs-10);padding:0 6px;">Selected</span><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div style="margin-top:var(--space-2);color:var(--gray-50);">
                                Correct: <strong style="color:var(--green);"><?= h($a['correct_answer']) ?></strong>
                                · Selected: <strong><?= h($selected) ?: '—' ?></strong>
                                · <?= $selected === $a['correct_answer'] ? '<span style="color:var(--green);">Correct</span>' : '<span style="color:var(--red);">Incorrect</span>' ?>
                            </div>
                            <div style="margin-top:var(--space-2);">
                                <label class="text-sm text-muted">Override marks:</label>
                                <input type="number" class="form-input" style="width:100px;display:inline;"
                                       name="marks[<?= $a['id'] ?>]"
                                       value="<?= $a['marks_obtained'] !== null ? (float)$a['marks_obtained'] : ($selected === $a['correct_answer'] ? (int)$a['marks'] : 0) ?>"
                                       min="0" max="<?= (int)$a['marks'] ?>" step="0.5">
                                <span class="text-sm text-muted"> / <?= (int)$a['marks'] ?></span>
                            </div>
                        </div>

                    <?php elseif ($a['type'] === 'coding'): ?>
                        <div style="margin-bottom:var(--space-3);">
                            <strong style="font-size:0.75rem;color:var(--gray-50);text-transform:uppercase;letter-spacing:0.04em;">Student's Code</strong>
                            <pre class="code-editor" style="white-space:pre-wrap;overflow-x:auto;margin-top:4px;"><?= h(json_decode($a['answer_json'], true)['code'] ?? '') ?></pre>
                        </div>
                        <div style="margin-bottom:var(--space-3);">
                            <label style="font-size:0.75rem;font-weight:500;color:var(--gray-60);text-transform:uppercase;letter-spacing:0.04em;">Marks (0 – <?= (int)$a['marks'] ?>)</label>
                            <input type="number" class="form-input" style="width:120px;"
                                   name="marks[<?= $a['id'] ?>]"
                                   value="<?= $a['marks_obtained'] !== null ? (float)$a['marks_obtained'] : '' ?>"
                                   min="0" max="<?= (int)$a['marks'] ?>" step="0.5" required>
                        </div>

                    <?php elseif ($a['type'] === 'explanation'): ?>
                        <div style="margin-bottom:var(--space-3);">
                            <strong style="font-size:0.75rem;color:var(--gray-50);text-transform:uppercase;letter-spacing:0.04em;">Student's Explanation</strong>
                            <div class="code-output" style="margin-top:4px;white-space:pre-wrap;"><?= h(json_decode($a['answer_json'], true)['text'] ?? '') ?></div>
                        </div>
                        <div>
                            <label style="font-size:0.75rem;font-weight:500;color:var(--gray-60);text-transform:uppercase;letter-spacing:0.04em;">Marks (0 – <?= (int)$a['marks'] ?>)</label>
                            <input type="number" class="form-input" style="width:120px;"
                                   name="marks[<?= $a['id'] ?>]"
                                   value="<?= $a['marks_obtained'] !== null ? (float)$a['marks_obtained'] : '' ?>"
                                   min="0" max="<?= (int)$a['marks'] ?>" step="0.5" required>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:var(--space-4);">
                <div>
                    <button type="submit" class="btn btn-primary btn-lg">Save All Grades</button>
                    <span class="text-sm text-muted" style="margin-left:12px;">
                        This will mark the submission as <strong>Evaluated</strong>.
                    </span>
                </div>
                <a href="grading.php?test_id=<?= $currentTestId ?>" class="btn btn-secondary">Back to list</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($submissionInfo && empty($answers)): ?>
<div class="panel">
    <div class="panel-header"><h2>No Answers</h2></div>
    <div class="panel-body">
        <p class="text-muted">This submission has no answers recorded.</p>
        <a href="grading.php?test_id=<?= $currentTestId ?>" class="btn btn-secondary">Back</a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
