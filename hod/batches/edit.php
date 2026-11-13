<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = (int)$_GET['id'];
$dept_id = $_SESSION['dept_id'];

// Security Check and Fetch Course Type
try {
    $stmt = $conn->prepare("SELECT b.*, c.course_type, c.course_name, c.shift 
                           FROM Batch b 
                           JOIN Course c ON b.course_id = c.course_id 
                           WHERE b.batch_id = ? AND c.dept_id = ?");
    $stmt->execute([$id, $dept_id]);
    $batch = $stmt->fetch();

    if (!$batch) {
        header("Location: list.php?error=Batch not found.");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['batch_name']);
    $year = (int)$_POST['admission_year'];
    $status = $_POST['status'];

    try {
        // Check for duplicates (other batches in same course with same name or year)
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Batch WHERE course_id = ? AND (batch_name = ? OR admission_year = ?) AND batch_id != ?");
        $checkStmt->execute([$batch['course_id'], $name, $year, $id]);
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Another batch with this name or admission year already exists for this course.";
        } else {
            $stmt = $conn->prepare("UPDATE Batch SET batch_name = ?, admission_year = ?, status = ? WHERE batch_id = ?");
            $stmt->execute([$name, $year, $status, $id]);
            header("Location: list.php?msg=Batch updated successfully.");
            exit();
        }
    }
    catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

require_once '../../includes/hod_header.php';
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
                    <p class="text-white text-opacity-80 small mb-0 fw-medium"><?php echo htmlspecialchars($batch['course_name']) . " (Shift " . $batch['shift'] . ")"; ?></p>
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
                        <label class="form-label fw-bold small text-uppercase text-muted">Admission Year</label>
                        <input type="number" class="form-control border-primary-subtle py-2" name="admission_year" id="admission_year" value="<?php echo $batch['admission_year']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Batch Name</label>
                        <input type="text" class="form-control border-primary-subtle bg-light py-2" name="batch_name" id="batch_name" value="<?php echo htmlspecialchars($batch['batch_name']); ?>" placeholder="e.g. 2024-2027" required readonly>
                        <div class="form-text small"><i class="bi bi-info-circle me-1"></i>Automatically recalculated if Admission Year changes.</div>
                    </div>
                    
                    <input type="hidden" id="course_type" value="<?php echo $batch['course_type']; ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Status</label>
                        <select class="form-select border-primary-subtle py-2" name="status">
                            <option value="1" <?php echo $batch['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="2" <?php echo $batch['status'] == 2 ? 'selected' : ''; ?>>Completed</option>
                            <option value="0" <?php echo $batch['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4 flex-fill py-2 shadow-sm">
                            <i class="bi bi-save me-1"></i> Update Batch
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
    const yearInput = document.getElementById('admission_year');
    const batchNameInput = document.getElementById('batch_name');
    const courseType = document.getElementById('course_type').value;

    function calculateBatchName() {
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

    yearInput.addEventListener('input', calculateBatchName);
});
</script>

<?php require_once '../../includes/footer.php'; ?>
