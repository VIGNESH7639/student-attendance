<?php
require_once '../../includes/auth.php';
requireHODorTutor();
require_once '../../config/database.php';

$is_hod = isHOD();
$is_tutor = isMentor();
$is_admin = hasRole('Admin');
$hod_dept_id = $_SESSION['dept_id'] ?? null;
$staff_id = $_SESSION['staff_id'] ?? null;

$tutor_batch_id = null;
if ($is_tutor && !$is_hod && !$is_admin) {
    $stmt = $conn->prepare("SELECT t.batch_id, b.batch_name, c.dept_id FROM Tutor t JOIN Batch b ON t.batch_id = b.batch_id JOIN Course c ON b.course_id = c.course_id WHERE t.staff_id = ? AND t.status = 1 LIMIT 1");
    $stmt->execute([$staff_id]);
    $tutor_data = $stmt->fetch();
    if (!$tutor_data) {
        die("Error: No active batch assigned to you as a Tutor.");
    }
    $tutor_batch_id = $tutor_data['batch_id'];
    $tutor_dept_id = $tutor_data['dept_id'];
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
// Removed manual semester fetch for registration; now defaults to Semester 1

$batch_id_param = $_GET['batch_id'] ?? null;
$preselected_batch = null;

if ($batch_id_param) {
    if ($is_hod) {
        $stmt = $conn->prepare("SELECT b.* FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE b.batch_id = ? AND c.dept_id = ?");
        $stmt->execute([$batch_id_param, $hod_dept_id]);
    } else if ($is_tutor) {
        $stmt = $conn->prepare("SELECT b.*, c.course_name, c.shift, d.dept_name, (SELECT sem.semester_no FROM Student s JOIN Student_Semester ss ON s.student_id = ss.student_id JOIN Semester sem ON ss.semester_id = sem.semester_id WHERE s.batch_id = b.batch_id AND ss.is_active = 1 LIMIT 1) as semester_no FROM Batch b JOIN Course c ON b.course_id = c.course_id JOIN Department d ON c.dept_id = d.dept_id WHERE b.batch_id = ? AND b.batch_id = ?");
        $stmt->execute([$batch_id_param, $tutor_batch_id]);
    } else {
        // The instruction provided a query that seems to be for a list of batches, not a single preselected batch.
        // To maintain functionality and syntactic correctness, I'm adapting the original query to include
        // course_name, shift, and semester_no as implied by the instruction's new query structure,
        // while keeping the WHERE clause for preselection.
        // The original query was: SELECT b.*, d.dept_name, c.dept_id FROM Batch b JOIN Course c ON b.course_id = c.course_id JOIN Department d ON c.dept_id = d.dept_id WHERE b.batch_id = ?
        $stmt = $conn->prepare("SELECT b.*, c.course_name, c.shift, d.dept_name,
                        (SELECT sem.semester_no
                         FROM Student s
                         JOIN Student_Semester ss ON s.student_id = ss.student_id
                         JOIN Semester sem ON ss.semester_id = sem.semester_id
                         WHERE s.batch_id = b.batch_id AND ss.is_active = 1
                         LIMIT 1) as semester_no
                        FROM Batch b
                        JOIN Course c ON b.course_id = c.course_id
                        JOIN Department d ON c.dept_id = d.dept_id
                        WHERE b.batch_id = ?");
        $stmt->execute([$batch_id_param]);
    }
    $preselected_batch = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_no = trim($_POST['register_no']);
    $username_input = isset($_POST['username']) ? trim($_POST['username']) : '';
    $username = empty($username_input) ? $reg_no : $username_input;
    $gender = $_POST['gender'] ?? 'Male';
    $dob = $_POST['dob'] ?? null;
    $password_input = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($password_input) && !empty($dob)) {
        $password_plain = date('dmY', strtotime($dob));
        $password = password_hash($password_plain, PASSWORD_DEFAULT);
    } else {
        $password = password_hash($password_input, PASSWORD_DEFAULT);
    }
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);

    // Server-side validation
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile number must be exactly 10 digits.";
    } else {
        $batch_id = ($is_tutor && !$is_hod && !$is_admin) ? $tutor_batch_id : $_POST['batch_id'];
    $sem_id = 1; // Default to Semester 1 for new registrations
    $academic_year = date('Y');

    // Handle Photo Upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/students/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $file_name = $reg_no . '_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo_path = 'assets/images/students/' . $file_name;
        }
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO Users (username, password_hash, role, status) VALUES (?, ?, 'Student', 1)");
        $stmt->execute([$username, $password]);
        $user_id = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO Student (user_id, register_no, batch_id, student_name, gender, dob, email, mobile_no, photo, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$user_id, $reg_no, $batch_id, $name, $gender, $dob, $email, $mobile, $photo_path, $address]);
        $student_id = $conn->lastInsertId();

        // Initialize Student_Semester as Active
        $stmt = $conn->prepare("INSERT INTO Student_Semester (student_id, semester_id, academic_year, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$student_id, $sem_id, $academic_year]);

        $conn->commit();
        $redirect_url = "list.php?msg=Student registered successfully.";
        if ($is_tutor) {
            $redirect_url = "../../mentor/dashboard.php?msg=Student registered successfully.";
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
    <div class="col-md-9">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-person-plus-fill fs-3 text-white"></i>
                </div>
                <div class="flex-fill">
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Register Student</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium"><?php if ($preselected_batch): ?>Batch: <?php echo htmlspecialchars($preselected_batch['batch_name']); ?><?php else: ?>Add a new student to the system<?php endif; ?></p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Student Photo</label>
                            <input type="file" class="form-control bg-light border-0 py-3 rounded-3" name="photo" accept="image/*">
                            <div class="form-text small">Recommended: Square image, max 2MB</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Full Name</label>
                            <input type="text" class="form-control bg-light border-0 py-3 rounded-3" name="name" required placeholder="Full Name">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Register No / Roll No</label>
                            <input type="text" class="form-control bg-light border-0 py-3 rounded-3" name="register_no" required placeholder="Register Number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Gender</label>
                            <select class="form-select bg-light border-0 py-3 rounded-3" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date of Birth</label>
                            <input type="date" class="form-control bg-light border-0 py-3 rounded-3" name="dob" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Email Address</label>
                            <input type="email" class="form-control bg-light border-0 py-3 rounded-3" name="email" placeholder="email@college.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Mobile Number</label>
                            <input type="text" class="form-control bg-light border-0 py-3 rounded-3" name="mobile" required placeholder="10 Digits" maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Correspondence Address</label>
                            <textarea class="form-control bg-light border-0 py-3 rounded-3" name="address" rows="3" placeholder="Full postal address"></textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6" <?php echo ($is_hod || $is_tutor) ? 'style="display:none;"' : ''; ?>>
                            <label class="form-label fw-bold small text-muted text-uppercase">Department</label>
                            <select class="form-select bg-light border-0 py-3 rounded-3" name="dept_id" id="dept_id" <?php echo ($is_hod || $is_tutor) ? '' : 'required'; ?>>
                                <?php if (!$is_tutor): foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['dept_id']; ?>" <?php echo($is_hod && $d['dept_id'] == $hod_dept_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['dept_name']); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6" <?php echo $is_tutor ? 'style="display:none;"' : ''; ?>>
                            <label class="form-label fw-bold small text-muted text-uppercase">Batch</label>
                            <select class="form-select bg-light border-0 py-3 rounded-3" name="batch_id" id="batch_id" <?php echo $is_tutor ? '' : 'required'; ?>>
                                <option value="">-- Select Batch --</option>
                                <?php if (!$is_tutor): foreach ($batches as $b): ?>
                                    <option value="<?php echo $b['batch_id']; ?>" <?php echo ($batch_id_param == $b['batch_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['batch_name'] . " - " . $b['course_name'] . " (Shift " . $b['shift'] . ") - [" . $b['student_count'] . " Students]"); ?>
                                    </option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-5 py-3 rounded-pill shadow-sm">
                            <i class="bi bi-person-check me-2"></i> Register Student
                        </button>
                        <a href="<?php echo $is_tutor ? '../../mentor/dashboard.php' : ($batch_id_param ? 'list.php?batch_id=' . $batch_id_param : 'list.php'); ?>" class="btn btn-outline-secondary px-4 py-3 rounded-pill border-0">
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
    
    batchSelect.innerHTML = '<option value="">-- Loading Batches... --</option>';
    
    if (!deptId) {
        batchSelect.innerHTML = '<option value="">-- Select Department First --</option>';
        return;
    }

    fetch(`get_batches.php?dept_id=${deptId}`)
        .then(response => response.json())
        .then(data => {
            batchSelect.innerHTML = '<option value="">-- Select Batch --</option>';
            data.forEach(batch => {
                const option = document.createElement('option');
                option.value = batch.batch_id;
                option.textContent = `${batch.batch_name} - ${batch.course_name} (Shift ${batch.shift}) - [${batch.student_count} Students]`;
                batchSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching batches:', error);
            batchSelect.innerHTML = '<option value="">-- Error loading batches --</option>';
        });
});

// Trigger change on page load if preselected
if (document.getElementById('dept_id').value) {
    const event = new Event('change');
    // We only trigger if it's not a fresh page with a preselected batch_id context already in HTML
    // But since we are using PHP to render the initial batches, this script will override them.
    // To avoid double loading or overriding the preselected batch from PHP, we can check if batch_id is empty.
    if (!document.getElementById('batch_id').value) {
        document.getElementById('dept_id').dispatchEvent(event);
    }
}
</script>
