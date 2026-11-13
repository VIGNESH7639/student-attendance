<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

try {
    $stmt = $conn->prepare("SELECT s.*, d.designation_name 
                            FROM Staff s 
                            LEFT JOIN Designation d ON s.designation_id = d.designation_id 
                            WHERE s.dept_id = ? 
                            ORDER BY s.staff_name ASC");
    $stmt->execute([$dept_id]);
    $staff = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="staff_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Register ID', 'Name', 'Mobile', 'Email', 'Designation', 'Status']);
    foreach ($staff as $row) {
        $status_text = ($row['status'] == 1) ? 'Active' : 'Inactive';
        fputcsv($output, [$row['staff_reg_id'], $row['staff_name'], $row['mobile_no'], $row['email'], $row['designation_name'], $status_text]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/hod_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Department Staff Report</h3>
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
                        <th>Register ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Designation</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['staff_reg_id']); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($s['staff_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($s['designation_name'] ?? '-'); ?></td>
                            <td>
                                <?php if ($s['status'] == 1): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php
    else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php
    endif; ?>
                            </td>
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
