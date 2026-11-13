<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$depts = $conn->query("SELECT * FROM department WHERE status='Active' ORDER BY department_name")->fetchAll();
$sems = $conn->query("SELECT * FROM semester ORDER BY semester_no")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'];
    $batch_id = $_POST['batch_id'];
    $sem_id = $_POST['semester_id'];
    $year = trim($_POST['academic_year']);

    try {
        $stmt = $conn->prepare("INSERT INTO subject_batch_assignment (subject_id, batch_id, semester_id, academic_year) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subject_id, $batch_id, $sem_id, $year]);
        header("Location: list.php?tab=assignments&msg=Subject assigned to batch successfully.");
        exit();
    }
    catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Error: This subject is already assigned to this batch and semester for the given academic year.";
        }
        else {
            $error = "Error: " . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mt-4 rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4 text-success">
                    <i class="bi bi-link-45deg fs-2 me-2"></i>
                    <h3 class="fw-bold mb-0">Assign Subject to Batch</h3>
                </div>

                <?php if (isset($error)): ?><div class="alert alert-danger shadow-sm border-0"><?php echo $error; ?></div><?php
endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Department</label>
                        <select class="form-select" id="dept_select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($depts as $d): ?>
                                <option value="<?php echo $d['department_id']; ?>"><?php echo htmlspecialchars($d['department_name']); ?></option>
                            <?php
endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Subject</label>
                        <select class="form-select" name="subject_id" id="subject_select" required disabled>
                            <option value="">-- Select Subject --</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Batch</label>
                        <select class="form-select" name="batch_id" id="batch_select" required disabled>
                            <option value="">-- Select Batch --</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Semester</label>
                            <select class="form-select" name="semester_id" required>
                                <?php foreach ($sems as $s): ?>
                                    <option value="<?php echo $s['semester_id']; ?>">Semester <?php echo $s['semester_no']; ?></option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Academic Year</label>
                            <input type="text" class="form-control" name="academic_year" placeholder="2025-2026" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-success fw-bold px-4 flex-fill py-2">Create Assignment</button>
                        <a href="list.php?tab=assignments" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dept_select').addEventListener('change', function() {
    const deptId = this.value;
    const subjectSelect = document.getElementById('subject_select');
    const batchSelect = document.getElementById('batch_select');
    
    subjectSelect.innerHTML = '<option value="">-- Loading Subjects --</option>';
    batchSelect.innerHTML = '<option value="">-- Loading Batches --</option>';
    subjectSelect.disabled = true;
    batchSelect.disabled = true;

    if (deptId) {
        // Fetch Subjects
        fetch(`get_subjects_and_batches.php?dept_id=${deptId}`)
            .then(response => response.json())
            .then(data => {
                subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
                data.subjects.forEach(s => {
                    subjectSelect.innerHTML += `<option value="${s.subject_id}">${s.subject_name} (${s.subject_code})</option>`;
                });
                
                batchSelect.innerHTML = '<option value="">-- Select Batch --</option>';
                data.batches.forEach(b => {
                    batchSelect.innerHTML += `<option value="${b.batch_id}">${b.batch_name}</option>`;
                });
                
                subjectSelect.disabled = false;
                batchSelect.disabled = false;
            });
    } else {
        subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        batchSelect.innerHTML = '<option value="">-- Select Batch --</option>';
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
