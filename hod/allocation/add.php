<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

// Fetch staff from this department
$stmt = $conn->prepare("SELECT * FROM Staff WHERE dept_id = ? AND status = 1 ORDER BY staff_name");
$stmt->execute([$dept_id]);
$staff_list = $stmt->fetchAll();

// Fetch Batches from this department with course_id for dynamic logic
$stmt = $conn->prepare("SELECT b.*, c.course_name, c.shift, c.course_id FROM Batch b 
                        JOIN Course c ON b.course_id = c.course_id 
                        WHERE c.dept_id = ? AND b.status = 1 
                        ORDER BY b.batch_name");
$stmt->execute([$dept_id]);
$batches = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = (int)$_POST['staff_id'];
    $sub_id = (int)$_POST['sub_id'];
    $batch_id = (int)$_POST['batch_id'];
    $course_sem_id = (int)$_POST['course_sem_id'];
    $academic_year = trim($_POST['academic_year']);

    try {
        if (!$course_sem_id || !$sub_id) throw new Exception("Please select both a Semester and a Subject.");

        // Check if allocation already exists for this staff-subject-batch combination
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Staff_Subject WHERE staff_id = ? AND sub_id = ? AND batch_id = ? AND academic_year = ?");
        $checkStmt->execute([$staff_id, $sub_id, $batch_id, $academic_year]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("This subject is already allocated to this faculty member for the selected batch.");
        }

        $stmt = $conn->prepare("INSERT INTO Staff_Subject (staff_id, sub_id, batch_id, course_sem_id, academic_year, status) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$staff_id, $sub_id, $batch_id, $course_sem_id, $academic_year]);
        
        header("Location: list.php?msg=Staff allocated successfully.");
        exit();
    }
    catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Department Name
$stmt = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
$stmt->execute([$dept_id]);
$dept_name = $stmt->fetchColumn();

// Use hod_header for consistent sidebar
require_once '../../includes/hod_header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3);">
                    <i class="bi bi-person-plus-fill fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;">New Subject Allocation</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Assign a faculty member to a specific subject and batch</p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="px-md-2" id="allocationForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Faculty Member</label>
                        <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                            <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-person-workspace"></i> </span>
                            <select class="form-select border-0 p-3 bg-white" name="staff_id" required>
                                <option value="">-- Select Faculty Member --</option>
                                <?php foreach ($staff_list as $s): ?>
                                    <option value="<?php echo $s['staff_id']; ?>"><?php echo htmlspecialchars($s['staff_name']); ?> (#<?php echo $s['staff_reg_id']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-2">Batch Selection</label>
                            <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                                <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-collection"></i> </span>
                                <select class="form-select border-0 p-3 bg-white" name="batch_id" id="batch_id" required>
                                    <option value="">-- Choose Batch --</option>
                                    <?php foreach ($batches as $b): ?>
                                        <option value="<?php echo $b['batch_id']; ?>" data-course-id="<?php echo $b['course_id']; ?>" data-admission-year="<?php echo $b['admission_year']; ?>">
                                            <?php echo htmlspecialchars($b['course_name'] . " - " . $b['batch_name'] . " (Shift " . $b['shift'] . ")"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase ms-2">Semester</label>
                            <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                                <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-calendar-range"></i> </span>
                                <select class="form-select border-0 p-3 bg-white" name="course_sem_id" id="course_sem_id" required disabled>
                                    <option value="">-- Select Semester --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Subject Selection</label>
                        <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                            <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-journal-check"></i> </span>
                            <select class="form-select border-0 p-3 bg-white" name="sub_id" id="sub_id" required disabled>
                                <option value="">-- Select Subject --</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-bold text-muted small text-uppercase ms-2">Academic Year</label>
                        <div class="input-group shadow-xs rounded-3 overflow-hidden border">
                            <span class="input-group-text border-0 bg-white ps-3 text-muted"> <i class="bi bi-calendar3"></i> </span>
                            <input type="text" class="form-control border-0 p-3 bg-white" name="academic_year" id="academic_year" required placeholder="e.g. 2023-2024" readonly>
                        </div>
                        <div class="form-text ms-2 small text-primary"><i class="bi bi-info-circle me-1"></i> Calculated automatically based on Batch & Semester</div>
                    </div>

                    <div class="d-flex gap-3 mt-2">
                        <button type="submit" class="btn btn-primary fw-bold p-3 px-5 rounded-pill flex-fill shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <span>Confirm & Allocate</span>
                            <i class="bi bi-arrow-right-short fs-4"></i>
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

    batchSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const courseId = option.getAttribute('data-course-id');
        
        semSelect.innerHTML = '<option value="">-- Select Semester --</option>';
        semSelect.disabled = true;
        subSelect.innerHTML = '<option value="">-- Select Subject --</option>';
        subSelect.disabled = true;
        acadYearInput.value = '';

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
        subSelect.disabled = true;
        
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
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
