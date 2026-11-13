<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = (int)$_GET['id'];
$dept_id = $_SESSION['dept_id'];

try {
    // Security check: Ensure allocation belongs to HOD's department
    $stmt = $conn->prepare("SELECT ss.alloc_id FROM Staff_Subject ss 
                            JOIN Course_Semester cs ON ss.course_sem_id = cs.course_sem_id
                            JOIN Course c ON cs.course_id = c.course_id
                            WHERE ss.alloc_id = ? AND c.dept_id = ?");
    $stmt->execute([$id, $dept_id]);

    if ($stmt->fetch()) {
        $stmt = $conn->prepare("DELETE FROM Staff_Subject WHERE alloc_id = ?");
        $stmt->execute([$id]);
        header("Location: list.php?msg=Allocation deleted successfully.");
    }
    else {
        header("Location: list.php?error=Access denied or allocation not found.");
    }
    exit();
}
catch (PDOException $e) {
    header("Location: list.php?error=Error: " . $e->getMessage());
    exit();
}
