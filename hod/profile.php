<?php
require_once '../includes/auth.php';
requireRole('HOD'); // HOD specific access
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT s.*, d.dept_name, u.username 
                           FROM Staff s
                           JOIN Department d ON s.dept_id = d.dept_id
                           JOIN Users u ON s.user_id = u.user_id
                           WHERE s.user_id = ?");
    $stmt->execute([$user_id]);
    $staff = $stmt->fetch();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../includes/hod_header.php';
?>

<div class="row g-4 mb-5 mt-4 justify-content-center">
    <!-- Header Section -->
    <div class="col-md-10 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 overflow-hidden position-relative">
            <div class="position-absolute top-0 end-0 p-4 opacity-10 text-primary">
                <i class="bi bi-person-badge-fill" style="font-size: 8rem;"></i>
            </div>
            <div class="position-relative z-1">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                        <i class="bi bi-person-circle fs-2"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($staff['staff_name']); ?></h2>
                        <h6 class="text-primary fw-bold mb-0 text-uppercase tracking-wider">Head of Department</h6>
                    </div>
                </div>

                <div class="row g-4 mt-2">
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 border h-100">
                            <span class="small fw-bold text-muted text-uppercase d-block mb-1">Department</span>
                            <span class="fw-bold text-dark fs-5 d-flex align-items-center">
                                <i class="bi bi-building-fill me-2 text-primary opacity-75"></i> <?php echo htmlspecialchars($staff['dept_name']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 border h-100">
                            <span class="small fw-bold text-muted text-uppercase d-block mb-1">User Credentials</span>
                            <span class="fw-bold text-dark fs-5 font-monospace d-flex align-items-center">
                                <i class="bi bi-shield-lock-fill me-2 text-primary opacity-75"></i> <?php echo htmlspecialchars($staff['username']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 border h-100 overflow-hidden">
                            <span class="small fw-bold text-muted text-uppercase d-block mb-1">Email Address</span>
                            <span class="fw-bold text-dark fs-6 d-flex align-items-center">
                                <i class="bi bi-envelope-fill me-2 text-primary opacity-75 flex-shrink-0"></i> 
                                <span class="text-break"><?php echo htmlspecialchars($staff['email'] ?: 'Not Provided'); ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 border h-100 overflow-hidden">
                            <span class="small fw-bold text-muted text-uppercase d-block mb-1">Mobile Number</span>
                            <span class="fw-bold text-dark fs-6 font-monospace d-flex align-items-center">
                                <i class="bi bi-telephone-fill me-2 text-primary opacity-75 flex-shrink-0"></i> 
                                <span class="text-break"><?php echo htmlspecialchars($staff['mobile_no'] ?: 'Not Provided'); ?></span>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="bg-light p-3 rounded-3 border h-100">
                            <span class="small fw-bold text-muted text-uppercase d-block mb-1">Gender</span>
                            <span class="fw-bold text-dark fs-6 d-flex align-items-center">
                                <i class="bi bi-gender-ambiguous me-2 text-primary opacity-75"></i> <?php echo htmlspecialchars($staff['gender'] ?? 'Not Specified'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4 opacity-10">
                <div class="d-flex justify-content-between align-items-center text-muted small">
                    <span class="fw-bold text-primary"><i class="bi bi-info-circle me-1"></i> Data managed by Administration</span>
                    <span>Staff ID: #<?php echo str_pad($staff['staff_id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
