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
    $stmt = $conn->prepare("DELETE FROM Department WHERE dept_id = ?");
    $stmt->execute([$id]);
    header("Location: list.php?msg=Department deleted successfully.");
    exit();
}
catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header("Location: list.php?error=Cannot delete department as it is linked to other records.");
    } else {
        header("Location: list.php?error=Error deleting department: " . $e->getMessage());
    }
    exit();
}
?>
