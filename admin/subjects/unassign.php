<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php?tab=assignments");
    exit();
}

$id = $_GET['id'];

try {
    // Check if this assignment is used in staff allocations
    $stmt = $conn->prepare("SELECT COUNT(*) FROM staff_subject_allocation WHERE assignment_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        header("Location: list.php?tab=assignments&error=Cannot remove assignment: It is currently allocated to staff.");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM subject_batch_assignment WHERE assignment_id = ?");
    $stmt->execute([$id]);
    header("Location: list.php?tab=assignments&msg=Assignment removed successfully.");
    exit();
}
catch (PDOException $e) {
    header("Location: list.php?tab=assignments&error=Error: " . $e->getMessage());
    exit();
}
