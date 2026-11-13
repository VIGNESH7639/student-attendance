<?php
require_once 'auth.php';
require_once '../config/database.php';

// Verify access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['HOD', 'Staff', 'Admin'])) {
    http_response_code(403);
    die("Access denied.");
}

// Get filters
$batch_id = isset($_GET['batch_id']) ? $_GET['batch_id'] : '';
$dept_id = isset($_GET['dept_id']) ? $_GET['dept_id'] : '';

$where_clauses = ["s.status = 1"];
$params = [];

if ($batch_id !== '') {
    $where_clauses[] = "s.batch_id = ?";
    $params[] = $batch_id;
}
if ($dept_id !== '') {
    $where_clauses[] = "c.dept_id = ?";
    $params[] = $dept_id;
}

// Special restriction for Staff (Tutor mode)
if ($_SESSION['role'] === 'Staff') {
    $stmt = $conn->prepare("SELECT batch_id FROM Tutor WHERE staff_id = (SELECT staff_id FROM Staff WHERE user_id = ?) AND status = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $tutor_batch = $stmt->fetchColumn();
    if ($tutor_batch) {
        $where_clauses[] = "s.batch_id = ?";
        $params[] = $tutor_batch;
    }
}

$query = "SELECT s.register_no as 'Register No', s.student_name as 'Student Name', 
                 s.gender as 'Gender', b.batch_name as 'Batch', s.email as 'Email', 
                 s.mobile_no as 'Mobile No', s.dob as 'DOB', s.address as 'Address'
          FROM Student s
          JOIN Batch b ON s.batch_id = b.batch_id
          JOIN Course c ON b.course_id = c.course_id
          WHERE " . implode(" AND ", $where_clauses) . "
          ORDER BY b.batch_name DESC, s.register_no ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filename = "students_export_" . date('Ymd') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

if (count($students) > 0) {
    // Write headers
    fputcsv($output, array_keys($students[0]));
    
    // Write data rows
    foreach ($students as $row) {
        fputcsv($output, $row);
    }
} else {
    // Write empty headers if no data
    fputcsv($output, ['Register No', 'Student Name', 'Gender', 'Batch', 'Email', 'Mobile No', 'DOB', 'Address']);
}

fclose($output);
exit;
