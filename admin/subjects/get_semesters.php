<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (isset($_GET['course_id'])) {
    $course_id = (int)$_GET['course_id'];
    
    try {
        $stmt = $conn->prepare("SELECT cs.course_sem_id, sem.semester_no 
                                FROM Course_Semester cs 
                                JOIN Semester sem ON cs.semester_id = sem.semester_id 
                                WHERE cs.course_id = ? 
                                ORDER BY sem.semester_no");
        $stmt->execute([$course_id]);
        $semesters = $stmt->fetchAll();
        
        echo json_encode($semesters);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
?>
