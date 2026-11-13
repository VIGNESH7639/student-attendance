<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];
try {
    $stmt = $conn->prepare("SELECT * FROM Batch WHERE batch_id = ?");
    $stmt->execute([$id]);
    $batch = $stmt->fetch();
    if (!$batch) {
        header("Location: list.php?error=Batch not found.");
        exit();
    }

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
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Batch WHERE course_id = ? AND batch_name = ? AND admission_year = ? AND batch_id != ?");
            $checkStmt->execute([$course_id, $name, $year, $id]);
            if ($checkStmt->fetchColumn() > 0) {
                $error = "Another batch with the same name and year already exists in this course.";
            } else {
                $stmt = $conn->prepare("UPDATE Batch SET batch_name = ?, course_id = ?, admission_year = ?, status = ? WHERE batch_id = ?");
                $stmt->execute([$name, $course_id, $year, $status, $id]);
                header("Location: list.php?msg=Batch updated successfully.");
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-pencil-square fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Edit Batch</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Update academic batch details</p>
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
                        <label class="form-label">Course</label>
                        <select class="form-select" name="course_id" id="course_id" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['course_id']; ?>" data-type="<?php echo $c['course_type']; ?>" <?php echo($c['course_id'] == $batch['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['course_name']) . " (" . htmlspecialchars($c['dept_name']) . ") - Shift " . $c['shift']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admission Year</label>
                        <input type="number" class="form-control" name="admission_year" id="admission_year" value="<?php echo $batch['admission_year']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Name</label>
                        <input type="text" class="form-control" name="batch_name" id="batch_name" value="<?php echo htmlspecialchars($batch['batch_name']); ?>" placeholder="e.g., 2024-2027" required readonly>
                        <small class="text-muted">Automatically calculated based on Course Type and Admission Year.</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="1" <?php echo $batch['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="2" <?php echo $batch['status'] == 2 ? 'selected' : ''; ?>>Completed</option>
                            <option value="0" <?php echo $batch['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4">Update Batch</button>
                        <a href="list.php" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('course_id');
    const yearInput = document.getElementById('admission_year');
    const batchNameInput = document.getElementById('batch_name');

    function calculateBatchName() {
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
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
