<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $dept_id = (int)$_POST['dept_id'];
    $shift = (int)$_POST['shift'];
    $type = $_POST['type'];
    $status = (int)$_POST['status'];

    if (empty($name)) {
        $error = "Course name is required.";
    } else {
        try {
            // Check for duplicate
            $check = $conn->prepare("SELECT COUNT(*) FROM Course WHERE course_name = ? AND dept_id = ? AND shift = ?");
            $check->execute([$name, $dept_id, $shift]);
            if ($check->fetchColumn() > 0) {
                $error = "This course already exists in the selected department and shift.";
            } else {
                $stmt = $conn->prepare("INSERT INTO Course (course_name, dept_id, shift, course_type, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $dept_id, $shift, $type, $status]);
                $course_id = $conn->lastInsertId();
                header("Location: assign_semester.php?id=$course_id&msg=Course added successfully. Please assign semesters.");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error adding course: " . $e->getMessage();
        }
    }
}

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
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Add Course</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Create a new course for the department</p>
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
                        <label for="dept_id" class="form-label fw-bold small text-uppercase text-muted">Department</label>
                        <select class="form-select border-primary-subtle py-2" id="dept_id" name="dept_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($depts as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold small text-uppercase text-muted">Course Name</label>
                        <input type="text" class="form-control border-primary-subtle py-2" id="name" name="name" placeholder="ex: B.Sc, M.Sc, BBA, BCA" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="shift" class="form-label fw-bold small text-uppercase text-muted">Shift</label>
                            <select class="form-select border-primary-subtle py-2" id="shift" name="shift">
                                <option value="1">Shift 1</option>
                                <option value="2">Shift 2</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label fw-bold small text-uppercase text-muted">Type</label>
                            <select class="form-select border-primary-subtle py-2" id="type" name="type">
                                <option value="UG">UG</option>
                                <option value="PG">PG</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="status" class="form-label fw-bold small text-uppercase text-muted">Status</label>
                        <select class="form-select border-primary-subtle py-2" id="status" name="status">
                            <option value="1">Active</option>
                            <option value="0">Deactive</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Save</button>
                        <a href="list.php" class="btn btn-outline-secondary px-4 fw-bold">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
