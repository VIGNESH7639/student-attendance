<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$depts = $conn->query("SELECT * FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();
$designations = $conn->query("SELECT * FROM Designation WHERE status = 1 ORDER BY designation_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_id = trim($_POST['staff_reg_id']);
    $username = $reg_id; // Automatically use Reg ID as username
    // Generate a random high-entropy password (not typically used for OTP login)
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $dept_id = (int)$_POST['dept_id'];
    $designation_id = (int)$_POST['designation_id'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
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
    if (strlen($phone) !== 10 || !is_numeric($phone)) {
        $error = "Mobile number must be exactly 10 digits.";
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO Users (username, password_hash, role, status) VALUES (?, ?, 'Staff', 1)");
            $stmt->execute([$username, $password]);
            $user_id = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO Staff (user_id, dept_id, staff_reg_id, staff_name, gender, mobile_no, email, photo, address, designation_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$user_id, $dept_id, $reg_id, $name, $gender, $phone, $email, $photo_path, $address, $designation_id]);

            $conn->commit();

            // Send initial OTP email to the new staff
            require_once '../../includes/mailer.php';
            $otp = sprintf("%06d", mt_rand(1, 999999));
            
            sendOTP($email, $otp, 'Welcome to Student Apt Portal - Your First Login OTP');

            header("Location: list.php?msg=Staff added successfully. An onboarding OTP has been sent to $email.");
            exit();
        }
        catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}
require_once '../../includes/header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-person-plus-fill fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Add Staff Member</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Register a new faculty member to the system</p>
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
                            <label class="form-label">Staff Photo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Staff Reg ID</label>
                            <input type="text" class="form-control" name="staff_reg_id" placeholder="e.g. STF001" required>
                        </div>
                    </div>
                    <!-- Username and Password fields removed - Login uses Register ID + OTP -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" class="form-control" name="name" placeholder="e.g. John Smith" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="dept_id" required>
                                <option value="">-- Select Dept --</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['dept_id']; ?>"><?php echo htmlspecialchars($d['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <select class="form-select" name="designation_id" required>
                                <option value="">-- Select Designation --</option>
                                <?php foreach ($designations as $ds): ?>
                                    <option value="<?php echo $ds['designation_id']; ?>"><?php echo htmlspecialchars($ds['designation_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" placeholder="staff@college.edu"></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" placeholder="e.g. 9876543210" pattern="[0-9]{10}" maxlength="10" minlength="10" title="Please enter exactly 10 digits" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required></div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12"><label class="form-label">Correspondence Address</label><textarea class="form-control" name="address" rows="2" placeholder="Full postal address"></textarea></div>
                    </div>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Register Staff</button>
                    <a href="list.php" class="btn btn-outline-secondary px-4">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
