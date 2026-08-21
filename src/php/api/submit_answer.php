<?php
/**
 * Submit / Auto-save Answers.
 * POST only. Stores answers in real-time.
 *
 * Payload:
 *  - Accepts standard form data (auto-save / no-JS fallback)
 *  - Accepts JSON bodies: {"test_id":..,"submission_id":..,"auto_save":false,"answer":{..},"question_ids":[...]}
 *
 * Integrity guarantees:
 *  - All answer upserts + final submission transition run inside ONE transaction.
 *  - The submit transition is conditional (WHERE status = 'in_progress') so rapid
 *    double-clicks can never flip the submission twice or corrupt scores.
 *  - student_answers carries UNIQUE KEY (submission_id, question_id) and uses
 *    ON DUPLICATE KEY UPDATE (submission_id maps 1:1 to (student_id, test_id)),
 *    giving database-level idempotency per (test, student, question).
 */
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = parseRequestBody();

if (!isset($input['csrf_token']) || !validateCsrfToken($input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$testId = (int)($input['test_id'] ?? 0);
$submissionId = $input['submission_id'] ?? '';
$autoSave = !empty($input['auto_save']);
$action = $input['action'] ?? '';
$answers = $input['answer'] ?? [];
$questionIds = $input['question_ids'] ?? [];

if (!is_array($answers)) $answers = [];
if (!is_array($questionIds)) $questionIds = [];

if ($testId <= 0 || empty($submissionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing test or submission info']);
    exit;
}

$pdo = getDB();

// Handle guest answers (stored in guest_entries.temp_data)
if (strpos($submissionId, 'guest_') === 0) {
    $guestEntryId = (int)str_replace('guest_', '', $submissionId);

    if (strpos($action, 'submit') !== false || !$autoSave) {
        // For guests, store answers in guest_entries.temp_data JSON
        $stmt = $pdo->prepare("SELECT temp_data FROM guest_entries WHERE id = ?");
        $stmt->execute([$guestEntryId]);
        $entry = $stmt->fetch();
        if (!$entry) {
            http_response_code(404);
            echo json_encode(['error' => 'Guest entry not found']);
            exit;
        }
        $tempData = json_decode($entry['temp_data'] ?? '{}', true) ?: [];
        $tempData['answers'] = $answers;
        $tempData['submitted_at'] = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("UPDATE guest_entries SET temp_data = ? WHERE id = ?");
        $stmt->execute([json_encode($tempData), $guestEntryId]);
    }

    echo json_encode(['success' => true, 'message' => 'Guest answers saved']);
    exit;
}

// Logged-in student path
// Check submission exists and is not already submitted
$stmt = $pdo->prepare("SELECT id, status FROM submissions WHERE id = ? AND test_id = ?");
$stmt->execute([$submissionId, $testId]);
$submission = $stmt->fetch();

if (!$submission) {
    http_response_code(404);
    echo json_encode(['error' => 'Submission not found']);
    exit;
}

// CRITICAL: Block if already submitted
if ($submission['status'] === 'submitted' || $submission['status'] === 'evaluated') {
    http_response_code(400);
    echo json_encode(['error' => 'Test already submitted', 'status' => $submission['status']]);
    exit;
}

// Resolve question types in ONE query (avoids per-answer round trips)
$questionTypes = [];
if (!empty($answers)) {
    $ids = array_keys($answers);
    $ids = array_filter($ids, fn($q) => (int)$q > 0);
    if (!empty($ids)) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $qStmt = $pdo->prepare("SELECT id, type FROM questions WHERE id IN ($ph)");
        $qStmt->execute(array_map('intval', $ids));
        foreach ($qStmt->fetchAll() as $qr) {
            $questionTypes[(int)$qr['id']] = $qr['type'];
        }
    }
}

// All-or-nothing: answer upserts + (for final submit) status transition in one transaction
$pdo->beginTransaction();
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO student_answers (submission_id, question_id, answer_json, submitted_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            answer_json = VALUES(answer_json),
            submitted_at = NOW()
    ");

    foreach ($answers as $qId => $answer) {
        $qId = (int)$qId;
        if ($qId <= 0) continue;

        // Format answer as JSON depending on question type
        $answerData = [];
        $qType = $questionTypes[$qId] ?? null;
        if ($qType === 'mcq') {
            $answerData = ['selected' => $answer];
        } elseif ($qType === 'coding') {
            $answerData = ['code' => is_array($answer) ? ($answer['code'] ?? '') : $answer];
        } elseif ($qType === 'explanation') {
            $answerData = ['text' => is_array($answer) ? ($answer['text'] ?? '') : $answer];
        } elseif ($qType === null) {
            // Question no longer exists — keep payload as-is instead of crashing the batch
            $answerData = is_array($answer) ? $answer : ['value' => $answer];
        }

        $insertStmt->execute([$submissionId, $qId, json_encode($answerData)]);
    }

    $submittedFlag = false;
    // If final submission (not auto-save)
    if (!$autoSave) {
        // Calculate total marks for the test
        $marksStmt = $pdo->prepare("SELECT COALESCE(SUM(marks), 0) FROM questions WHERE test_id = ?");
        $marksStmt->execute([$testId]);
        $totalMarks = $marksStmt->fetchColumn();

        // Idempotent transition: only flips from in_progress → submitted.
        // A concurrent/duplicate request finds rowCount() === 0 and is a no-op.
        $stmt = $pdo->prepare("
            UPDATE submissions
            SET status = 'submitted', submitted_at = NOW(), total_marks = ?
            WHERE id = ? AND status = 'in_progress'
        ");
        $stmt->execute([$totalMarks, $submissionId]);
        $submittedFlag = $stmt->rowCount() > 0;
    }

    $pdo->commit();

    if (!$autoSave) {
        echo json_encode([
            'success' => true,
            'submitted' => true,
            'duplicate' => !$submittedFlag,
            'message' => $submittedFlag ? 'Test submitted successfully' : 'Test was already submitted',
        ]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Answers saved']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('submit_answer failed for submission ' . $submissionId . ': ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save answers. Please try again.']);
    exit;
}