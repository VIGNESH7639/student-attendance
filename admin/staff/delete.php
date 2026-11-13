<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];

try {
    $conn->beginTransaction();

    // Get user_id first
    $stmt = $conn->prepare("SELECT user_id FROM Staff WHERE staff_id = ?");
    $stmt->execute([$id]);
    $user_id = $stmt->fetchColumn();

    if ($user_id) {
        // Delete from Staff first (foreign key constraint)
        $stmt = $conn->prepare("DELETE FROM Staff WHERE staff_id = ?");
        $stmt->execute([$id]);

        // Then delete from Users
        $stmt = $conn->prepare("DELETE FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }

    $conn->commit();
    header("Location: list.php?msg=Staff member deleted successfully.");
    exit();
}
catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    if ($e->getCode() == 23000) {
        header("Location: list.php?error=Cannot delete staff member as they are linked to other records (like subjects or attendance).");
    } else {
        header("Location: list.php?error=Error deleting staff: " . $e->getMessage());
    }
    exit();
}
?>
