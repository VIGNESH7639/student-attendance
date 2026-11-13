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
    $stmt = $conn->prepare("DELETE FROM Batch WHERE batch_id = ?");
    $stmt->execute([$id]);
    header("Location: list.php?msg=Batch deleted successfully.");
    exit();
}
catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header("Location: list.php?error=Cannot delete batch as it is linked to other records (like students or attendance).");
    } else {
        header("Location: list.php?error=Error deleting batch: " . $e->getMessage());
    }
    exit();
}
?>
