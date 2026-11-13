<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$selected_batch = isset($_GET['batch_id']) ? $_GET['batch_id'] : null;
$selected_subject = isset($_GET['subject_id']) ? $_GET['subject_id'] : null;

// Redirect if both are selected (MUST BE BEFORE ANY OUTPUT)
if ($selected_batch && $selected_subject) {
    // Find the allocation ID
    $stmt = $conn->prepare("SELECT alloc_id FROM Staff_Subject WHERE batch_id = ? AND sub_id = ? AND status = 1 LIMIT 1");
    $stmt->execute([$selected_batch, $selected_subject]);
    $alloc_id = $stmt->fetchColumn();
    
    if ($alloc_id) {
        header("Location: ../../staff/reports/internal_marks.php?id=" . $alloc_id);
        exit();
    } else {
        $error = "No active staff allocation found for this subject and batch.";
    }
}

require_once '../../includes/hod_header.php';

$dept_id = $_SESSION['dept_id'];

// Get all batches for this department
$stmt = $conn->prepare("SELECT b.batch_id, b.batch_name, c.course_name, c.shift FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE c.dept_id = ? AND b.status = 1 ORDER BY c.course_name, b.batch_name");
$stmt->execute([$dept_id]);
$batches = $stmt->fetchAll();

// Get subjects filtered by selected batch (if selected)
if ($selected_batch) {
    $stmt = $conn->prepare("SELECT ss.alloc_id, s.sub_id as subject_id, s.sub_name as subject_name, s.sub_code as subject_code 
                           FROM Staff_Subject ss
                           JOIN Subject s ON ss.sub_id = s.sub_id
                           WHERE ss.batch_id = ? AND ss.status = 1");
    $stmt->execute([$selected_batch]);
    $subjects = $stmt->fetchAll();
} else {
    $subjects = [];
}
?>

<div class="row g-4 mb-5 mt-2 justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="glass-card shadow-sm border-0">
            <div class="p-4 border-bottom border-light">
                <div class="d-flex align-items-center mb-0">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-file-earmark-diff-fill fs-4"></i>
                    </div>
                    <div>
                        <h4 class="font-heading fw-bold text-dark mb-0">Internal Master Desk</h4>
                        <p class="text-muted small mb-0">Select cohort parameters to generate reports.</p>
                    </div>
                </div>
            </div>
            <div class="p-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-circle-fill me-3 fs-4"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <form method="GET" class="row g-4">
                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase mb-3" style="letter-spacing: 1px;">Step 1: Batch Selection</label>
                        <select class="form-select border-0 bg-light rounded-3 py-3 px-4 fw-bold shadow-none transition" name="batch_id" onchange="this.form.submit()" required>
                            <option value="">-- Choose Target Batch --</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?php echo $b['batch_id']; ?>" <?php echo $selected_batch == $b['batch_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($selected_batch): ?>
                    <div class="col-12">
                        <label class="form-label fw-bold text-muted small text-uppercase mb-3" style="letter-spacing: 1px;">Step 2: Subject Identification</label>
                        <select class="form-select border-0 bg-light rounded-3 py-3 px-4 fw-bold shadow-none transition" name="subject_id" onchange="this.form.submit()" required>
                            <option value="">-- Choose Academic Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['subject_id']; ?>" <?php echo $selected_subject == $s['subject_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['subject_name']); ?> (<?php echo htmlspecialchars($s['subject_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn btn-premium btn-premium-primary w-100 py-3 shadow-sm" <?php echo !$selected_subject ? 'disabled' : ''; ?>>
                            <i class="bi bi-file-earmark-text-fill h5 mb-0"></i> 
                            <span class="ms-2">Generate Internal Summary</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
