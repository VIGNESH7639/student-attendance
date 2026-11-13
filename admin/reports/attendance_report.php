<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$dept_id = $_GET['dept_id'] ?? null;
$batch_id = $_GET['batch_id'] ?? null;

$departments = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();

$batches = [];
if ($dept_id) {
    $stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift 
                            FROM Batch b 
                            JOIN Course c ON b.course_id = c.course_id 
                            WHERE c.dept_id = ? AND b.status = 1 
                            ORDER BY b.batch_name DESC");
    $stmt->execute([$dept_id]);
    $batches = $stmt->fetchAll();
}

$attendance_data = [];
if ($batch_id) {
    try {
        // Fetch students and their attendance percentages using the correct table structure
        $stmt = $conn->prepare("SELECT 
                                    s.register_no, 
                                    s.student_name, 
                                    COUNT(ad.att_detail_id) as total_days,
                                    SUM(CASE WHEN ad.status = 1 THEN 1 ELSE 0 END) as present_days
                                FROM Student s 
                                LEFT JOIN Attendance_Detail ad ON s.student_id = ad.student_id
                                LEFT JOIN Attendance a ON ad.attendance_id = a.attendance_id AND a.batch_id = s.batch_id
                                WHERE s.batch_id = ? AND s.status = 1 
                                GROUP BY s.student_id 
                                ORDER BY s.student_name ASC");
        $stmt->execute([$batch_id]);
        $attendance_data = $stmt->fetchAll();
    }
    catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $batch_id && !empty($attendance_data)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_summary_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S.No', 'Reg No', 'Student Name', 'Total Classes', 'Present Days', 'Percentage']);
    $sno = 1;
    foreach ($attendance_data as $row) {
        $pct = ($row['total_days'] > 0) ? round(($row['present_days'] / $row['total_days']) * 100, 2) : 0;
        fputcsv($output, [
            $sno++,
            $row['register_no'], 
            $row['student_name'], 
            $row['total_days'], 
            $row['present_days'], 
            $pct . '%'
        ]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Attendance Summary Report</h2>
        <p class="text-muted mb-0 small">Track overall attendance statistics for specific batches.</p>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold" <?php echo empty($attendance_data) ? 'disabled' : ''; ?>>
            <i class="bi bi-printer me-2"></i> Print PDF
        </button>
        <a href="?export=csv&batch_id=<?php echo $batch_id; ?>&dept_id=<?php echo $dept_id; ?>" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm <?php echo empty($attendance_data) ? 'disabled' : ''; ?>">
            <i class="bi bi-file-earmark-excel me-2"></i> Export CSV
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Select Department</label>
                <select name="dept_id" class="form-select border-0 bg-light rounded-3 py-2" onchange="this.form.submit()">
                    <option value="">-- Choose Department --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['dept_id']; ?>" <?php echo $dept_id == $d['dept_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Select Batch</label>
                <select name="batch_id" class="form-select border-0 bg-light rounded-3 py-2" required <?php echo empty($batches) ? 'disabled' : ''; ?>>
                    <option value="">-- Select Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $batch_id == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['batch_name'] . " (" . $b['course_name'] . " - Shift " . $b['shift'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">
                    <i class="bi bi-graph-up me-1"></i> View Statistics
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($batch_id && !empty($attendance_data)): ?>
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4 text-center">S.No</th>
                    <th>Reg No</th>
                    <th>Student Name</th>
                    <th class="text-center">Total Classes</th>
                    <th class="text-center">Present Days</th>
                    <th class="text-center">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach ($attendance_data as $row): 
                    $pct = ($row['total_days'] > 0) ? round(($row['present_days'] / $row['total_days']) * 100, 2) : 0;
                    $color = ($pct >= 75) ? 'success' : (($pct >= 65) ? 'warning' : 'danger');
                ?>
                    <tr>
                        <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                        <td class="font-monospace small"><?php echo htmlspecialchars($row['register_no']); ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td class="text-center"><?php echo $row['total_days']; ?></td>
                        <td class="text-center text-success fw-bold"><?php echo $row['present_days']; ?></td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <div class="progress flex-grow-1 d-none d-md-flex" style="height: 6px; width: 60px;">
                                    <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" style="width: <?php echo $pct; ?>%"></div>
                                </div>
                                <span class="badge bg-<?php echo $color; ?>-subtle text-<?php echo $color; ?> border border-<?php echo $color; ?>-subtle rounded-pill px-3">
                                    <?php echo $pct; ?>%
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($batch_id): ?>
<div class="alert alert-info rounded-4 border-0 shadow-sm">No attendance records found for this selection.</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
