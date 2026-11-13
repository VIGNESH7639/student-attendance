<?php
require_once '../../includes/auth.php';
requireAdminOrHOD();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (isset($_GET['course_sem_id'])) {
    $course_sem_id = (int)$_GET['course_sem_id'];
    
    try {
        $stmt = $conn->prepare("SELECT sub_id, sub_name, sub_code 
                                FROM Subject 
                                WHERE course_sem_id = ? AND status = 1 
                                ORDER BY sub_name");
        $stmt->execute([$course_sem_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($subjects);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
?>
