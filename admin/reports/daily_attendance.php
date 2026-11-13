<?php
require_once '../../includes/auth.php';
// Redirect Admins away from Daily Attendance - user requested to remove it from Admin side
if ($_SESSION['role'] === 'Admin') {
    header("Location: index.php?error=access");
    exit();
}
requireRole('Admin'); // Fallback check
require_once '../../config/database.php';

$dept_id = $_GET['dept_id'] ?? null;
$batch_id = $_GET['batch_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

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

$report_data = [];
$students = [];
$attendance_matrix = [];

if ($batch_id && $date) {
    try {
        // Fetch Students
        $stmt = $conn->prepare("SELECT student_id, register_no, student_name FROM Student WHERE batch_id = ? AND status = 1 ORDER BY register_no ASC");
        $stmt->execute([$batch_id]);
        $students = $stmt->fetchAll();

        // Fetch Attendance for this Date and Batch
        $stmt = $conn->prepare("SELECT 
                                    ad.student_id, 
                                    a.hour_no, 
                                    ad.status,
                                    sub.sub_code,
                                    sub.sub_name
                                FROM Attendance a
                                JOIN Attendance_Detail ad ON a.attendance_id = ad.attendance_id
                                JOIN Subject sub ON a.sub_id = sub.sub_id
                                WHERE a.batch_id = ? AND a.attendance_date = ?");
        $stmt->execute([$batch_id, $date]);
        $att_records = $stmt->fetchAll();

        // Build matrix
        foreach ($att_records as $rec) {
            $attendance_matrix[$rec['student_id']][$rec['hour_no']] = [
                'status' => $rec['status'],
                'sub_code' => $rec['sub_code'],
                'sub_name' => $rec['sub_name']
            ];
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $batch_id && !empty($students)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="daily_attendance_' . $date . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['S.No', 'Reg No', 'Student Name', 'Period 1', 'Period 2', 'Period 3', 'Period 4', 'Period 5']);
    $sno = 1;
    foreach ($students as $s) {
        $row = [$sno++, $s['register_no'], $s['student_name']];
        for ($i = 1; $i <= 5; $i++) {
            if (isset($attendance_matrix[$s['student_id']][$i])) {
                $row[] = ($attendance_matrix[$s['student_id']][$i]['status'] == 1) ? 'Present' : 'Absent';
            } else {
                $row[] = '-';
            }
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-success"><i class="bi bi-calendar-day me-2"></i>Daily Attendance Report</h2>
        <p class="text-muted mb-0 small">Period-wise daily attendance records for cross-checking.</p>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <button onclick="window.print()" class="btn btn-outline-success rounded-pill px-4 fw-bold" <?php echo empty($students) ? 'disabled' : ''; ?>>
            <i class="bi bi-printer me-2"></i> Print PDF
        </button>
        <a href="?export=csv&batch_id=<?php echo $batch_id; ?>&dept_id=<?php echo $dept_id; ?>&date=<?php echo $date; ?>" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm <?php echo empty($students) ? 'disabled' : ''; ?>">
            <i class="bi bi-file-earmark-excel me-2"></i> Export CSV
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Select Date</label>
                <input type="date" name="date" class="form-control border-0 bg-light rounded-3 py-2" value="<?php echo htmlspecialchars($date); ?>" required onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
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
                <select name="batch_id" class="form-select border-0 bg-light rounded-3 py-2" required <?php echo empty($batches) ? 'disabled' : ''; ?> onchange="this.form.submit()">
                    <option value="">-- Select Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $batch_id == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['batch_name'] . " (" . $b['course_name'] . " - Shift " . $b['shift'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100 rounded-3 py-2 fw-bold shadow-sm">
                    <i class="bi bi-search me-1"></i> Retrieve
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($batch_id && !empty($students)): ?>
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-person-check text-success me-2"></i> 
            Attendance for <?php echo date('d M Y', strtotime($date)); ?>
        </h5>
        <div class="small fw-bold px-3 py-1 bg-light text-muted border rounded-pill">Total Students: <?php echo count($students); ?></div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase text-center">
                <tr>
                    <th rowspan="2" class="align-middle ps-4 text-center border-bottom-0" style="width: 5%">S.No</th>
                    <th rowspan="2" class="align-middle text-start border-bottom-0" style="width: 15%">Reg No</th>
                    <th rowspan="2" class="align-middle text-start border-bottom-0" style="width: 20%">Student Name</th>
                    <th colspan="5" class="py-2 border-bottom-0">Periods / Hours</th>
                </tr>
                <tr>
                    <?php for($i=1; $i<=5; $i++): ?>
                        <th class="py-2" style="width: 12%">Period <?php echo $i; ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach ($students as $s): ?>
                    <tr>
                        <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                        <td class="font-monospace small fw-bold text-dark"><?php echo htmlspecialchars($s['register_no']); ?></td>
                        <td class="fw-bold text-secondary"><?php echo htmlspecialchars($s['student_name']); ?></td>
                        <?php for ($period = 1; $period <= 5; $period++): ?>
                            <td class="text-center p-2">
                                <?php 
                                if (isset($attendance_matrix[$s['student_id']][$period])) {
                                    $record = $attendance_matrix[$s['student_id']][$period];
                                    if ($record['status'] == 1) {
                                        echo '<div class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-3 py-1 px-2 d-inline-block w-100 shadow-xs" title="Present - '.htmlspecialchars($record['sub_name']).'">';
                                        echo '<i class="bi bi-check-circle-fill me-1"></i> <span class="d-print-none">Pres.</span><span class="d-none d-print-inline">P</span>';
                                        echo '<div class="fw-bold mt-1 text-dark d-print-none" style="font-size: 0.65rem;">'.htmlspecialchars($record['sub_code']).'</div>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-3 py-1 px-2 d-inline-block w-100 shadow-xs" title="Absent - '.htmlspecialchars($record['sub_name']).'">';
                                        echo '<i class="bi bi-x-circle-fill me-1"></i> <span class="d-print-none">Abs.</span><span class="d-none d-print-inline">A</span>';
                                        echo '<div class="fw-bold mt-1 text-dark d-print-none" style="font-size: 0.65rem;">'.htmlspecialchars($record['sub_code']).'</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="badge bg-light text-muted border py-1 px-3 d-inline-block shadow-xs">';
                                    echo '<i class="bi bi-dash"></i>';
                                    echo '</div>';
                                }
                                ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($batch_id): ?>
<div class="alert alert-info rounded-4 border-0 shadow-sm"><i class="bi bi-info-circle me-2"></i> No students found for this batch.</div>
<?php endif; ?>

<style>
.shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.table-bordered > :not(caption) > * > * { border-width: 1px 1px; }

@media print {
    body { background-color: white !important; }
    .navbar, .sidebar, .btn-primary { display: none !important; }
    .app-container { margin-left: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    .badge { border: 1px solid #dee2e6 !important; background: transparent !important; color: #000 !important; }
    .text-success, .text-danger { color: #000 !important; }
    .table td, .table th { padding: 0.25rem !important; border-color: #dee2e6 !important; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
