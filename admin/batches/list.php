<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

// Fetch departments for filter
$depts = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();
// Fetch courses with shift for filter, restricted by department if selected
$courses_query = "SELECT course_id, course_name, shift FROM Course WHERE status = 1";
$courses_params = [];
if (!empty($_GET['dept_id'])) {
    $courses_query .= " AND dept_id = ?";
    $courses_params[] = $_GET['dept_id'];
}
$courses_query .= " ORDER BY course_name ASC, shift ASC";
$stmt_courses = $conn->prepare($courses_query);
$stmt_courses->execute($courses_params);
$courses = $stmt_courses->fetchAll();
// Fetch unique admission years for filter
$years = $conn->query("SELECT DISTINCT admission_year FROM Batch ORDER BY admission_year DESC")->fetchAll();

// Handle filters
$where = [];
$params = [];

if (!empty($_GET['search'])) {
    $where[] = "b.batch_name LIKE ?";
    $params[] = "%" . $_GET['search'] . "%";
}
if (!empty($_GET['dept_id'])) {
    $where[] = "c.dept_id = ?";
    $params[] = $_GET['dept_id'];
}
if (!empty($_GET['course_id'])) {
    $where[] = "b.course_id = ?";
    $params[] = $_GET['course_id'];
}
if (!empty($_GET['year'])) {
    $where[] = "b.admission_year = ?";
    $params[] = $_GET['year'];
}

$whereSql = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

try {
    $stmt = $conn->prepare("SELECT b.*, c.course_name, c.shift, d.dept_name,
                        (SELECT sem.semester_no 
                         FROM Student s 
                         JOIN Student_Semester ss ON s.student_id = ss.student_id 
                         JOIN Semester sem ON ss.semester_id = sem.semester_id 
                         WHERE s.batch_id = b.batch_id AND ss.is_active = 1 
                         LIMIT 1) as semester_no 
                        FROM Batch b 
                        JOIN Course c ON b.course_id = c.course_id 
                        JOIN Department d ON c.dept_id = d.dept_id
                        $whereSql
                        ORDER BY b.batch_name ASC");
    $stmt->execute($params);
    $batches = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error fetching batches: " . $e->getMessage());
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Academic Batches</h2>
        <p class="text-muted mb-0 small">Overview of student groups and active semesters.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold border-2">
            <i class="bi bi-printer me-2"></i> Print List
        </button>
        <a href="add.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-plus-circle me-2"></i> Add Batch
        </a>
    </div>
</div>

<!-- Search and Filter -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4 bg-white">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Search Batch</label>
                <input type="text" name="search" class="form-control bg-light border-0 py-2 rounded-3" placeholder="Batch Name..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small text-muted text-uppercase">Department</label>
                <select name="dept_id" id="dept_id" class="form-select bg-light border-0 py-2 rounded-3">
                    <option value="">All</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['dept_id']; ?>" <?php echo (($_GET['dept_id'] ?? '') == $d['dept_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small text-muted text-uppercase">Course</label>
                <select name="course_id" id="course_id" class="form-select bg-light border-0 py-2 rounded-3">
                    <option value="">All</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo (($_GET['course_id'] ?? '') == $c['course_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_name']) . " - Shift " . $c['shift']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small text-muted text-uppercase">Year</label>
                <select name="year" class="form-select bg-light border-0 py-2 rounded-3">
                    <option value="">All</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y['admission_year']; ?>" <?php echo (($_GET['year'] ?? '') == $y['admission_year']) ? 'selected' : ''; ?>>
                            <?php echo $y['admission_year']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary fw-bold w-100 rounded-pill py-2">Filter</button>
                <a href="list.php" class="btn btn-outline-secondary fw-bold w-100 rounded-pill py-2 text-decoration-none text-center">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-container">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Batch Name</th>
                    <th class="py-3">Department & Course</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="py-3 text-center">Active Term</th>
                    <th class="pe-4 py-3 text-end">Edit</th>
                </tr>
            </thead>
                <tbody>
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 mb-3 d-block text-muted opacity-50"></i>
                                No batches found matching your criteria.
                            </td>
                        </tr>
                    <?php
else: ?>
                        <?php $sno = 1; foreach ($batches as $batch): ?>
                            <tr>
                                <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($batch['batch_name']); ?></div>
                                    <div class="small text-muted">Year: <?php echo $batch['admission_year']; ?></div>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark"><?php echo htmlspecialchars($batch['course_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($batch['dept_name']); ?> (Shift <?php echo $batch['shift']; ?>)</div>
                                </td>
                                <td class="text-center">
                                    <?php 
                                    if ($batch['status'] == 1): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">Active</span>
                                    <?php elseif ($batch['status'] == 2): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">Deactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 rounded-pill">
                                        Sem <?php echo $batch['semester_no'] ?? '-'; ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="edit.php?id=<?php echo $batch['batch_id']; ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 shadow-sm">
                                        <i class="bi bi-pencil-fill px-1"></i>
                                    </a>
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

<style>
.bg-info-subtle { background-color: rgba(13, 202, 240, 0.1); }
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1); }
.bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1); }
.btn-white { background: white; color: #333; }
.btn-white:hover { background: #f8f9fa; }
</style>

<script>
document.getElementById('dept_id').addEventListener('change', function() {
    const deptId = this.value;
    const courseSelect = document.getElementById('course_id');
    
    courseSelect.innerHTML = '<option value="">All</option>';
    
    // Construct the URL for fetching courses. 
    // If no department is selected, we might want to fetch all courses or keep it empty.
    // Given the user request, "only its courses must show", we'll fetch only if deptId is set.
    // If deptId is empty (All), we can either show all or none. 
    // Standard behavior for "All" department is to show "All" courses.
    
    let url = '../subjects/get_courses.php';
    if (deptId) {
        url += '?dept_id=' + deptId;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = `${course.course_name} - Shift ${course.shift}`;
                courseSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching courses:', error));
});
</script>

<?php require_once '../../includes/footer.php'; ?>
