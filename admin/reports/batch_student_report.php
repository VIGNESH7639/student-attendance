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

$students = [];
if ($batch_id) {
    try {
        $stmt = $conn->prepare("SELECT s.*, b.batch_name, d.dept_name,
                                (SELECT sem.semester_no FROM Student_Semester ss 
                                 JOIN Semester sem ON ss.semester_id = sem.semester_id
                                 WHERE ss.student_id = s.student_id AND ss.is_active = 1 
                                 LIMIT 1) as current_semester_no
                                FROM Student s 
                                JOIN Batch b ON s.batch_id = b.batch_id 
                                JOIN Course c ON b.course_id = c.course_id
                                JOIN Department d ON c.dept_id = d.dept_id
                                WHERE s.batch_id = ? AND s.status = 1
                                ORDER BY s.student_name ASC");
        $stmt->execute([$batch_id]);
        $students = $stmt->fetchAll();
    }
    catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $batch_id) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="batch_student_report_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S.No', 'Register No', 'Name', 'Batch', 'Sem', 'Mobile', 'Email']);
    $sno = 1;
    foreach ($students as $row) {
        fputcsv($output, [$sno++, $row['register_no'], $row['student_name'], $row['batch_name'], 'Sem ' . ($row['current_semester_no'] ?? '-'), $row['mobile_no'], $row['email']]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Batch-wise Student Report</h2>
        <p class="text-muted mb-0 small">Generate student lists for specific department batches.</p>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold" <?php echo empty($students) ? 'disabled' : ''; ?>>
            <i class="bi bi-printer me-2"></i> Print
        </button>
        <a href="?export=csv&batch_id=<?php echo $batch_id; ?>&dept_id=<?php echo $dept_id; ?>" class="btn btn-success rounded-pill px-4 fw-bold <?php echo empty($students) ? 'disabled' : ''; ?>">
            <i class="bi bi-file-earmark-excel me-2"></i> Export
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Select Department</label>
                <select name="dept_id" id="dept_id" class="form-select border-0 bg-light rounded-3 py-2" onchange="this.form.submit()">
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
                <select name="batch_id" id="batch_id" class="form-select border-0 bg-light rounded-3 py-2" required <?php echo empty($batches) ? 'disabled' : ''; ?>>
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
                    <i class="bi bi-search me-1"></i> View Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($batch_id && !empty($students)): ?>
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold text-center text-uppercase"><?php echo htmlspecialchars($students[0]['dept_name'] . " - " . $students[0]['batch_name']); ?></h5>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4 text-center">S.No</th>
                    <th>Reg No</th>
                    <th>Student Name</th>
                    <th class="text-center">Current Sem</th>
                    <th>Mobile</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach ($students as $s): ?>
                    <tr>
                        <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                        <td class="font-monospace small"><?php echo htmlspecialchars($s['register_no']); ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($s['student_name']); ?></td>
                        <td class="text-center">Sem <?php echo $s['current_semester_no'] ?? '-'; ?></td>
                        <td><?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></td>
                        <td class="small"><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($batch_id): ?>
<div class="alert alert-info rounded-4 border-0 shadow-sm">No students found in this batch.</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
