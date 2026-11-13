<?php
require_once '../../includes/auth.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: ../../login.php"); exit; }

require_once '../../config/database.php';

$role = $_SESSION['role'] ?? '';
$is_admin = ($role === 'Admin');
$is_hod = ($role === 'HOD');
$is_staff = ($role === 'Staff');
$user_id = $_SESSION['user_id'];

$is_tutor = false;
$tutor_batches = [];

if ($is_staff) {
    $stmt = $conn->prepare("SELECT batch_id FROM Tutor WHERE staff_id = (SELECT staff_id FROM Staff WHERE user_id = ?) AND status = 1");
    $stmt->execute([$user_id]);
    $tutor_batches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($tutor_batches)) {
        $is_tutor = true;
    }
}

if (!$is_admin && !$is_hod && !$is_tutor) {
    die("Unauthorized Access. You must be an Admin, HOD, or Assigned Batch Tutor to view this page.");
}

$hod_dept_id = $_SESSION['dept_id'] ?? null;

if ($is_hod) {
    require_once '../../includes/hod_header.php';
} else {
    require_once '../../includes/header.php';
}

$message = "";
$error = "";

// Handle Promotion Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_batch_id'])) {
    $batch_id = (int)$_POST['promote_batch_id'];
    
    // Security check for Tutor
    if ($is_tutor && !$is_admin && !$is_hod) {
        if (!in_array($batch_id, $tutor_batches)) {
            die("Unauthorized to promote this batch.");
        }
    }
    
    try {
        $conn->beginTransaction();

        // 1. Find the current active semester and course's max semester for this batch
        $stmt = $conn->prepare("SELECT 
                                    sem.semester_no, 
                                    ss.academic_year, 
                                    sem.semester_id,
                                    b.course_id,
                                    (SELECT MAX(s2.semester_no) 
                                     FROM Course_Semester cs 
                                     JOIN Semester s2 ON cs.semester_id = s2.semester_id 
                                     WHERE cs.course_id = b.course_id) as max_sem_no
                                FROM Student_Semester ss 
                                JOIN Student s ON ss.student_id = s.student_id 
                                JOIN Semester sem ON ss.semester_id = sem.semester_id 
                                JOIN Batch b ON s.batch_id = b.batch_id
                                WHERE s.batch_id = ? AND ss.is_active = 1 
                                LIMIT 1");
        $stmt->execute([$batch_id]);
        $current = $stmt->fetch();

        if (!$current) {
            throw new Exception("No active students found in this batch to promote.");
        }

        $current_no = (int)$current['semester_no'];
        $current_year = (int)$current['academic_year'];
        $max_sem_no = (int)$current['max_sem_no'];

        if ($current_no >= $max_sem_no) {
            throw new Exception("Batch is already in the final semester (Semester $max_sem_no).");
        }

        $next_no = $current_no + 1;
        $next_year = ($current_no % 2 === 0) ? $current_year + 1 : $current_year;

        // Find next semester_id
        $stmt = $conn->prepare("SELECT semester_id FROM Semester WHERE semester_no = ?");
        $stmt->execute([$next_no]);
        $next_sem_id = $stmt->fetchColumn();

        if (!$next_sem_id) {
            throw new Exception("Next semester (Sem $next_no) not found in configuration.");
        }

        // 2. Deactivate current semesters for all students in this batch
        $stmt = $conn->prepare("UPDATE Student_Semester SET is_active = 0 
                                WHERE is_active = 1 AND student_id IN (
                                    SELECT student_id FROM Student WHERE batch_id = ?
                                )");
        $stmt->execute([$batch_id]);

        // 3. Insert new active semesters
        $stmt = $conn->prepare("INSERT INTO Student_Semester (student_id, semester_id, academic_year, is_active)
                                SELECT student_id, ?, ?, 1 
                                FROM Student 
                                WHERE batch_id = ?");
        $stmt->execute([$next_sem_id, $next_year, $batch_id]);

        $conn->commit();
        $message = "Batch promoted successfully to Semester $next_no ($next_year).";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Promotion Failed: " . $e->getMessage();
    }
}

// Fetch batches with their current active semester
$batch_id_param = $_GET['batch_id'] ?? null;
$search_batch = trim($_GET['search_batch'] ?? '');

$query = "SELECT b.*, c.course_name, d.dept_name,
            (SELECT sem.semester_no 
             FROM Student s 
             JOIN Student_Semester ss ON s.student_id = ss.student_id 
             JOIN Semester sem ON ss.semester_id = sem.semester_id 
             WHERE s.batch_id = b.batch_id AND ss.is_active = 1 
             LIMIT 1) as current_sem_no,
            (SELECT MAX(s2.semester_no) 
             FROM Course_Semester cs 
             JOIN Semester s2 ON cs.semester_id = s2.semester_id 
             WHERE cs.course_id = b.course_id) as max_sem_no,
            (SELECT COUNT(*) FROM Student WHERE batch_id = b.batch_id) as student_count
          FROM Batch b
          JOIN Course c ON b.course_id = c.course_id
          JOIN Department d ON c.dept_id = d.dept_id
          WHERE b.status = 1";

$params = [];
if ($is_hod) {
    $query .= " AND c.dept_id = ?";
    $params[] = $hod_dept_id;
} else if ($is_tutor && !$is_admin && !$is_hod) {
    if (empty($tutor_batches)) {
        $query .= " AND 1=0";
    } else {
        $in_clause = implode(',', array_fill(0, count($tutor_batches), '?'));
        $query .= " AND b.batch_id IN ($in_clause)";
        $params = array_merge($params, $tutor_batches);
    }
}

if ($batch_id_param) {
    $query .= " AND b.batch_id = ?";
    $params[] = $batch_id_param;
}

if (!empty($search_batch)) {
    $query .= " AND (b.batch_name LIKE ? OR c.course_name LIKE ? OR b.admission_year LIKE ?)";
    $like_term = '%' . $search_batch . '%';
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$batches = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Semester Promotion</h2>
        <p class="text-muted mb-0 small">Bulk promote student batches to the next academic semester.</p>
    </div>
    <a href="<?php echo ($is_tutor && !$is_admin && !$is_hod) ? '../../mentor/students.php' : 'list.php' . ($batch_id_param ? '?batch_id=' . $batch_id_param : ''); ?>" class="btn btn-outline-primary rounded-pill px-4">
        <i class="bi bi-people me-2"></i> Student List
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-9">
                <label class="form-label fw-bold small text-muted text-uppercase"><i class="bi bi-search me-1"></i> Search Batch</label>
                <input type="text" name="search_batch" class="form-control border-0 bg-light rounded-3 py-2" placeholder="Search by batch name, course, or admission year..." value="<?php echo htmlspecialchars($search_batch); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold shadow-sm">
                    Filter Batches
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4">Batch Details</th>
                    <th>Course & Dept</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Students</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-arrow-up-circle opacity-25 d-block mb-3" style="font-size: 3rem;"></i>
                            No batches available for promotion.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($batches as $b): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($b['batch_name']); ?></div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 small">
                                        <?php echo htmlspecialchars($b['admission_year']); ?>
                                    </span>
                                    <span class="small text-muted font-monospace">ID: <?php echo htmlspecialchars($b['batch_id']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-secondary"><?php echo htmlspecialchars($b['course_name']); ?></div>
                                <div class="text-muted small fw-medium mt-1"><i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($b['dept_name']); ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $b['current_sem_no'] ? 'bg-primary bg-opacity-10 text-primary border border-primary-subtle' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle'; ?> px-3 py-2 rounded-pill shadow-xs">
                                    <?php echo $b['current_sem_no'] ? 'Semester ' . $b['current_sem_no'] : 'Not Started'; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle fw-bold text-dark shadow-sm" style="width: 40px; height: 40px;">
                                    <?php echo htmlspecialchars($b['student_count']); ?>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($b['student_count'] > 0 && $b['current_sem_no'] < $b['max_sem_no']): ?>
                                    <form method="POST" class="d-inline-block" onsubmit="return confirm('Promote batch <?php echo addslashes($b['batch_name']); ?> to Semester <?php echo $b['current_sem_no'] + 1; ?>?')">
                                        <input type="hidden" name="promote_batch_id" value="<?php echo $b['batch_id']; ?>">
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm hover-lift">
                                            <i class="bi bi-arrow-up-circle me-1"></i> Promote to Sem <?php echo $b['current_sem_no'] + 1; ?>
                                        </button>
                                    </form>
                                <?php elseif ($b['current_sem_no'] >= $b['max_sem_no'] && $b['current_sem_no'] > 0): ?>
                                    <button class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-bold" disabled>
                                        <i class="bi bi-check-all me-1"></i> Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-bold" disabled title="No students found in this batch">
                                        <i class="bi bi-slash-circle me-1"></i> Empty Batch
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.hover-lift {
    transition: transform 0.2s, box-shadow 0.2s;
}
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}
</style>

<?php require_once '../../includes/footer.php'; ?>
