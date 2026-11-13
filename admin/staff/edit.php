<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $_GET['id'];
$depts = $conn->query("SELECT * FROM Department WHERE status = 1 ORDER BY dept_name ASC")->fetchAll();
$designations = $conn->query("SELECT * FROM Designation WHERE status = 1 ORDER BY designation_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $reg_id = trim($_POST['staff_reg_id']);
    $username = $reg_id; // Keep username in sync with Reg ID
    $dept_id = (int)$_POST['dept_id'];
    $designation_id = (int)$_POST['designation_id'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $phone = trim($_POST['phone']);
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
    if (strlen($phone) !== 10 || !is_numeric($phone)) {
        $error = "Mobile number must be exactly 10 digits.";
    } else {
        try {
            $conn->beginTransaction();

            // Get user_id first
            $stmt = $conn->prepare("SELECT user_id FROM Staff WHERE staff_id = ?");
            $stmt->execute([$id]);
            $user_id = $stmt->fetchColumn();

            // Update Users table
            $stmt = $conn->prepare("UPDATE Users SET username = ?, status = ? WHERE user_id = ?");
            $stmt->execute([$username, $status, $user_id]);

            // Update Staff table
            $stmt = $conn->prepare("UPDATE Staff SET staff_reg_id = ?, staff_name = ?, gender = ?, mobile_no = ?, email = ?, photo = ?, address = ?, designation_id = ?, status = ? WHERE staff_id = ?");
            $stmt->execute([$reg_id, $name, $gender, $phone, $email, $photo_path, $address, $designation_id, $status, $id]);

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

try {
    $stmt = $conn->prepare("SELECT s.*, u.username FROM Staff s JOIN Users u ON s.user_id = u.user_id WHERE s.staff_id = ?");
    $stmt->execute([$id]);
    $staff = $stmt->fetch();
    if (!$staff) {
        header("Location: list.php?error=Staff member not found.");
        exit();
    }
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
?>
<div class="row justify-content-center mt-3">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header py-4 px-4 d-flex align-items-center gap-3 border-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <div class="rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 54px; height: 54px; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
                    <i class="bi bi-pencil-square fs-3 text-white"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-white mb-1" style="font-family:'Outfit',sans-serif; letter-spacing:-0.5px;">Edit Staff Member</h4>
                    <p class="text-white text-opacity-80 small mb-0 fw-medium">Update faculty details and permissions</p>
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
                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-md-3 text-center">
                            <?php if ($staff['photo']): ?>
                                <img src="../../<?php echo htmlspecialchars($staff['photo']); ?>" class="rounded-circle border shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded-circle border d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px;">
                                    <i class="bi bi-person text-muted" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-bold">Update Photo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                            <div class="form-text small">Leave blank to keep current photo.</div>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. John Smith" value="<?php echo htmlspecialchars($staff['staff_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Staff Reg ID</label>
                            <input type="text" class="form-control" name="staff_reg_id" placeholder="e.g. STF001" value="<?php echo htmlspecialchars($staff['staff_reg_id']); ?>" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <!-- Username hidden - synced with Reg ID -->
                            <label class="form-label">Department</label>
                            <select class="form-select bg-light" id="dept_id" disabled>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['dept_id']; ?>" <?php echo($d['dept_id'] == $staff['dept_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <select class="form-select" name="designation_id" required>
                                <?php foreach ($designations as $ds): ?>
                                    <option value="<?php echo $ds['designation_id']; ?>" <?php echo($ds['designation_id'] == $staff['designation_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ds['designation_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="1" <?php echo $staff['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $staff['status'] == 0 ? 'selected' : ''; ?>>Deactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="Male" <?php echo ($staff['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($staff['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($staff['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="staff@college.edu" value="<?php echo htmlspecialchars($staff['email']); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" placeholder="e.g. 9876543210" value="<?php echo htmlspecialchars($staff['mobile_no']); ?>" pattern="[0-9]{10}" maxlength="10" minlength="10" title="Please enter exactly 10 digits" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Correspondence Address</label>
                            <textarea class="form-control" name="address" rows="3" placeholder="Full postal address"><?php echo htmlspecialchars($staff['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold px-4">Update Staff</button>
                        <a href="list.php" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
