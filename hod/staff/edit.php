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

// Fetch current details
try {
    $stmt = $conn->prepare("SELECT s.*, u.username FROM Staff s JOIN Users u ON s.user_id = u.user_id WHERE s.staff_id = ? AND s.dept_id = ?");
    $stmt->execute([$id, $dept_id]);
    $staff = $stmt->fetch();
    if (!$staff) {
        header("Location: list.php?error=Staff not found.");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_id = trim($_POST['staff_reg_id']);
    $username = $reg_id; // Keep username in sync with Reg ID
    $designation_id = (int)$_POST['designation_id'];
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $address = trim($_POST['address']);
    $gender = $_POST['gender'] ?? 'Male';
    $status = (int)$_POST['status'];

    // Handle Photo Upload
    $photo_path = $_POST['existing_photo'] ?? null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/staff/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $file_name = $reg_id . '_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            // Delete old photo if exists
            if (!empty($photo_path) && file_exists('../../' . $photo_path)) {
                unlink('../../' . $photo_path);
            }
            $photo_path = 'assets/images/staff/' . $file_name;
        }
    }

    // Validation
    if (strlen($mobile) !== 10 || !is_numeric($mobile)) {
        $error = "Mobile number must be exactly 10 digits.";
    } else {
        try {
            $conn->beginTransaction();

            // 1. Update User
            $stmt = $conn->prepare("UPDATE Users SET username = ?, status = ? WHERE user_id = ?");
            $stmt->execute([$username, $status, $staff['user_id']]);

            // 2. Update Staff
            $stmt = $conn->prepare("UPDATE Staff SET staff_reg_id = ?, staff_name = ?, gender = ?, mobile_no = ?, email = ?, photo = ?, address = ?, designation_id = ?, status = ? WHERE staff_id = ?");
            $stmt->execute([$reg_id, $name, $gender, $mobile, $email, $photo_path, $address, $designation_id, $status, $id]);

            $conn->commit();
            header("Location: list.php?msg=Staff member updated successfully.");
            exit();
        }
        catch (PDOException $e) {
            $conn->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

$designations = $conn->query("SELECT * FROM Designation WHERE status = 1 ORDER BY designation_name ASC")->fetchAll();
$dept_stmt = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
$dept_stmt->execute([$dept_id]);
$dept_name = $dept_stmt->fetchColumn();

require_once '../../includes/hod_header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-pencil-square fs-3 text-white"></i>
                </div>
                <div class="flex-fill">
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Edit Staff Member</h4>
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
                    <input type="hidden" name="existing_photo" value="<?php echo $staff['photo']; ?>">
                    
                    <div class="row g-3 mb-4 align-items-center bg-light p-3 rounded-4 mx-0">
                        <div class="col-auto">
                            <?php if ($staff['photo']): ?>
                                <img src="../../<?php echo $staff['photo']; ?>" class="rounded-circle shadow-sm border border-2 border-white" style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px;">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col">
                            <label class="form-label fw-bold small text-muted text-uppercase mb-1">Update Photograph</label>
                            <input type="file" class="form-control rounded-3" name="photo" accept="image/*">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Staff Reg ID</label>
                            <input type="text" class="form-control rounded-3" name="staff_reg_id" value="<?php echo htmlspecialchars($staff['staff_reg_id']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Full Name</label>
                            <input type="text" class="form-control rounded-3" name="name" value="<?php echo htmlspecialchars($staff['staff_name']); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Gender</label>
                            <select class="form-select rounded-3" name="gender" required>
                                <option value="Male" <?php echo $staff['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $staff['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Others" <?php echo $staff['gender'] == 'Others' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <!-- Username hidden - synced with Reg ID -->
                            <div class="alert alert-light border rounded-3 py-2 small mb-0">
                                <strong>Staff ID:</strong> <?php echo htmlspecialchars($staff['staff_reg_id']); ?> 
                                <span class="text-muted ms-2">(Used for portal login)</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-9">
                            <label class="form-label fw-medium">Designation</label>
                            <select class="form-select rounded-3" name="designation_id" required>
                                <?php foreach ($designations as $d): ?>
                                    <option value="<?php echo $d['designation_id']; ?>" <?php echo $d['designation_id'] == $staff['designation_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['designation_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Status</label>
                            <select class="form-select rounded-3" name="status">
                                <option value="1" <?php echo $staff['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $staff['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Email Address</label>
                            <input type="email" class="form-control rounded-3" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Mobile Number</label>
                            <input type="text" class="form-control rounded-3" name="mobile" value="<?php echo htmlspecialchars($staff['mobile_no']); ?>" maxlength="10">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Residential Address</label>
                        <textarea class="form-control rounded-3" name="address" rows="3"><?php echo htmlspecialchars($staff['address']); ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4 flex-fill py-3 shadow-sm rounded-3">
                            <i class="bi bi-save me-2"></i>Update Staff Details
                        </button>
                        <a href="list.php" class="btn btn-outline-secondary px-4 py-3 rounded-3">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
