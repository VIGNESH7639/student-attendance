<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dept_filter = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : 0;

try {
    // Build query
    $query = "SELECT c.*, d.dept_name, 
              (SELECT COUNT(*) FROM Course_Semester cs WHERE cs.course_id = c.course_id) as sem_count 
              FROM Course c 
              JOIN Department d ON c.dept_id = d.dept_id WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND c.course_name LIKE ?";
        $params[] = "%$search%";
    }

    if ($dept_filter > 0) {
        $query .= " AND c.dept_id = ?";
        $params[] = $dept_filter;
    }

    $query .= " ORDER BY d.dept_name, c.course_name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();

    // Fetch departments for filter
    $depts = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();
}
catch (PDOException $e) {
    die("Error fetching courses: " . $e->getMessage());
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Academic Courses</h2>
        <p class="text-muted mb-0 small">Manage degrees, shifts, and semester structures.</p>
    </div>
    <a href="add.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
        <i class="bi bi-plus-circle me-2"></i> Add Course
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Search and Filter -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-3 bg-white">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0 py-2 ps-3">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control bg-light border-0 py-2" placeholder="Search course name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="dept_id" class="form-select bg-light border-0 py-2">
                    <option value="">All Departments</option>
                    <?php foreach ($depts as $dept): ?>
                        <option value="<?php echo $dept['dept_id']; ?>" <?php echo $dept_filter == $dept['dept_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Filter</button>
                <a href="list.php" class="btn btn-outline-secondary w-100 rounded-pill fw-bold">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-container">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Course Name</th>
                    <th class="py-3">Department</th>
                    <th class="py-3 text-center">Shift/Type</th>
                    <th class="py-3 text-center">Structure</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end">Actions</th>
                </tr>
            </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No courses found.</td>
                        </tr>
                    <?php else: ?>
                        <?php $sno = 1; foreach ($courses as $course): ?>
                            <tr>
                                <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                </td>
                                <td><span class="small"><?php echo htmlspecialchars($course['dept_name']); ?></span></td>
                                <td class="text-center">
                                    <div class="small fw-bold"><?php echo $course['shift'] == 1 ? 'Shift 1' : 'Shift 2'; ?></div>
                                    <div class="small text-muted"><?php echo $course['course_type']; ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">
                                        <?php echo $course['sem_count']; ?> Semesters
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $course['status'] == 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?> rounded-pill px-3">
                                        <?php echo $course['status'] == 1 ? 'Active' : 'Deactive'; ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="assign_semester.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline-info border-0 rounded-circle p-2 shadow-sm" title="Assign Semesters">
                                            <i class="bi bi-list-columns px-1"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 shadow-sm" title="Edit">
                                            <i class="bi bi-pencil-fill px-1"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
