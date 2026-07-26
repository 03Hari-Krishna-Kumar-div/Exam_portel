<?php
/**
 * AJAX: Get batches for a course.
 */
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$courseId = (int)($_GET['course_id'] ?? 0);
if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid course ID']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id, name FROM batches WHERE course_id = ? ORDER BY name");
$stmt->execute([$courseId]);
$batches = $stmt->fetchAll();

echo json_encode($batches);
