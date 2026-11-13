<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

try {
    // Fetch filter options
    $staff_list = $conn->prepare("SELECT staff_id, staff_name FROM Staff WHERE dept_id = ? AND status = 1 ORDER BY staff_name");
    $staff_list->execute([$dept_id]);
    $staff_list = $staff_list->fetchAll();

    $batch_list = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE c.dept_id = ? AND b.status = 1 ORDER BY b.batch_name");
    $batch_list->execute([$dept_id]);
    $batch_list = $batch_list->fetchAll();

    $subject_list = $conn->prepare("SELECT DISTINCT sub.sub_id, sub.sub_name, sub.sub_code FROM Subject sub JOIN Course_Semester cs ON sub.course_sem_id = cs.course_sem_id JOIN Course c ON cs.course_id = c.course_id WHERE c.dept_id = ? AND sub.status = 1 ORDER BY sub.sub_name");
    $subject_list->execute([$dept_id]);
    $subject_list = $subject_list->fetchAll();

    // Get filter inputs
    $f_staff_id = $_GET['staff_id'] ?? '';
    $f_batch_id = $_GET['batch_id'] ?? '';
    $f_sub_id = $_GET['sub_id'] ?? '';

    $query = "SELECT ssa.*, st.staff_name, sub.sub_name, sub.sub_code, b.batch_name 
              FROM staff_subject ssa 
              JOIN Staff st ON ssa.staff_id = st.staff_id 
              JOIN Subject sub ON ssa.sub_id = sub.sub_id 
              JOIN Batch b ON ssa.batch_id = b.batch_id 
              WHERE st.dept_id = ?";
    
    $params = [$dept_id];

    if ($f_staff_id !== '') {
        $query .= " AND ssa.staff_id = ?";
        $params[] = $f_staff_id;
    }
    if ($f_batch_id !== '') {
        $query .= " AND ssa.batch_id = ?";
        $params[] = $f_batch_id;
    }
    if ($f_sub_id !== '') {
        $query .= " AND ssa.sub_id = ?";
        $params[] = $f_sub_id;
    }

    $query .= " ORDER BY ssa.academic_year DESC, b.batch_name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $allocations = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subject_allocation_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Staff', 'Subject', 'Subject Code', 'Batch', 'Academic Year', 'Status']);
    foreach ($allocations as $row) {
        $status_text = ($row['status'] == 1) ? 'Active' : 'Inactive';
        fputcsv($output, [$row['staff_name'], $row['sub_name'], $row['sub_code'], $row['batch_name'], $row['academic_year'], $status_text]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/hod_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold">Subject Allocation Report</h3>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary d-print-none"><i class="bi bi-printer"></i> Print PDF</button>
        <a href="?export=csv&staff_id=<?php echo $f_staff_id; ?>&batch_id=<?php echo $f_batch_id; ?>&sub_id=<?php echo $f_sub_id; ?>" class="btn btn-success d-print-none"><i class="bi bi-file-earmark-excel"></i> Export CSV</a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Staff Member</label>
                <select class="form-select border-0 bg-light rounded-3 py-2" name="staff_id">
                    <option value="">-- All Staff --</option>
                    <?php foreach ($staff_list as $s): ?>
                        <option value="<?php echo $s['staff_id']; ?>" <?php echo $f_staff_id == $s['staff_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['staff_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Batch</label>
                <select class="form-select border-0 bg-light rounded-3 py-2" name="batch_id">
                    <option value="">-- All Batches --</option>
                    <?php foreach ($batch_list as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $f_batch_id == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Subject</label>
                <select class="form-select border-0 bg-light rounded-3 py-2" name="sub_id">
                    <option value="">-- All Subjects --</option>
                    <?php foreach ($subject_list as $sub): ?>
                        <option value="<?php echo $sub['sub_id']; ?>" <?php echo $f_sub_id == $sub['sub_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sub['sub_name'] . " [" . $sub['sub_code'] . "]"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-3 py-2 fw-bold flex-fill">Filter</button>
                <a href="subject_allocation_report.php" class="btn btn-light rounded-3 py-2 fw-bold text-muted border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body">
        <div class="table-container border-0">
            <table class="table table-bordered align-middle">
                <thead class="bg-light text-dark">
                    <tr>
                        <th>Staff Member</th>
                        <th>Subject</th>
                        <th>Batch</th>
                        <th>Academic Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allocations)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No subject allocations found.</td></tr>
                    <?php
else: ?>
                        <?php foreach ($allocations as $a): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($a['staff_name']); ?></td>
                                <td><?php echo htmlspecialchars($a['sub_name']); ?> (<?php echo $a['sub_code']; ?>)</td>
                                <td><?php echo htmlspecialchars($a['batch_name']); ?></td>
                                <td><?php echo htmlspecialchars($a['academic_year']); ?></td>
                                <td class="text-center">
                                    <?php if ($a['status'] == 1): ?>
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
