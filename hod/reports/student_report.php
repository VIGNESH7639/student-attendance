<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

try {
    $stmt = $conn->prepare("SELECT s.*, b.batch_name, 
                            (SELECT ss.semester_id FROM Student_Semester ss 
                             WHERE ss.student_id = s.student_id AND ss.is_active = 1 
                             LIMIT 1) as current_semester
                            FROM Student s 
                            JOIN Batch b ON s.batch_id = b.batch_id 
                            JOIN Course c ON b.course_id = c.course_id
                            WHERE c.dept_id = ? AND s.status = 1
                            ORDER BY b.batch_name ASC, s.student_name ASC");
    $stmt->execute([$dept_id]);
    $students = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Register No', 'Name', 'Batch', 'Current Sem', 'Mobile', 'Email']);
    foreach ($students as $row) {
        fputcsv($output, [$row['register_no'], $row['student_name'], $row['batch_name'], 'Sem ' . ($row['current_semester'] ?? '-'), $row['mobile_no'], $row['email']]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/hod_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Department Student Report</h3>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary d-print-none"><i class="bi bi-printer"></i> Print PDF</button>
        <a href="?export=csv" class="btn btn-success d-print-none"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-container border-0">
            <table class="table table-bordered align-middle">
                <thead class="bg-light text-dark">
                    <tr>
                        <th>Reg No</th>
                        <th>Name</th>
                        <th>Batch</th>
                        <th>Sem</th>
                        <th>Mobile</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td class="font-monospace small"><?php echo htmlspecialchars($s['register_no']); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($s['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['batch_name']); ?></td>
                            <td>Sem <?php echo $s['current_semester'] ?? '-'; ?></td>
                            <td><?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></td>
                            <td><small><?php echo htmlspecialchars($s['email'] ?? '-'); ?></small></td>
                        </tr>
                    <?php
endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php

// Wrapper close
?>
        </div> <!-- Fixed mismatched tag -->
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
