<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

try {
    $courses = $conn->query("SELECT c.course_id, c.course_name, c.course_type, c.shift, d.dept_name 
                            FROM Course c 
                            JOIN Department d ON c.dept_id = d.dept_id 
                            WHERE c.status = 1 
                            ORDER BY c.course_name ASC, d.dept_name ASC, c.shift ASC")->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['batch_name']);
    $course_id = (int)$_POST['course_id'];
    $year = $_POST['admission_year'];
    $status = (int)$_POST['status'];

    if (empty($name) || empty($course_id) || empty($year)) {
        $error = "All fields are required.";
    } else {
        try {
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Batch WHERE course_id = ? AND batch_name = ? AND admission_year = ?");
            $checkStmt->execute([$course_id, $name, $year]);
            if ($checkStmt->fetchColumn() > 0) {
                $error = "Batch already exists for this course and year.";
            } else {
                $stmt = $conn->prepare("INSERT INTO Batch (batch_name, course_id, admission_year, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $course_id, $year, $status]);
                header("Location: list.php?msg=Batch added successfully.");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<?php
// Fetch active departments for the filter
$depts = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();
require_once '../../includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-plus-circle fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Add New Batch</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Create a new academic batch for a course</p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Select Department</label>
                        <select class="form-select border-primary-subtle py-2" id="dept_id" required>
                            <option value="">-- Choose Department --</option>
                            <?php foreach ($depts as $d): ?>
                                <option value="<?php echo $d['dept_id']; ?>"><?php echo htmlspecialchars($d['dept_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Select Course</label>
                        <select class="form-select border-primary-subtle py-2" name="course_id" id="course_id" required disabled>
                            <option value="">-- Select Department First --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Admission Year</label>
                        <input type="number" class="form-control border-primary-subtle py-2" name="admission_year" id="admission_year" value="<?php echo date("Y"); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Batch Name</label>
                        <input type="text" class="form-control border-primary-subtle bg-light py-2" name="batch_name" id="batch_name" placeholder="e.g. 2024-2027" required readonly>
                        <div class="form-text small"><i class="bi bi-info-circle me-1"></i>Automatically calculated based on Course Type.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Status</label>
                        <select class="form-select border-primary-subtle py-2" name="status">
                            <option value="1">Active</option>
                            <option value="2">Completed</option>
                            <option value="0">Deactive</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4 flex-fill py-2 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Batch
                        </button>
                        <a href="list.php" class="btn btn-light fw-bold px-4 py-2 border">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('dept_id');
    const courseSelect = document.getElementById('course_id');
    const yearInput = document.getElementById('admission_year');
    const batchNameInput = document.getElementById('batch_name');

    deptSelect.addEventListener('change', function() {
        const deptId = this.value;
        courseSelect.innerHTML = '<option value="">-- Choose Course --</option>';
        courseSelect.disabled = true;
        batchNameInput.value = '';

        if (deptId) {
            fetch('../subjects/get_courses.php?dept_id=' + deptId)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        data.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.course_id;
                            option.setAttribute('data-type', course.course_type);
                            option.textContent = `${course.course_name} - Shift ${course.shift}`;
                            courseSelect.appendChild(option);
                        });
                        courseSelect.disabled = false;
                    } else {
                        courseSelect.innerHTML = '<option value="">-- No courses found --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching courses:', error);
                    courseSelect.innerHTML = '<option value="">-- Error loading courses --</option>';
                });
        }
    });

    function calculateBatchName() {
        if (!courseSelect.selectedIndex) return;
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        if (!selectedOption.value) {
            batchNameInput.value = '';
            return;
        }
        
        const courseType = selectedOption.getAttribute('data-type');
        const year = parseInt(yearInput.value);

        if (courseType && !isNaN(year)) {
            let endYear;
            if (courseType === 'UG') endYear = year + 3;
            else if (courseType === 'PG') endYear = year + 2;
            
            if (endYear) batchNameInput.value = year + '-' + endYear;
            else batchNameInput.value = '';
        } else {
            batchNameInput.value = '';
        }
    }

    courseSelect.addEventListener('change', calculateBatchName);
    yearInput.addEventListener('input', calculateBatchName);
});
</script>

<?php require_once '../../includes/footer.php'; ?>
