<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

try {
    $query = "SELECT course_id, course_name, shift, course_type FROM Course WHERE status = 1";
    $params = [];
    if (isset($_GET['dept_id']) && !empty($_GET['dept_id'])) {
        $query .= " AND dept_id = ?";
        $params[] = (int)$_GET['dept_id'];
    }
    $query .= " ORDER BY course_name, shift";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
    
    echo json_encode($courses);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
