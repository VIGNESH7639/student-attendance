<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

try {
    $stmt = $conn->prepare("SELECT t.*, st.staff_name, b.batch_name 
                            FROM tutor t 
                            JOIN Staff st ON t.staff_id = st.staff_id 
                            JOIN Batch b ON t.batch_id = b.batch_id 
                            WHERE st.dept_id = ?
                            ORDER BY t.academic_year DESC, b.batch_name ASC");
    $stmt->execute([$dept_id]);
    $tutors = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tutor_allocation_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Batch', 'Staff (Tutor)', 'Academic Year', 'Status']);
    foreach ($tutors as $row) {
        $status_text = ($row['status'] == 1) ? 'Active' : 'Inactive';
        fputcsv($output, [$row['batch_name'], $row['staff_name'], $row['academic_year'], $status_text]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/hod_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Tutor Allocation Report</h3>
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
                        <th>Batch Name</th>
                        <th>Tutor (Staff Name)</th>
                        <th>Academic Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tutors)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No tutor assignments found.</td></tr>
                    <?php
else: ?>
                        <?php foreach ($tutors as $t): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($t['batch_name']); ?></td>
                                <td><?php echo htmlspecialchars($t['staff_name']); ?></td>
                                <td><?php echo htmlspecialchars($t['academic_year']); ?></td>
                                <td class="text-center">
                                    <?php if ($t['status'] == 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
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
