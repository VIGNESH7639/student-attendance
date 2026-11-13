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

// Fetch allocation details first
try {
    $stmt = $conn->prepare("SELECT ss.* FROM Staff_Subject ss 
                            JOIN Course_Semester cs ON ss.course_sem_id = cs.course_sem_id
                            JOIN Course c ON cs.course_id = c.course_id
                            WHERE ss.alloc_id = ? AND c.dept_id = ?");
    $stmt->execute([$id, $dept_id]);
    $allocation = $stmt->fetch();
    if (!$allocation) {
        header("Location: list.php?error=Allocation not found.");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = (int)$_POST['staff_id'];
    $sub_id = (int)$_POST['sub_id'];
    $batch_id = (int)$_POST['batch_id'];
    $academic_year = trim($_POST['academic_year']);
    $status = (int)$_POST['status'];

    try {
        $stmt = $conn->prepare("SELECT course_sem_id FROM Subject WHERE sub_id = ?");
        $stmt->execute([$sub_id]);
        $course_sem_id = $stmt->fetchColumn();

        // Check if this new combination already exists elsewhere (prevent collision)
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Staff_Subject WHERE staff_id = ? AND sub_id = ? AND batch_id = ? AND academic_year = ? AND alloc_id != ?");
        $checkStmt->execute([$staff_id, $sub_id, $batch_id, $academic_year, $id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("This updated combination is already allocated to this faculty member in another record.");
        }

        $stmt = $conn->prepare("UPDATE Staff_Subject SET staff_id = ?, sub_id = ?, batch_id = ?, course_sem_id = ?, academic_year = ?, status = ? WHERE alloc_id = ?");
        $stmt->execute([$staff_id, $sub_id, $batch_id, $course_sem_id, $academic_year, $status, $id]);
        header("Location: list.php?msg=Allocation updated successfully.");
        exit();
    }
    catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch lists
$staff_list = $conn->prepare("SELECT * FROM Staff WHERE dept_id = ? AND status = 1 ORDER BY staff_name");
$staff_list->execute([$dept_id]);
$staff_list = $staff_list->fetchAll();

// Fetch Batches from this department with course_id
$batches = $conn->prepare("SELECT b.*, c.course_name, c.shift, c.course_id FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE c.dept_id = ? AND b.status = 1 ORDER BY b.batch_name");
$batches->execute([$dept_id]);
$batches = $batches->fetchAll();

// Fetch current semesters for the course (to pre-fill)
$stmt = $conn->prepare("SELECT cs.course_sem_id, sem.semester_no FROM Course_Semester cs JOIN Semester sem ON cs.semester_id = sem.semester_id WHERE cs.course_id = (SELECT course_id FROM Batch WHERE batch_id = ?) ORDER BY sem.semester_no");
$stmt->execute([$allocation['batch_id']]);
$current_semesters = $stmt->fetchAll();

// Fetch current subjects for the semester (to pre-fill)
$stmt = $conn->prepare("SELECT sub_id, sub_name, sub_code FROM Subject WHERE course_sem_id = ? AND status = 1 ORDER BY sub_name");
$stmt->execute([$allocation['course_sem_id']]);
$current_subjects = $stmt->fetchAll();

$dept_name = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
$dept_name->execute([$dept_id]);
$dept_name = $dept_name->fetchColumn();

require_once '../../includes/hod_header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3);">
                    <i class="bi bi-pencil-square fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;">Edit Allocation</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Update faculty assignment for this subject and batch</p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="px-md-2" id="editAllocationForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Faculty Member</label>
                        <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                            <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-person-workspace"></i> </span>
                            <select class="form-select border-0 p-3 bg-white" name="staff_id" required>
                                <?php foreach ($staff_list as $s): ?>
                                    <option value="<?php echo $s['staff_id']; ?>" <?php echo $s['staff_id'] == $allocation['staff_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['staff_name']); ?> (#<?php echo $s['staff_reg_id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-2">Batch</label>
                            <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                                <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-collection"></i> </span>
                                <select class="form-select border-0 p-3 bg-white" name="batch_id" id="batch_id" required>
                                    <?php foreach ($batches as $b): ?>
                                        <option value="<?php echo $b['batch_id']; ?>" data-course-id="<?php echo $b['course_id']; ?>" data-admission-year="<?php echo $b['admission_year']; ?>" <?php echo $b['batch_id'] == $allocation['batch_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-2">Semester</label>
                            <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                                <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-calendar-range"></i> </span>
                                <select class="form-select border-0 p-3 bg-white" name="course_sem_id" id="course_sem_id" required>
                                    <?php foreach ($current_semesters as $sem): ?>
                                        <option value="<?php echo $sem['course_sem_id']; ?>" data-semester-no="<?php echo $sem['semester_no']; ?>" <?php echo $sem['course_sem_id'] == $allocation['course_sem_id'] ? 'selected' : ''; ?>>
                                            Semester <?php echo $sem['semester_no']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-2">Current Status</label>
                            <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                                <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-toggle-on"></i> </span>
                                <select class="form-select border-0 p-3 bg-white" name="status">
                                    <option value="1" <?php echo $allocation['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo $allocation['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Subject Master</label>
                        <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                            <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-journal-check"></i> </span>
                            <select class="form-select border-0 p-3 bg-white" name="sub_id" id="sub_id" required>
                                <?php foreach ($current_subjects as $sub): ?>
                                    <option value="<?php echo $sub['sub_id']; ?>" <?php echo $sub['sub_id'] == $allocation['sub_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sub['sub_name']); ?> [<?php echo $sub['sub_code']; ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Academic Year</label>
                        <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                            <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-calendar3"></i> </span>
                            <input type="text" class="form-control border-0 p-3 bg-white" name="academic_year" id="academic_year" required value="<?php echo htmlspecialchars($allocation['academic_year']); ?>" readonly>
                        </div>
                        <div class="form-text ms-2 small text-primary"><i class="bi bi-info-circle me-1"></i> Calculated automatically based on Batch & Semester</div>
                    </div>

                    <div class="d-flex gap-3 mt-2">
                        <button type="submit" class="btn btn-primary fw-bold p-3 px-5 rounded-pill flex-fill shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <span>Update Allocation</span>
                            <i class="bi bi-check-circle-fill"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const batchSelect = document.getElementById('batch_id');
    const semSelect = document.getElementById('course_sem_id');
    const subSelect = document.getElementById('sub_id');
    const acadYearInput = document.getElementById('academic_year');

    function calculateAcademicYear() {
        const batchOption = batchSelect.options[batchSelect.selectedIndex];
        if (!batchOption || batchSelect.value === "") {
            acadYearInput.value = '';
            return;
        }
        
        let admissionYear = parseInt(batchOption.getAttribute('data-admission-year'));
        
        // Fallback: Parse from batch name (e.g. "2024-2027")
        if (isNaN(admissionYear) || admissionYear === 0) {
            const batchName = batchOption.text;
            const match = batchName.match(/\d{4}/);
            if (match) {
                admissionYear = parseInt(match[0]);
            }
        }
        
        const semOption = semSelect.options[semSelect.selectedIndex];
        if (!semOption || semSelect.value === "") {
            acadYearInput.value = '';
            return;
        }
        const semesterNo = parseInt(semOption.getAttribute('data-semester-no'));

        if (!isNaN(admissionYear) && !isNaN(semesterNo)) {
            const yearOffset = Math.floor((semesterNo - 1) / 2);
            const startYear = admissionYear + yearOffset;
            const endYear = startYear + 1;
            acadYearInput.value = `${startYear}-${endYear}`;
        } else {
            acadYearInput.value = '';
        }
    }

    // Run on load to verify/set initial value
    calculateAcademicYear();

    batchSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const courseId = option.getAttribute('data-course-id');
        
        semSelect.innerHTML = '<option value="">-- Select Semester --</option>';
        subSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        subSelect.disabled = true;

        if (courseId) {
            fetch('../subjects/get_semesters.php?course_id=' + courseId)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        data.forEach(sem => {
                            const opt = document.createElement('option');
                            opt.value = sem.course_sem_id;
                            opt.setAttribute('data-semester-no', sem.semester_no);
                            opt.textContent = 'Semester ' + sem.semester_no;
                            semSelect.appendChild(opt);
                        });
                        semSelect.disabled = false;
                    }
                });
        }
    });

    semSelect.addEventListener('change', function() {
        const courseSemId = this.value;
        subSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        
        calculateAcademicYear();

        if (courseSemId) {
            fetch('get_subjects_by_semester.php?course_sem_id=' + courseSemId)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        data.forEach(sub => {
                            const opt = document.createElement('option');
                            opt.value = sub.sub_id;
                            opt.textContent = `${sub.sub_name} [${sub.sub_code}]`;
                            subSelect.appendChild(opt);
                        });
                        subSelect.disabled = false;
                    }
                });
        } else {
            subSelect.disabled = true;
        }
    });
});
</script>
<?php require_once '../../includes/footer.php'; ?>
