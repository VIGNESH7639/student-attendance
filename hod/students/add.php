<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$is_hod = isHOD();
$hod_dept_id = $_SESSION['dept_id'] ?? null;
$staff_id = $_SESSION['staff_id'] ?? null;

// Fetch Batches for this department with course info and student count
$batches = $conn->prepare("SELECT b.*, c.course_name, c.shift, 
                            (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id) AS student_count 
                            FROM Batch b JOIN Course c ON b.course_id = c.course_id 
                            WHERE c.dept_id = ? AND b.status = 1 ORDER BY b.batch_name ASC");
$batches->execute([$hod_dept_id]);
$batches = $batches->fetchAll();

$batch_id_param = $_GET['batch_id'] ?? null;
$preselected_batch = null;

if ($batch_id_param) {
    $stmt = $conn->prepare("SELECT b.* FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE b.batch_id = ? AND c.dept_id = ?");
    $stmt->execute([$batch_id_param, $hod_dept_id]);
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
        $batch_id = $_POST['batch_id'];
        
        // Security Check: Batch must belong to HOD's department
        $check = $conn->prepare("SELECT b.batch_id FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE b.batch_id = ? AND c.dept_id = ?");
        $check->execute([$batch_id, $hod_dept_id]);
        if (!$check->fetch()) {
            die("Error: Invalid Batch selection.");
        }

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
            if ($batch_id_param) {
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

require_once '../../includes/hod_header.php';
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
                    <p class="text-white text-opacity-80 small mb-0 fw-medium"><?php if ($preselected_batch): ?>Batch: <?php echo htmlspecialchars($preselected_batch['batch_name']); ?><?php else: ?>Add a new student to your department<?php endif; ?></p>
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
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Batch</label>
                            <select class="form-select bg-light border-0 py-3 rounded-3" name="batch_id" id="batch_id" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach ($batches as $b): ?>
                                    <option value="<?php echo $b['batch_id']; ?>" <?php echo ($batch_id_param == $b['batch_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['batch_name'] . " - " . $b['course_name'] . " (Shift " . $b['shift'] . ") - [" . $b['student_count'] . " Students]"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-5 py-3 rounded-pill shadow-sm">
                            <i class="bi bi-person-check me-2"></i> Register Student
                        </button>
                        <a href="list.php<?php echo $batch_id_param ? '?batch_id=' . $batch_id_param : ''; ?>" class="btn btn-outline-secondary px-4 py-3 rounded-pill border-0">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
