<?php
require_once '../../includes/auth.php';
requireHODorTutor();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

if (isHOD()) {
    $stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE c.dept_id = ? AND b.status = 1 ORDER BY c.course_name, b.batch_name");
    $stmt->execute([$dept_id]);
    $batches = $stmt->fetchAll();
} else {
    $stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift FROM Batch b JOIN Tutor t ON b.batch_id = t.batch_id JOIN Staff s ON t.staff_id = s.staff_id JOIN Course c ON b.course_id = c.course_id WHERE s.user_id = ? AND t.status = 1 AND b.status = 1 ORDER BY c.course_name, b.batch_name");
    $stmt->execute([$_SESSION['user_id']]);
    $batches = $stmt->fetchAll();
}

$selected_batch = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;
$selected_semester = isset($_GET['semester_id']) ? $_GET['semester_id'] : null;
$stats = [];
$semesters = [];

if ($selected_batch) {
    // Get available semesters for this batch's course
    $stmt = $conn->prepare("SELECT cs.semester_id, s.semester_no 
                           FROM Batch b 
                           JOIN Course_Semester cs ON b.course_id = cs.course_id 
                           JOIN Semester s ON cs.semester_id = s.semester_id 
                           WHERE b.batch_id = ? 
                           ORDER BY s.semester_no ASC");
    $stmt->execute([$selected_batch]);
    $semesters = $stmt->fetchAll();
    // Security check
    $stmt = $conn->prepare("SELECT b.batch_name FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE b.batch_id = ? AND c.dept_id = ?");
    $stmt->execute([$selected_batch, $dept_id]);
    $batch_info = $stmt->fetch();

    if ($batch_info) {
        $params = [$selected_batch];
        $sem_filter = "";
        if ($selected_semester) {
            $sem_filter = " AND cs.semester_id = ? ";
            $params[] = $selected_semester;
        }
        $params[] = $selected_batch;

        $query = "SELECT 
                    s.register_no, 
                    s.student_name as name, 
                    COUNT(fa.att_detail_id) as total_sessions,
                    SUM(CASE WHEN fa.status = 1 THEN 1 ELSE 0 END) as present_sessions
                FROM Student s 
                LEFT JOIN (
                    SELECT ad.att_detail_id, ad.student_id, ad.status
                    FROM attendance_detail ad
                    JOIN attendance a ON ad.attendance_id = a.attendance_id
                    JOIN Subject sub ON a.sub_id = sub.sub_id
                    JOIN Course_Semester cs ON sub.course_sem_id = cs.course_sem_id
                    WHERE a.batch_id = ? $sem_filter
                ) as fa ON s.student_id = fa.student_id
                WHERE s.batch_id = ? AND s.status = 1 
                GROUP BY s.student_id 
                ORDER BY s.student_name ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $stats = $stmt->fetchAll();

        // 2. Fetch the Absolute Total Sessions conducted for this batch (optionally filtered by semester)
        $total_query = "SELECT COUNT(*) FROM Attendance a 
                       JOIN Subject sub ON a.sub_id = sub.sub_id
                       JOIN Course_Semester cs ON sub.course_sem_id = cs.course_sem_id
                       WHERE a.batch_id = ?";
        $total_params = [$selected_batch];
        if ($selected_semester) {
            $total_query .= " AND cs.semester_id = ?";
            $total_params[] = (int)$selected_semester;
        }
        $stmt_total = $conn->prepare($total_query);
        $stmt_total->execute($total_params);
        $batch_grand_total = $stmt_total->fetchColumn();
    }
}

if (isHOD()) {
    require_once '../../includes/hod_header.php';
} else {
    require_once '../../includes/header.php';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary"><i class="bi bi-calendar-check-fill me-2"></i>Attendance Master Report</h2>
        <p class="text-muted mb-0 small"><?php echo $selected_batch ? "Cumulative attendance analytics for <strong>" . htmlspecialchars($batch_info['batch_name']) . "</strong>." : "Select a batch to view detailed attendance analytics."; ?></p>
    </div>
    <div class="d-print-none">
        <?php if ($selected_batch && !empty($stats)): ?>
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                <i class="bi bi-printer me-2"></i> Print PDF
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-bold small text-muted text-uppercase"><i class="bi bi-layers me-1"></i> Select Batch</label>
                <select class="form-select border-0 bg-light rounded-3 py-2" name="batch_id" onchange="this.form.submit()">
                    <option value="">-- Choose Batch --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?php echo $b['batch_id']; ?>" <?php echo $selected_batch == $b['batch_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-bold small text-muted text-uppercase"><i class="bi bi-calendar3 me-1"></i> Select Semester (Optional)</label>
                <select class="form-select border-0 bg-light rounded-3 py-2" name="semester_id" onchange="this.form.submit()" <?php echo !$selected_batch ? 'disabled' : ''; ?>>
                    <option value="">-- All Semesters --</option>
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?php echo $s['semester_id']; ?>" <?php echo $selected_semester == $s['semester_id'] ? 'selected' : ''; ?>>
                            Semester <?php echo $s['semester_no']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <a href="attendance_report.php" class="btn btn-light w-100 rounded-3 py-2 fw-bold text-muted border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <?php if ($selected_batch && !empty($batch_info)): ?>
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="fw-bold mb-0">Attendance Summary: <?php echo htmlspecialchars($batch_info['batch_name']); ?> 
                <?php if ($selected_semester): ?>
                    <span class="text-primary small">(Semester <?php 
                        $key = array_search($selected_semester, array_column($semesters, 'semester_id'));
                        echo $semesters[$key]['semester_no']; 
                    ?>)</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold text-uppercase">
                    <tr>
                        <th class="ps-4">Reg No</th>
                        <th>Student Name</th>
                        <th class="text-center">Total Sessions</th>
                        <th class="text-center">Attended</th>
                        <th class="text-center pe-4">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No data available for the selected criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats as $row):
                        $percentage = $batch_grand_total > 0 ? ($row['present_sessions'] / $batch_grand_total) * 100 : 0;
                        $color = $percentage < 75 ? 'bg-danger' : ($percentage >= 90 ? 'bg-success' : 'bg-primary');
                    ?>
                        <tr>
                            <td class="ps-4 font-monospace small fw-bold text-dark"><?php echo htmlspecialchars($row['register_no']); ?></td>
                            <td class="fw-bold text-secondary"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="text-center"><?php echo $batch_grand_total; ?></td>
                            <td class="text-center"><?php echo $row['present_sessions']; ?></td>
                            <td class="text-center pe-4">
                                <span class="badge rounded-pill px-3 py-2 <?php echo $color; ?> bg-opacity-10 <?php echo str_replace('bg-', 'text-', $color); ?> border <?php echo str_replace('bg-', 'border-', $color); ?>-subtle fw-bold">
                                    <?php echo number_format($percentage, 2); ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card-body py-5 text-center text-muted">
            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-calendar-check fs-1 text-primary"></i>
            </div>
            <h5 class="fw-bold text-dark">No Batch Selected</h5>
            <p class="small mb-0">Please use the filters above to generate an attendance report.</p>
        </div>
    <?php endif; ?>
</div>

<style>
@media print {
    body { background-color: white !important; }
    .navbar, .sidebar, .btn-primary, .d-print-none, .main-header { display: none !important; }
    .main-wrapper { margin-left: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #eee !important; }
    .badge { border: 1px solid #dee2e6 !important; background: transparent !important; color: #000 !important; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>

