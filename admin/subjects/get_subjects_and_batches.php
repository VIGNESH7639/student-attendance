<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['dept_id'])) {
    echo json_encode(['subjects' => [], 'batches' => []]);
    exit();
}

$dept_id = $_GET['dept_id'];

try {
    $stmt = $conn->prepare("SELECT subject_id, subject_name, subject_code FROM subject WHERE department_id = ? AND subject_status = 'Active' ORDER BY subject_name");
    $stmt->execute([$dept_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT batch_id, batch_name FROM batch WHERE department_id = ? AND status = 'Active' ORDER BY batch_name");
    $stmt->execute([$dept_id]);
    $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'subjects' => $subjects,
        'batches' => $batches
    ]);
}
catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
