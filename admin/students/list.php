<?php
require_once '../../includes/auth.php';
requireAdminOrHOD();
require_once '../../config/database.php';

$is_hod = isHOD();
$hod_dept_id = $_SESSION['dept_id'] ?? null;

if ($is_hod) {
    require_once '../../includes/hod_header.php';
    // Fetch Batches for this department with course info and student count
    $stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift, 
                            (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id) AS student_count 
                            FROM Batch b JOIN Course c ON b.course_id = c.course_id 
                            WHERE c.dept_id = ? AND b.status = 1 ORDER BY b.batch_name DESC");
    $stmt->execute([$hod_dept_id]);
    $batches = $stmt->fetchAll();
} else {
    require_once '../../includes/header.php';
    // Fetch all active Departments for Admin filter
    $departments = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();
    
    $dept_filter = $_GET['dept_id'] ?? null;
    
    // Fetch active Batches for Admin with course info, filtered by department if selected and student count
    $batch_query = "SELECT b.batch_id, b.batch_name, c.course_name, c.shift, 
                    (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id) AS student_count 
                    FROM Batch b 
                    JOIN Course c ON b.course_id = c.course_id 
                    WHERE b.status = 1";
    $batch_params = [];
    if ($dept_filter) {
        $batch_query .= " AND c.dept_id = ?";
        $batch_params[] = $dept_filter;
    }
    $batch_query .= " ORDER BY b.batch_name DESC";
    $stmt_batches = $conn->prepare($batch_query);
    $stmt_batches->execute($batch_params);
    $batches = $stmt_batches->fetchAll();
}

$search = $_GET['search'] ?? '';

try {
    $batch_id = $_GET['batch_id'] ?? null;
    $batch_name = "";

    if ($batch_id) {
        $stmt = $conn->prepare("SELECT batch_name FROM Batch WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        $batch_name = $stmt->fetchColumn();
    }

    $query = "SELECT s.*, b.batch_name, d.dept_name, u.username, 
              (SELECT sem.semester_no 
               FROM Student_Semester ss 
               JOIN Semester sem ON ss.semester_id = sem.semester_id 
               WHERE ss.student_id = s.student_id AND ss.is_active = 1 
               LIMIT 1) as semester_no
              FROM Student s 
              JOIN Batch b ON s.batch_id = b.batch_id 
              JOIN Course c ON b.course_id = c.course_id 
              JOIN Department d ON c.dept_id = d.dept_id 
              JOIN Users u ON s.user_id = u.user_id";

    $params = [];
    $where_clauses = [];

    if ($is_hod) {
        $where_clauses[] = " c.dept_id = ? ";
        $params[] = $hod_dept_id;
    }

    if ($batch_id) {
        $where_clauses[] = " s.batch_id = ? ";
        $params[] = $batch_id;
    }

    if (!$is_hod && !empty($dept_filter)) {
        $where_clauses[] = " c.dept_id = ? ";
        $params[] = $dept_filter;
    }

    if (!empty($search)) {
        $where_clauses[] = " (s.student_name LIKE ? OR s.register_no LIKE ?) ";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $query .= " ORDER BY s.register_no ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-1 text-primary"><?php echo $batch_id ? htmlspecialchars($batch_name) . " - Students" : "Overall Students List"; ?></h2>
        <p class="text-muted mb-0 small"><?php echo $is_hod ? 'Managing students for your department' : 'Viewing all students in the system'; ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($is_hod): ?>
            <a href="promote.php" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold border-2">
                <i class="bi bi-arrow-up-circle me-2"></i> Promote Students
            </a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold border-2">
            <i class="bi bi-printer me-2"></i> Print List
        </button>
        <a href="add.php<?php echo $batch_id ? '?batch_id=' . $batch_id : ''; ?>" class="btn btn-primary rounded-pill px-4 shadow-lg fw-bold border-0">
            <i class="bi bi-person-plus-fill me-2"></i> Add Student
        </a>
    </div>
</div>

<!-- Search and Filter Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3">
            <?php if (!$batch_id): ?>
                <?php if (!$is_hod): ?>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Filter by Department</label>
                    <select name="dept_id" class="form-select border-0 bg-light rounded-3" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?php echo $d['dept_id']; ?>" <?php echo (isset($dept_filter) && $dept_filter == $d['dept_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['dept_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Filter by Batch</label>
                    <select name="batch_id" class="form-select border-0 bg-light rounded-3">
                        <option value="">All Batches</option>
                        <?php foreach ($batches as $b): ?>
                            <option value="<?php echo $b['batch_id']; ?>" <?php echo $batch_id == $b['batch_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['batch_name'] . " (" . $b['course_name'] . " - Shift " . $b['shift'] . ") - [" . $b['student_count'] . " Students]"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <!-- Hidden inputs to maintain current filters when searching within a batch -->
                <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($batch_id); ?>">
                <?php if ($dept_filter ?? null): ?>
                    <input type="hidden" name="dept_id" value="<?php echo htmlspecialchars($dept_filter); ?>">
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="<?php echo $batch_id ? 'col-md-6' : 'col-md-3'; ?>">
                <label class="form-label small fw-bold text-muted">Search Student</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0 px-3"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-0 bg-light rounded-end-3 py-2" 
                           placeholder="Search student..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="<?php echo $batch_id ? 'col-md-6' : 'col-md-3'; ?> d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary rounded-3 w-100 fw-bold shadow-sm">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <?php if ($batch_id || $search || ($dept_filter ?? null)): ?>
                    <a href="list.php<?php echo $batch_id ? '?batch_id='.$batch_id : ''; ?>" class="btn btn-light rounded-3 w-100 fw-bold border">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-container">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Photo</th>
                    <th class="py-3">Student Details</th>
                    <?php if (!$batch_id): ?>
                        <th class="py-3">Batch</th>
                    <?php endif; ?>
                    <th class="py-3">Dept</th>
                    <th class="py-3 text-center">Sem</th>
                    <th class="py-3">Contact info</th>
                    <th class="py-3">Address</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end">Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php $sno = 1; foreach ($students as $s): ?>
                    <tr>
                        <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                        <td>
                            <?php if ($s['photo'] && file_exists('../../' . $s['photo'])): ?>
                                <img src="../../<?php echo $s['photo']; ?>" class="rounded-pill shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #fff;">
                            <?php else: ?>
                                <div class="rounded-pill bg-light d-flex align-items-center justify-content-center text-muted shadow-sm" style="width: 45px; height: 45px; border: 2px solid #fff;">
                                    <i class="bi bi-person small"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['student_name']); ?></div>
                            <div class="small font-monospace text-primary"><?php echo htmlspecialchars($s['register_no']); ?></div>
                        </td>
                        <?php if (!$batch_id): ?>
                            <td class="small"><?php echo htmlspecialchars($s['batch_name']); ?></td>
                        <?php endif; ?>
                        <td><span class="small"><?php echo htmlspecialchars($s['dept_name']); ?></span></td>
                        <td class="text-center">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill">
                                Sem <?php echo $s['semester_no'] ?? '-'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="small fw-bold"><i class="bi bi-telephone me-1 text-muted"></i> <?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></div>
                            <div class="small text-muted"><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($s['email'] ?? '-'); ?></div>
                        </td>
                        <td>
                            <div class="small text-muted" style="max-width: 15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($s['address'] ?? ''); ?>">
                                <?php echo htmlspecialchars($s['address'] ?? '-'); ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($s['status'] == 1): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Deactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <a href="edit.php?id=<?php echo $s['student_id']; ?><?php echo $batch_id ? '&batch_id=' . $batch_id : ''; ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 shadow-sm" title="Edit Profile">
                                <i class="bi bi-pencil-fill px-1"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="<?php echo $batch_id ? 9 : 10; ?>" class="text-center py-5 text-muted bg-light bg-opacity-50">
                            <i class="bi bi-people opacity-25 d-block mb-3" style="font-size: 3rem;"></i>
                            <p class="mb-0">No students found for this selection.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
