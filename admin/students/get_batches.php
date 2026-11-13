<?php
require_once '../../includes/auth.php';
requireAdminOrHOD();
require_once '../../config/database.php';

if (isset($_GET['dept_id'])) {
    $dept_id = $_GET['dept_id'];
    
    try {
        $stmt = $conn->prepare("SELECT b.*, c.course_name, c.shift, 
                               (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id) AS student_count 
                               FROM Batch b 
                               JOIN Course c ON b.course_id = c.course_id 
                               WHERE c.dept_id = ? AND b.status = 1 
                               ORDER BY b.batch_name ASC");
        $stmt->execute([$dept_id]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($batches);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Department ID missing']);
}
?>
