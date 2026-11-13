<?php
require_once '../includes/auth.php';
requireRole('Staff');
require_once '../config/database.php';

$staff_id = $_SESSION['staff_id'] ?? null;
$batch_id = $_SESSION['mentor_batch_id'] ?? null;

if (!$batch_id) {
    header("Location: dashboard.php");
    exit();
}

// Get batch info
$stmt = $conn->prepare("SELECT b.batch_name, c.course_name, c.shift FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE b.batch_id = ?");
$stmt->execute([$batch_id]);
$batch = $stmt->fetch();

if (!$batch) {
    header("Location: dashboard.php");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $reg_no   = trim($_POST['register_no']);
    $username = $reg_no; // same as register number
    $gender   = $_POST['gender'] ?? 'Male';
    $dob      = $_POST['dob'] ?? null;
    $email    = trim($_POST['email']);
    $mobile   = trim($_POST['mobile']);
    $address  = trim($_POST['address']);

    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile number must be exactly 10 digits.";
    } else {
        // Password = DOB in ddmmyyyy format if not provided
        $password = password_hash(date('dmY', strtotime($dob)), PASSWORD_DEFAULT);

        // Handle Photo Upload
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/students/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext  = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $file_name = $reg_no . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $file_name)) {
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

            // Default to Semester 1
            $stmt = $conn->prepare("INSERT INTO Student_Semester (student_id, semester_id, academic_year, is_active) VALUES (?, 1, ?, 1)");
            $stmt->execute([$student_id, date('Y')]);

            $conn->commit();
            header("Location: students.php?msg=Student registered successfully.");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

require_once '../includes/mentor_header.php';
?>

<div class="row justify-content-center mt-1">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-2">
            <div class="card-header py-1 px-3 d-flex align-items-center gap-2 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-person-plus-fill fs-5 text-white"></i>
                </div>
                <div class="flex-fill">
                    <h6 class="fw-bold text-white mb-0" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Register Student</h6>
                    <p class="text-white text-opacity-80 mb-0" style="font-size: 0.7rem;">
                        <?php echo htmlspecialchars($batch['batch_name'] . ' — ' . $batch['course_name']); ?>
                    </p>
                </div>
            </div>
            <div class="card-body p-3 bg-white">

                <?php if ($error): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-3 text-center py-2 text-small">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Full Name</label>
                            <input type="text" class="form-control bg-light border-0 py-2 rounded-3" name="name" required placeholder="Full Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Register No</label>
                            <input type="text" class="form-control bg-light border-0 py-2 rounded-3" name="register_no" required placeholder="Register Number">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Gender</label>
                            <select class="form-select bg-light border-0 py-2 rounded-3" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Date of Birth</label>
                            <input type="date" class="form-control bg-light border-0 py-2 rounded-3" name="dob" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Mobile Number</label>
                            <input type="text" class="form-control bg-light border-0 py-2 rounded-3" name="mobile" required placeholder="10 Digits" maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Email Address</label>
                            <input type="email" class="form-control bg-light border-0 py-2 rounded-3" name="email" placeholder="email@college.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Student Photo</label>
                            <input type="file" class="form-control bg-light border-0 py-2 rounded-3" name="photo" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold extra-small text-muted text-uppercase mb-1">Correspondence Address</label>
                        <textarea class="form-control bg-light border-0 py-2 rounded-3" name="address" rows="2" placeholder="Full postal address"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm">
                            <i class="bi bi-person-check me-2"></i> Register Student
                        </button>
                        <a href="students.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill border-0">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
