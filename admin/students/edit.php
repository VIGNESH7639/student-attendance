<?php
require_once '../../includes/auth.php';
requireHODorTutor();
require_once '../../config/database.php';

$is_hod = isHOD();
$is_tutor = isMentor();
$is_admin = hasRole('Admin');
$hod_dept_id = $_SESSION['dept_id'] ?? null;
$staff_id = $_SESSION['staff_id'] ?? null;

if (!isset($_GET['id'])) {
    if ($is_tutor) {
        header("Location: ../../mentor/dashboard.php");
    } else {
        header("Location: list.php");
    }
    exit();
}

$id = $_GET['id'];
$tutor_batch_id = null;
if ($is_tutor && !$is_hod && !$is_admin) {
    $stmt = $conn->prepare("SELECT batch_id FROM Tutor WHERE staff_id = ? AND status = 1 LIMIT 1");
    $stmt->execute([$staff_id]);
    $tutor_batch_id = $stmt->fetchColumn();
    if (!$tutor_batch_id) {
        die("Error: No active batch assigned to you.");
    }
}

if ($is_hod) {
    $depts = $conn->prepare("SELECT * FROM Department WHERE dept_id = ? AND status = 1");
    $depts->execute([$hod_dept_id]);
    $depts = $depts->fetchAll();

    $batches = $conn->prepare("SELECT b.*, c.course_name, c.shift, 
                                (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id) AS student_count 
                                FROM Batch b JOIN Course c ON b.course_id = c.course_id 
                                WHERE c.dept_id = ? AND b.status = 1 ORDER BY b.batch_name ASC");
    $batches->execute([$hod_dept_id]);
    $batches = $batches->fetchAll();
} else if (!$is_tutor) {
    $depts = $conn->query("SELECT * FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();
    $batches = $conn->query("SELECT b.*, c.course_name, c.shift, 
                                (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id) AS student_count 
                                FROM Batch b JOIN Course c ON b.course_id = c.course_id 
                                WHERE b.status = 1 ORDER BY b.batch_name ASC")->fetchAll();
}
// Manual semester update removed to maintain history; use Promotion system for transitions

$batch_id_param = $_GET['batch_id'] ?? $_POST['batch_context'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_no = trim($_POST['register_no']);
    $username = $reg_no; // Automatically sync username with register number
    $gender = $_POST['gender'] ?? 'Male';
    $dob = $_POST['dob'] ?? null;
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);

    // Server-side validation
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile number must be exactly 10 digits.";
        throw new Exception($error);
    }

    $batch_id = ($is_tutor && !$is_hod && !$is_admin) ? $tutor_batch_id : $_POST['batch_id'];
    $status = (int)$_POST['status'];

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT user_id, photo FROM Student WHERE student_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $user_id = $row['user_id'];
        $photo_path = $row['photo'];

        // Handle Photo Upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/images/students/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = $reg_no . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                // Delete old photo if it exists
                if ($photo_path && file_exists('../../' . $photo_path)) {
                    @unlink('../../' . $photo_path);
                }
                $photo_path = 'assets/images/students/' . $file_name;
            }
        }

        // Update Users table
        $stmt = $conn->prepare("UPDATE Users SET username = ?, status = ? WHERE user_id = ?");
        $stmt->execute([$username, $status, $user_id]);

        // Update Student table
        $stmt = $conn->prepare("UPDATE Student SET register_no = ?, batch_id = ?, student_name = ?, gender = ?, dob = ?, email = ?, mobile_no = ?, photo = ?, address = ?, status = ? WHERE student_id = ?");
        $stmt->execute([$reg_no, $batch_id, $name, $gender, $dob, $email, $mobile, $photo_path, $address, $status, $id]);

        // Student_Semester update removed from basic profile edit to maintain history; use Promotion system

        $conn->commit();
        $redirect_url = "list.php?msg=Student updated successfully.";
        if ($is_tutor) {
            $redirect_url = "../../mentor/students.php?msg=Student updated successfully.";
        } else if ($batch_id_param) {
            $redirect_url .= "&batch_id=" . $batch_id_param;
        }
        header("Location: " . $redirect_url);
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

try {
    $stmt = $conn->prepare("SELECT s.*, u.username, 
              (SELECT sem.semester_no FROM Student_Semester ss JOIN Semester sem ON ss.semester_id = sem.semester_id WHERE ss.student_id = s.student_id AND ss.is_active = 1 LIMIT 1) as current_semester_no,
              c.dept_id
              FROM Student s 
              JOIN Users u ON s.user_id = u.user_id 
              JOIN Batch b ON s.batch_id = b.batch_id
              JOIN Course c ON b.course_id = c.course_id
              WHERE s.student_id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    if (!$student) {
        $redir = $is_tutor ? "../../mentor/dashboard.php?error=Student not found." : "list.php?error=Student not found.";
        header("Location: " . $redir);
        exit();
    }
    
    // Authorization specifically for Tutor
    if ($is_tutor && !$is_hod && !$is_admin && $student['batch_id'] != $tutor_batch_id) {
        die("Error: Student does not belong to your assigned batch.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($is_hod) {
    require_once '../../includes/hod_header.php';
} else if ($is_tutor) {
    require_once '../../includes/header.php';
} else {
    require_once '../../includes/header.php';
}
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
            <div class="card-header py-2 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-pencil-square fs-4 text-white"></i>
                </div>
                <div class="flex-fill">
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Edit Student Profile</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Reg No: <?php echo htmlspecialchars($student['register_no']); ?></p>
                </div>
            </div>
            <div class="card-body p-3 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-1 text-center py-1 text-small" style="font-size: 0.75rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="batch_context" value="<?php echo htmlspecialchars($batch_id_param); ?>">
                    
                    <div class="row g-2 mb-2">
                        <div class="col-md-3 text-center">
                            <label class="form-label d-block fw-bold small text-muted text-uppercase">Profile Photo</label>
                            <?php if ($student['photo'] && file_exists('../../' . $student['photo'])): ?>
                                <img src="../../<?php echo $student['photo']; ?>" class="img-thumbnail rounded-3 shadow-sm mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded-3 d-inline-flex align-items-center justify-content-center mb-3 text-muted" style="width: 120px; height: 120px; border: 2px dashed #dee2e6;">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control form-control-sm" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Full Name</label>
                                    <input type="text" class="form-control bg-light border-0 py-2 rounded-3" name="name" value="<?php echo htmlspecialchars($student['student_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Register No</label>
                                    <input type="text" class="form-control bg-light border-0 py-2 rounded-3" name="register_no" value="<?php echo htmlspecialchars($student['register_no']); ?>" required>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Gender</label>
                                    <select class="form-select bg-light border-0 py-2 rounded-3" name="gender" required>
                                        <option value="Male" <?php echo ($student['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($student['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($student['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Date of Birth</label>
                                    <input type="date" class="form-control bg-light border-0 py-2 rounded-3" name="dob" value="<?php echo $student['dob']; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-1">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Email Address</label>
                            <input type="email" class="form-control form-control-sm bg-light border-0" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Mobile Number</label>
                            <input type="text" class="form-control form-control-sm bg-light border-0" name="mobile" value="<?php echo htmlspecialchars($student['mobile_no']); ?>" required maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                        </div>
                    </div>

                    <div class="row g-2 mb-1">
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Correspondence Address</label>
                            <textarea class="form-control form-control-sm bg-light border-0" name="address" rows="1"><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-4" <?php echo ($is_hod || $is_tutor) ? 'style="display:none;"' : ''; ?>>
                            <label class="form-label fw-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Department</label>
                            <select class="form-select form-select-sm bg-light border-0" name="dept_id" id="dept_id" <?php echo ($is_hod || $is_tutor) ? '' : 'required'; ?>>
                                <?php if (!$is_tutor): foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['dept_id']; ?>" <?php echo($d['dept_id'] == $student['dept_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['dept_name']); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="<?php echo ($is_hod || $is_tutor) ? ($is_tutor ? 'd-none' : 'col-md-5') : 'col-md-4'; ?>">
                            <label class="form-label fw-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Batch</label>
                            <select class="form-select form-select-sm bg-light border-0" name="batch_id" id="batch_id" <?php echo $is_tutor ? '' : 'required'; ?>>
                                <?php if (!$is_tutor): foreach ($batches as $b): ?>
                                    <option value="<?php echo $b['batch_id']; ?>" <?php echo($b['batch_id'] == $student['batch_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['batch_name'] . " - " . $b['course_name'] . " (Shift " . $b['shift'] . ") - [" . $b['student_count'] . " Students]"); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="<?php echo ($is_hod || $is_tutor) ? ($is_tutor ? 'col-md-6' : 'col-md-4') : 'col-md-2'; ?>">
                            <label class="form-label fw-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Current Semester</label>
                            <div class="bg-primary bg-opacity-10 text-primary fw-bold p-1 rounded-3 text-center border border-primary-subtle" style="font-size: 0.8rem;">
                                Sem <?php echo $student['current_semester_no'] ?? '1'; ?>
                            </div>
                        </div>
                        <div class="<?php echo ($is_hod || $is_tutor) ? ($is_tutor ? 'col-md-6' : 'col-md-3') : 'col-md-2'; ?>">
                            <label class="form-label fw-bold text-muted text-uppercase mb-0" style="font-size: 0.65rem;">Status</label>
                            <select class="form-select form-select-sm bg-light border-0" name="status">
                                <option value="1" <?php echo $student['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $student['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm">
                            <i class="bi bi-check-circle me-2"></i> Save Changes
                        </button>
                        <a href="<?php echo $is_tutor ? '../../mentor/students.php' : ($batch_id_param ? 'list.php?batch_id=' . $batch_id_param : 'list.php'); ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill border-0">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>

<script>
document.getElementById('dept_id').addEventListener('change', function() {
    const deptId = this.value;
    const batchSelect = document.getElementById('batch_id');
    const currentBatchId = "<?php echo $student['batch_id']; ?>"; // Keep track of current selection
    
    batchSelect.innerHTML = '<option value="">-- Loading Batches... --</option>';
    
    fetch(`get_batches.php?dept_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            batchSelect.innerHTML = '<option value="">-- Select Batch --</option>';
            data.forEach(batch => {
                const option = document.createElement('option');
                option.value = batch.batch_id;
                option.textContent = `${batch.batch_name} - ${batch.course_name} (Shift ${batch.shift}) - [${batch.student_count} Students]`;
                if (batch.batch_id == currentBatchId) option.selected = true;
                batchSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching batches:', error);
        });
});
</script>
