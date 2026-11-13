<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$is_hod = isHOD();
$hod_dept_id = $_SESSION['dept_id'] ?? null;
$staff_id = $_SESSION['staff_id'] ?? null;

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];

// Fetch Batches for this department
$batches = $conn->prepare("SELECT b.*, c.course_name, c.shift, 
                            (SELECT COUNT(*) FROM Student s WHERE s.batch_id = b.batch_id) AS student_count 
                            FROM Batch b JOIN Course c ON b.course_id = c.course_id 
                            WHERE c.dept_id = ? AND b.status = 1 ORDER BY b.batch_name ASC");
$batches->execute([$hod_dept_id]);
$batches = $batches->fetchAll();

$batch_id_param = $_GET['batch_id'] ?? $_POST['batch_context'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_no = trim($_POST['register_no']);
    $username = trim($_POST['username']);
    $gender = $_POST['gender'] ?? 'Male';
    $dob = $_POST['dob'] ?? null;
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);

    // Server-side validation
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile number must be exactly 10 digits.";
    } else {
        $batch_id = $_POST['batch_id'];
        $status = (int)$_POST['status'];

        // Security Check: Batch must belong to HOD's department
        $check = $conn->prepare("SELECT b.batch_id FROM Batch b JOIN Course c ON b.course_id = c.course_id WHERE b.batch_id = ? AND c.dept_id = ?");
        $check->execute([$batch_id, $hod_dept_id]);
        if (!$check->fetch()) {
            die("Error: Invalid Batch selection.");
        }

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

            $conn->commit();
            $redirect_url = "list.php?msg=Student updated successfully.";
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

try {
    $stmt = $conn->prepare("SELECT s.*, u.username, 
              (SELECT sem.semester_no FROM Student_Semester ss JOIN Semester sem ON ss.semester_id = sem.semester_id WHERE ss.student_id = s.student_id AND ss.is_active = 1 LIMIT 1) as current_semester_no,
              c.dept_id
              FROM Student s 
              JOIN Users u ON s.user_id = u.user_id 
              JOIN Batch b ON s.batch_id = b.batch_id
              JOIN Course c ON b.course_id = c.course_id
              WHERE s.student_id = ? AND c.dept_id = ?");
    $stmt->execute([$id, $hod_dept_id]);
    $student = $stmt->fetch();
    if (!$student) {
        header("Location: list.php?error=Student not found or access denied.");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../includes/hod_header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-9">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-pencil-square fs-3 text-white"></i>
                </div>
                <div class="flex-fill">
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Edit Student Profile</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Reg No: <?php echo htmlspecialchars($student['register_no']); ?></p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="batch_context" value="<?php echo htmlspecialchars($batch_id_param); ?>">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 text-center">
                            <label class="form-label d-block fw-bold small text-muted text-uppercase">Profile Photo</label>
                            <?php if ($student['photo'] && file_exists('../../' . $student['photo'])): ?>
                                <img src="../../<?php echo $student['photo']; ?>" class="img-thumbnail rounded-4 shadow-sm mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded-4 d-inline-flex align-items-center justify-content-center mb-3 text-muted" style="width: 150px; height: 150px; border: 2px dashed #dee2e6;">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control form-control-sm" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Full Name</label>
                                    <input type="text" class="form-control bg-light border-0 py-3 rounded-3" name="name" value="<?php echo htmlspecialchars($student['student_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Register No</label>
                                    <input type="text" class="form-control bg-light border-0 py-3 rounded-3" name="register_no" value="<?php echo htmlspecialchars($student['register_no']); ?>" required>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Username</label>
                                    <input type="text" class="form-control bg-light border-0 py-3 rounded-3" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Gender</label>
                                    <select class="form-select bg-light border-0 py-3 rounded-3" name="gender" required>
                                        <option value="Male" <?php echo ($student['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($student['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($student['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Date of Birth</label>
                                    <input type="date" class="form-control bg-light border-0 py-3 rounded-3" name="dob" value="<?php echo $student['dob']; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Email Address</label>
                            <input type="email" class="form-control bg-light border-0 py-3 rounded-3" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Mobile Number</label>
                            <input type="text" class="form-control bg-light border-0 py-3 rounded-3" name="mobile" value="<?php echo htmlspecialchars($student['mobile_no']); ?>" required maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Correspondence Address</label>
                            <textarea class="form-control bg-light border-0 py-3 rounded-3" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-muted text-uppercase">Batch</label>
                            <select class="form-select bg-light border-0 py-3 rounded-3" name="batch_id" id="batch_id" required>
                                <?php foreach ($batches as $b): ?>
                                    <option value="<?php echo $b['batch_id']; ?>" <?php echo($b['batch_id'] == $student['batch_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b['batch_name'] . " - " . $b['course_name'] . " (Shift " . $b['shift'] . ") - [" . $b['student_count'] . " Students]"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted text-uppercase">Current Semester</label>
                            <div class="bg-primary bg-opacity-10 text-primary fw-bold py-3 px-4 rounded-3 text-center border border-primary-subtle">
                                Sem <?php echo $student['current_semester_no'] ?? '1'; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Status</label>
                            <select class="form-select bg-light border-0 py-3 rounded-3" name="status">
                                <option value="1" <?php echo $student['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $student['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-5 py-3 rounded-pill shadow-sm">
                            <i class="bi bi-check-circle me-2"></i> Save Changes
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
