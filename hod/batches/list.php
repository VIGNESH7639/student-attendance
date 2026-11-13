<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

// Fetch courses for filter (specific to HOD's dept)
$courses = $conn->prepare("SELECT course_id, course_name FROM Course WHERE dept_id = ? AND status = 1 ORDER BY course_name ASC");
$courses->execute([$dept_id]);
$courses = $courses->fetchAll();

// Fetch unique admission years for filter
$years = $conn->prepare("SELECT DISTINCT admission_year FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE c.dept_id = ? ORDER BY admission_year DESC");
$years->execute([$dept_id]);
$years = $years->fetchAll();

// Handle filters
$where = ["c.dept_id = ?"];
$params = [$dept_id];

if (!empty($_GET['search'])) {
    $where[] = "b.batch_name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}
if (!empty($_GET['course_id'])) {
    $where[] = "b.course_id = ?";
    $params[] = $_GET['course_id'];
}
if (!empty($_GET['year'])) {
    $where[] = "b.admission_year = ?";
    $params[] = $_GET['year'];
}

$whereSql = " WHERE " . implode(" AND ", $where);

try {
    $stmt = $conn->prepare("SELECT b.*, c.course_name, c.shift,
                        (SELECT sem.semester_no 
                         FROM Student s 
                         JOIN Student_Semester ss ON s.student_id = ss.student_id 
                         JOIN Semester sem ON ss.semester_id = sem.semester_id 
                         WHERE s.batch_id = b.batch_id AND ss.is_active = 1 
                         LIMIT 1) as semester_no 
                        FROM Batch b 
                        JOIN Course c ON b.course_id = c.course_id 
                        $whereSql
                        ORDER BY b.admission_year DESC, b.batch_name ASC");
    $stmt->execute($params);
    $batches = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error fetching batches: " . $e->getMessage());
}

require_once '../../includes/hod_header.php';
?>

<div class="row g-4 mb-4 mt-2">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <h2 class="font-heading fw-bold mb-0 text-dark">Academic Batches</h2>
                <p class="text-muted mb-0 small">Manage student groups and track active terms.</p>
            </div>
            <a href="add.php" class="btn btn-premium btn-premium-primary">
                <i class="bi bi-plus-circle"></i> Add New Batch
            </a>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="glass-card mb-4 overflow-hidden">
    <div class="p-4">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 1px;">Search Batch</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0 py-2" placeholder="Batch Name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 1px;">Course</label>
                <select name="course_id" class="form-select bg-light border-0 py-2">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo (($_GET['course_id'] ?? '') == $c['course_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 1px;">Admission Year</label>
                <select name="year" class="form-select bg-light border-0 py-2">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y['admission_year']; ?>" <?php echo (($_GET['year'] ?? '') == $y['admission_year']) ? 'selected' : ''; ?>>
                            <?php echo $y['admission_year']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-premium btn-premium-primary w-100">Apply Filters</button>
                <a href="list.php" class="btn btn-light border w-100 py-2 d-flex align-items-center justify-content-center" style="border-radius: 50rem;"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="table-responsive-premium">
    <table class="table table-premium align-middle mb-0">
        <thead>
            <tr>
                <th class="ps-4 text-center">S.No</th>
                <th>Batch Name</th>
                <th>Course & Shift</th>
                <th class="text-center">Status</th>
                <th class="text-center">Active Term</th>
                <th class="pe-4 text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($batches)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-4 mb-3 d-block opacity-25"></i>
                        No batches found matching your criteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($batches as $batch): ?>
                    <tr>
                        <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($batch['batch_name']); ?></div>
                            <div class="small text-muted">Year: <?php echo $batch['admission_year']; ?></div>
                        </td>
                        <td>
                            <div class="small fw-bold text-dark"><?php echo htmlspecialchars($batch['course_name']); ?></div>
                            <div class="small text-muted">Shift <?php echo $batch['shift']; ?></div>
                        </td>
                        <td class="text-center">
                            <?php 
                            if ($batch['status'] == 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 border-0">ACTIVE</span>
                            <?php elseif ($batch['status'] == 2): ?>
                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2 border-0">COMPLETED</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-2 border-0">DEACTIVE</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill border-0 fw-bold">
                                SEM <?php echo $batch['semester_no'] ?? '-'; ?>
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            <a href="edit.php?id=<?php echo $batch['batch_id']; ?>" class="btn btn-sm btn-light border rounded-circle p-2 shadow-sm" title="Edit Batch">
                                <i class="bi bi-pencil-fill text-primary"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.bg-info-subtle { background-color: rgba(13, 202, 240, 0.1); }
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1); }
.bg-secondary-subtle { background-color: rgba(108, 117, 125, 0.1); }
</style>

<?php require_once '../../includes/footer.php'; ?>
