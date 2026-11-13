<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$dept_filter = $_GET['dept_id'] ?? null;
$course_filter = $_GET['course_id'] ?? null;
$shift_filter = $_GET['shift'] ?? null;

$departments = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();

// Fetch courses for the selected department (to prepopulate the dropdown after filtered page load)
$courses = [];
if ($dept_filter) {
    $stmt_c = $conn->prepare("SELECT course_id, course_name, shift FROM Course WHERE dept_id = ? AND status = 1 ORDER BY course_name ASC, shift ASC");
    $stmt_c->execute([$dept_filter]);
    $courses = $stmt_c->fetchAll();
}

try {
    // Optimized query to fetch batches with course, shift, and student count
    $query = "SELECT 
                d.dept_name,
                c.course_name,
                c.shift,
                b.batch_id,
                b.batch_name,
                (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id AND s.status = 1) as student_count
              FROM Batch b
              JOIN Course c ON b.course_id = c.course_id
              JOIN Department d ON c.dept_id = d.dept_id
              WHERE b.status = 1";
              
    $params = [];
    if ($dept_filter) {
        $query .= " AND d.dept_id = ?";
        $params[] = $dept_filter;
    }
    if ($course_filter) {
        $query .= " AND c.course_id = ?";
        $params[] = $course_filter;
    }
    
    $query .= " ORDER BY d.dept_name ASC, c.course_name ASC, c.shift ASC, b.batch_name DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="department_batch_summary_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Department', 'Course', 'Shift', 'Batch Name', 'Student Count']);
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['dept_name'], 
            $row['course_name'], 
            'Shift ' . $row['shift'], 
            $row['batch_name'], 
            $row['student_count']
        ]);
    }
    fclose($output);
    exit();
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Department Batch Summary</h2>
        <p class="text-muted mb-0 small">Overview of all batches with course details, shifts, and current student strength.</p>
    </div>
    <div class="d-flex gap-2 d-print-none">
        <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
            <i class="bi bi-printer me-2"></i> Print Table
        </button>
        <a href="?export=csv<?php echo $dept_filter ? '&dept_id='.$dept_filter : ''; ?><?php echo $course_filter ? '&course_id='.$course_filter : ''; ?><?php echo $shift_filter ? '&shift='.$shift_filter : ''; ?>" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-file-earmark-excel me-2"></i> Export Data
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
    <div class="card-body p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Department</label>
                <select name="dept_id" id="dept_id" class="form-select border-0 bg-light rounded-3 py-2">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?php echo $d['dept_id']; ?>" <?php echo $dept_filter == $d['dept_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['dept_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Course</label>
                <select name="course_id" id="course_id" class="form-select border-0 bg-light rounded-3 py-2">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo $course_filter == $c['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_name']) . " - Shift " . $c['shift']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Filter</button>
            </div>
            <?php if ($dept_filter || $course_filter): ?>
            <div class="col-md-2">
                <a href="department_batch_report.php" class="btn btn-light border w-100 rounded-3 py-2 fw-bold">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4">S.No</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th class="text-center">Shift</th>
                    <th>Batch Name</th>
                    <th class="text-center">Students</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sno = 1;
                foreach ($rows as $row): 
                ?>
                    <tr>
                        <td class="ps-4 text-muted small"><?php echo $sno++; ?></td>
                        <td><span class="fw-bold"><?php echo htmlspecialchars($row['dept_name']); ?></span></td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['course_name']); ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge <?php echo $row['shift'] == 1 ? 'bg-info' : 'bg-warning'; ?> bg-opacity-10 <?php echo $row['shift'] == 1 ? 'text-info' : 'text-warning'; ?> border <?php echo $row['shift'] == 1 ? 'border-info-subtle' : 'border-warning-subtle'; ?> rounded-pill px-3">
                                Shift <?php echo $row['shift']; ?>
                            </span>
                        </td>
                        <td class="font-monospace small"><?php echo htmlspecialchars($row['batch_name']); ?></td>
                        <td class="text-center">
                            <span class="fw-bold fs-5 <?php echo $row['student_count'] > 0 ? 'text-primary' : 'text-muted'; ?>">
                                <?php echo $row['student_count']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No batches found for the selected criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('dept_id').addEventListener('change', function() {
    const deptId = this.value;
    const courseSelect = document.getElementById('course_id');
    
    courseSelect.innerHTML = '<option value="">All Courses</option>';
    
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
