<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';

$dept_id = $_SESSION['dept_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_id = trim($_POST['staff_reg_id']);
    $username = $reg_id; // Automatically use Reg ID as username
    // Generate a secure random password (not used for OTP login)
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $designation_id = (int)$_POST['designation_id'];
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $gender = $_POST['gender'] ?? 'Male';

    // Handle Photo Upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/staff/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $file_name = $reg_id . '_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo_path = 'assets/images/staff/' . $file_name;
        }
    }

    // Validation
    if (strlen($mobile) !== 10 || !is_numeric($mobile)) {
        $error = "Mobile number must be exactly 10 digits.";
    } else {
        try {
            $conn->beginTransaction();

            // 1. Create User
            $stmt = $conn->prepare("INSERT INTO Users (username, password_hash, role, status) VALUES (?, ?, 'Staff', 1)");
            $stmt->execute([$username, $password]);
            $user_id = $conn->lastInsertId();

            // 2. Create Staff
            $stmt = $conn->prepare("INSERT INTO Staff (user_id, dept_id, staff_reg_id, staff_name, gender, mobile_no, email, photo, address, designation_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$user_id, $dept_id, $reg_id, $name, $gender, $mobile, $email, $photo_path, $address, $designation_id]);

            $conn->commit();

            // Send initial OTP email to the new staff
            require_once '../../includes/mailer.php';
            $otp = sprintf("%06d", mt_rand(1, 999999));
            sendOTP($email, $otp, 'Welcome to Student Apt Portal - Your First Login OTP');

            header("Location: list.php?msg=Staff member registered successfully. An onboarding OTP has been sent to $email.");
            exit();
        }
        catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Designations
$designations = $conn->query("SELECT * FROM Designation WHERE status = 1 ORDER BY designation_name ASC")->fetchAll();

// Fetch Department Name
$stmt = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
$stmt->execute([$dept_id]);
$dept_name = $stmt->fetchColumn();

require_once '../../includes/hod_header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-person-plus-fill fs-3 text-white"></i>
                </div>
                <div class="flex-fill">
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Add Staff Member</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium"><?php echo htmlspecialchars($dept_name); ?> Department</p>
                </div>
            </div>
            <div class="card-body p-4 bg-white">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4 text-center py-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Staff Photo</label>
                            <input type="file" class="form-control rounded-3" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Staff Reg ID</label>
                            <input type="text" class="form-control rounded-3" name="staff_reg_id" placeholder="e.g. STF001" required>
                        </div>
                    </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Full Name</label>
                            <input type="text" class="form-control rounded-3" name="name" placeholder="Full name of faculty" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Gender</label>
                            <select class="form-select rounded-3" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Others">Other</option>
                            </select>
                        </div>
                    </div>
                    <!-- Username and Password fields removed - Login uses Register ID + OTP -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-medium">Designation</label>
                            <select class="form-select rounded-3" name="designation_id" required>
                                <option value="">-- Select Designation --</option>
                                <?php foreach ($designations as $d): ?>
                                    <option value="<?php echo $d['designation_id']; ?>"><?php echo htmlspecialchars($d['designation_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Email Address</label>
                            <input type="email" class="form-control rounded-3" name="email" placeholder="email@college.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Mobile Number</label>
                            <input type="text" class="form-control rounded-3" name="mobile" placeholder="10-digit mobile number" maxlength="10">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Residential Address</label>
                        <textarea class="form-control rounded-3" name="address" rows="3" placeholder="Enter full permanent address"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4 flex-fill py-3 rounded-3 shadow-sm">
                            <i class="bi bi-person-plus me-2"></i>Register Staff member
                        </button>
                        <a href="list.php" class="btn btn-outline-secondary px-4 py-3 rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
