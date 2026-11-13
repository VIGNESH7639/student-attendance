<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$types = $conn->query("SELECT * FROM Subject_Type ORDER BY type_name")->fetchAll();
// Fetch active departments
$depts = $conn->query("SELECT dept_id, dept_name FROM Department WHERE status = 1 ORDER BY dept_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_sem_id = (int)$_POST['course_sem_id'];
    $type_id = (int)$_POST['type_id'];
    $code = trim($_POST['sub_code']);
    $name = trim($_POST['sub_name']);
    $status = (int)$_POST['status'];

    try {
        // Check for duplicate subject code
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Subject WHERE sub_code = ?");
        $checkStmt->execute([$code]);
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Subject code '$code' already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO Subject (sub_code, sub_name, course_sem_id, type_id, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $course_sem_id, $type_id, $status]);
            header("Location: list.php?msg=Subject created successfully.");
            exit();
        }
    }
    catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
require_once '../../includes/header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-journal-plus fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Add Subject</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Add a new subject to the curriculum</p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">Department</label>
                            <select class="form-select border-0 shadow-sm p-3" id="dept_id" required>
                                <option value="">-- Select Dept --</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['dept_id']; ?>">
                                        <?php echo htmlspecialchars($d['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold text-muted small text-uppercase">Course</label>
                            <select class="form-select border-0 shadow-sm p-3" id="course_id" required disabled>
                                <option value="">-- Select Course --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">Semester</label>
                            <select class="form-select border-0 shadow-sm p-3" name="course_sem_id" id="course_sem_id" required disabled>
                                <option value="">-- Sem --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 text-start">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-3">Subject Code</label>
                            <input type="text" class="form-control border-0 shadow-sm p-3 px-4 rounded-pill" name="sub_code" placeholder="e.g. CS101" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-3">Subject Name</label>
                            <input type="text" class="form-control border-0 shadow-sm p-3 px-4 rounded-pill" name="sub_name" placeholder="Full name of the subject" required>
                        </div>
                    </div>

                    <div class="mb-5 text-start">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase ms-3">Subject Type</label>
                                <select class="form-select border-0 shadow-sm p-3 px-4 rounded-pill" name="type_id" required>
                                    <option value="">-- Select Type --</option>
                                    <?php foreach ($types as $t): ?>
                                        <option value="<?php echo $t['type_id']; ?>"><?php echo htmlspecialchars($t['type_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted small text-uppercase ms-3">Status</label>
                                <select class="form-select border-0 shadow-sm p-3 px-4 rounded-pill" name="status">
                                    <option value="1">Active</option>
                                    <option value="0">Deactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary fw-bold p-3 px-5 rounded-pill flex-fill">Create Subject</button>
                        <a href="list.php" class="btn btn-outline-secondary p-3 px-5 rounded-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const deptSelect = document.getElementById('dept_id');
const courseSelect = document.getElementById('course_id');
const semSelect = document.getElementById('course_sem_id');

deptSelect.addEventListener('change', function() {
    const deptId = this.value;
    
    // Reset following selects
    courseSelect.innerHTML = '<option value="">-- Select Course --</option>';
    courseSelect.disabled = true;
    semSelect.innerHTML = '<option value="">-- Sem --</option>';
    semSelect.disabled = true;

    if (deptId) {
        fetch('get_courses.php?dept_id=' + deptId)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.course_id;
                        option.textContent = `${course.course_name} (Shift ${course.shift})`;
                        courseSelect.appendChild(option);
                    });
                    courseSelect.disabled = false;
                }
            })
            .catch(error => console.error('Error fetching courses:', error));
    }
});

courseSelect.addEventListener('change', function() {
    const courseId = this.value;
    
    // Reset following select
    semSelect.innerHTML = '<option value="">-- Sem --</option>';
    semSelect.disabled = true;

    if (courseId) {
        fetch('get_semesters.php?course_id=' + courseId)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(sem => {
                        const option = document.createElement('option');
                        option.value = sem.course_sem_id;
                        option.textContent = 'Semester ' + sem.semester_no;
                        semSelect.appendChild(option);
                    });
                    semSelect.disabled = false;
                }
            })
            .catch(error => console.error('Error fetching semesters:', error));
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
