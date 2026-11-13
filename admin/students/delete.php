<?php
require_once '../../includes/auth.php';
requireAdminOrHOD();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];
$batch_id = $_GET['batch_id'] ?? null;

try {
    $conn->beginTransaction();

    // Get user_id first
    $stmt = $conn->prepare("SELECT user_id FROM Student WHERE student_id = ?");
    $stmt->execute([$id]);
    $user_id = $stmt->fetchColumn();

    if ($user_id) {
        // Delete from Student_Semester first
        $stmt = $conn->prepare("DELETE FROM Student_Semester WHERE student_id = ?");
        $stmt->execute([$id]);

        // Delete from Student
        $stmt = $conn->prepare("DELETE FROM Student WHERE student_id = ?");
        $stmt->execute([$id]);

        // Finally delete from Users
        $stmt = $conn->prepare("DELETE FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    $conn->commit();
    $redirect = "list.php?msg=Student deleted successfully.";
    if ($batch_id) $redirect .= "&batch_id=" . $batch_id;
    header("Location: " . $redirect);
    exit();
}
catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $error_msg = "Error deleting student: ";
    if ($e->getCode() == 23000) {
        $error_msg = "Cannot delete student as they are linked to other records (attendance, marks, etc.).";
    } else {
        $error_msg .= $e->getMessage();
    }
    
    $redirect = "list.php?error=" . urlencode($error_msg);
    if ($batch_id) $redirect .= "&batch_id=" . $batch_id;
    header("Location: " . $redirect);
    exit();
}
?>
