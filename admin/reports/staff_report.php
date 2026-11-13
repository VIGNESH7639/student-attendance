<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$dept_filter = $_GET['dept_id'] ?? null;
$departments = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();

try {
    $query = "SELECT s.*, d.dept_name 
              FROM Staff s 
              JOIN Department d ON s.dept_id = d.dept_id";
              
    $params = [];
    if ($dept_filter) {
        $query .= " WHERE s.dept_id = ?";
        $params[] = $dept_filter;
    }
    
    $query .= " ORDER BY d.dept_name ASC, s.staff_name ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $staff = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="overall_staff_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S.No', 'Department', 'Name', 'Reg ID', 'Mobile', 'Email', 'Status']);
    $sno = 1;
    foreach ($staff as $row) {
        $status_text = ($row['status'] == 1) ? 'Active' : 'Inactive';
        fputcsv($output, [$sno++, $row['dept_name'], $row['staff_name'], $row['staff_reg_id'], $row['mobile_no'], $row['email'], $status_text]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Overall Staff Report</h2>
        <p class="text-muted mb-0 small">Directory of all teaching and non-teaching staff across college.</p>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
            <i class="bi bi-printer me-2"></i> Print Report
        </button>
        <a href="?export=csv<?php echo $dept_filter ? '&dept_id='.$dept_filter : ''; ?>" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-file-earmark-excel me-2"></i> Export CSV
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Filter by Department</label>
                <select name="dept_id" class="form-select border-0 bg-light rounded-3 py-2">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['dept_id']; ?>" <?php echo $dept_filter == $d['dept_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
            </div>
            <?php if ($dept_filter): ?>
            <div class="col-md-2">
                <a href="staff_report.php" class="btn btn-light border w-100 rounded-3 py-2 fw-bold">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4 text-center">S.No</th>
                    <th>Dept</th>
                    <th>Staff Name</th>
                    <th>Reg ID</th>
                    <th>Contact Info</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach ($staff as $s): ?>
                    <tr>
                        <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                        <td><span class="small fw-bold text-primary"><?php echo htmlspecialchars($s['dept_name']); ?></span></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($s['staff_name']); ?></td>
                        <td class="font-monospace small"><?php echo htmlspecialchars($s['staff_reg_id']); ?></td>
                        <td class="small">
                            <div class="fw-bold"><i class="bi bi-telephone me-1 opacity-50"></i> <?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></div>
                            <div class="text-muted"><i class="bi bi-envelope me-1 opacity-50"></i> <?php echo htmlspecialchars($s['email'] ?? '-'); ?></div>
                        </td>
                        <td class="text-center">
                            <?php if ($s['status'] == 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($staff)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No staff found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
