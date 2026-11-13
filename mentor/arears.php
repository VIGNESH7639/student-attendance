<?php
require_once '../includes/auth.php';
requireRole('Staff');
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

try {
    // 1. Get staff info
    $stmt = $conn->prepare("SELECT staff_id FROM Staff WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $staff = $stmt->fetch();
    $staff_id = $staff['staff_id'];

    // 2. Get assigned tutor role and batch info
    $stmt = $conn->prepare("SELECT t.batch_id, b.batch_name, c.course_name 
                           FROM Tutor t 
                           JOIN Batch b ON t.batch_id = b.batch_id 
                           JOIN Course c ON b.course_id = c.course_id
                           WHERE t.staff_id = ? AND t.status = 1");
    $stmt->execute([$staff_id]);
    $tutor_role = $stmt->fetch();

    if (!$tutor_role) {
        header("Location: ../staff/dashboard.php");
        exit();
    }

    $batch_id = $tutor_role['batch_id'];

    // 3. Get unique subjects that have arrears in this batch (for the filter dropdown)
    $stmt = $conn->prepare("SELECT DISTINCT sub.sub_id, sub.sub_name, sub.sub_code 
                            FROM result r
                            JOIN student s ON r.student_id = s.student_id
                            JOIN subject sub ON r.sub_id = sub.sub_id
                            WHERE s.batch_id = ? AND r.result_status = 0");
    $stmt->execute([$batch_id]);
    $arrear_subjects = $stmt->fetchAll();

    $selected_sub = isset($_GET['sub_id']) ? (int)$_GET['sub_id'] : null;

    // 4. Fetch Arrear Students for this batch (optionally filtered by subject)
    // We join with Marks to get the latest attempt number for the specific failed subjects
    $query = "SELECT s.student_id, s.student_name, s.register_no, 
                     sub.sub_name, sub.sub_code, sub.sub_id, sem.semester_no, r.total_marks,
                     (SELECT MAX(m.attempt_no) FROM marks m WHERE m.student_id = s.student_id AND m.sub_id = sub.sub_id) as latest_attempt
              FROM student s
              JOIN result r ON s.student_id = r.student_id
              LEFT JOIN subject sub ON r.sub_id = sub.sub_id
              LEFT JOIN course_semester cs ON sub.course_sem_id = cs.course_sem_id
              LEFT JOIN semester sem ON cs.semester_id = sem.semester_id
              WHERE s.batch_id = ? AND r.result_status = 0 AND s.status = 1";
    
    if ($selected_sub) {
        $query .= " AND sub.sub_id = ?";
    }
    
    $query .= " ORDER BY s.register_no ASC, sem.semester_no ASC, sub.sub_code ASC";
    
    $stmt = $conn->prepare($query);
    if ($selected_sub) {
        $stmt->execute([$batch_id, $selected_sub]);
    } else {
        $stmt->execute([$batch_id]);
    }
    $arrear_list = $stmt->fetchAll();

    // Check total active students in batch for header stats
    $stmt = $conn->prepare("SELECT COUNT(*) FROM student WHERE batch_id = ? AND status = 1");
    $stmt->execute([$batch_id]);
    $total_batch_students = $stmt->fetchColumn();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../includes/mentor_header.php';
?>

<div class="row g-4 mb-5 mt-2">
    <div class="col-12">
        <div class="glass-card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    </div>
                    <div>
                        <h2 class="font-heading fw-bold text-dark mb-0">Arrear Management</h2>
                        <p class="text-muted mb-0">Tracking failed subjects for <?php echo htmlspecialchars($tutor_role['batch_name']); ?> (<?php echo htmlspecialchars($tutor_role['course_name']); ?>) &mdash; <strong><?php echo $total_batch_students; ?></strong> Active Students</p>
                    </div>
                </div>
                <div class="text-end d-flex align-items-center gap-3">
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="small fw-bold text-muted text-uppercase mb-0 me-1" style="letter-spacing: 0.5px;">Filter:</label>
                        <select name="sub_id" class="form-select form-select-sm border shadow-sm rounded-pill px-3" onchange="this.form.submit()" style="min-width: 200px;">
                            <option value="">All Subjects (<?php echo count($arrear_subjects); ?>)</option>
                            <?php foreach ($arrear_subjects as $as): ?>
                                <option value="<?php echo $as['sub_id']; ?>" <?php echo $selected_sub == $as['sub_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($as['sub_name']); ?> (<?php echo $as['sub_code']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($selected_sub): ?>
                            <a href="arrears.php" class="btn btn-sm btn-light border rounded-pill px-3">
                                <i class="bi bi-x-circle me-1"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                    <span class="badge bg-danger rounded-pill px-3 py-2">
                        Records: <?php echo count($arrear_list); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="glass-card overflow-hidden">
            <div class="table-responsive-premium">
                <table class="table table-premium align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Student Details</th>
                            <th class="text-center">Semester</th>
                            <th>Subject (Click to Filter)</th>
                            <th class="text-center">Attempt</th>
                            <th class="text-center">Last Score</th>
                            <th class="pe-4 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($arrear_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-patch-check-fill text-success opacity-25 mb-3" style="font-size: 4rem;"></i>
                                        <h5 class="fw-bold text-dark">No Arrears Found!</h5>
                                        <p class="text-muted">
                                            <?php echo $selected_sub ? 'No students have arrears in this specific subject.' : 'All students in this batch have cleared their subjects.'; ?>
                                        </p>
                                        <?php if ($selected_sub): ?>
                                            <a href="arrears.php" class="btn btn-sm btn-primary rounded-pill px-4 mt-2">View All Subjects</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $current_student = '';
                            foreach ($arrear_list as $row): 
                                $is_new_student = ($current_student !== $row['register_no']);
                                if ($is_new_student) {
                                    $current_student = $row['register_no'];
                                }
                            ?>
                                <tr class="<?php echo $is_new_student ? 'border-top' : ''; ?>">
                                    <td class="ps-4">
                                        <?php if ($is_new_student): ?>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($row['register_no']); ?></div>
                                        <?php else: ?>
                                            <div class="text-muted small ps-3">"</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1 fw-bold">
                                            SEM <?php echo $row['semester_no']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?sub_id=<?php echo $row['sub_id']; ?>" class="text-decoration-none subject-filter-link">
                                            <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($row['sub_name']); ?></div>
                                            <div class="small text-muted font-monospace"><?php echo htmlspecialchars($row['sub_code']); ?></div>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['latest_attempt'] > 1): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-1 shadow-sm fw-bold">
                                                <i class="bi bi-clock-history me-1"></i> ATTEMPT <?php echo $row['latest_attempt']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 fw-bold">
                                                ATTEMPT 1
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-danger">
                                        <?php echo number_format($row['total_marks'], 2); ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2 fw-bold">
                                            <i class="bi bi-x-circle-fill me-1"></i> ARREAR
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
