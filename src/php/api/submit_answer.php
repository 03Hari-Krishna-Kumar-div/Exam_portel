<?php
/**
 * Submit / Auto-save Answers.
 * POST only. Stores answers in real-time.
 * Blocks submission if already submitted.
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

if (!validateCsrfToken()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$testId = (int)($_POST['test_id'] ?? 0);
$submissionId = $_POST['submission_id'] ?? '';
$autoSave = !empty($_POST['auto_save']);
$answers = $_POST['answer'] ?? [];
$questionIds = $_POST['question_ids'] ?? [];

if ($testId <= 0 || empty($submissionId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing test or submission info']);
    exit;
}

$pdo = getDB();

// Handle guest answers (stored in guest_entries.temp_data)
if (strpos($submissionId, 'guest_') === 0) {
    $guestEntryId = (int)str_replace('guest_', '', $submissionId);

    if (strpos($_POST['action'] ?? '', 'submit') !== false || !$autoSave) {
        // For guests, store answers in guest_entries.temp_data JSON
        $stmt = $pdo->prepare("SELECT temp_data FROM guest_entries WHERE id = ?");
        $stmt->execute([$guestEntryId]);
        $entry = $stmt->fetch();
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

// Save answers
$insertStmt = $pdo->prepare("
    INSERT INTO student_answers (submission_id, question_id, answer_json, submitted_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE answer_json = VALUES(answer_json), submitted_at = NOW()
");

foreach ($answers as $qId => $answer) {
    $qId = (int)$qId;
    if ($qId <= 0) continue;

    // Format answer as JSON depending on question type
    // PHP sends it as string — we store as JSON
    $answerData = [];
    $qStmt = $pdo->prepare("SELECT type FROM questions WHERE id = ?");
    $qStmt->execute([$qId]);
    $qRow = $qStmt->fetch();

    if ($qRow) {
        if ($qRow['type'] === 'mcq') {
            $answerData = ['selected' => $answer];
        } elseif ($qRow['type'] === 'coding') {
            $answerData = ['code' => $answer];
        } elseif ($qRow['type'] === 'explanation') {
            $answerData = ['text' => $answer];
        }
    }

    $insertStmt->execute([$submissionId, $qId, json_encode($answerData)]);
}

// If final submission (not auto-save)
if (!$autoSave) {
    // Calculate total marks for the test
    $marksStmt = $pdo->prepare("SELECT SUM(marks) FROM questions WHERE test_id = ?");
    $marksStmt->execute([$testId]);
    $totalMarks = $marksStmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("
        UPDATE submissions
        SET status = 'submitted', submitted_at = NOW(), total_marks = ?
        WHERE id = ?
    ");
    $stmt->execute([$totalMarks, $submissionId]);

    // Clear any pending tab switch logs (lock them)
    echo json_encode(['success' => true, 'submitted' => true, 'message' => 'Test submitted successfully']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Answers saved']);
