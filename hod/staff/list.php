<?php
require_once '../../includes/auth.php';
requireHOD();
require_once '../../config/database.php';
require_once '../../includes/hod_header.php';

$dept_id = $_SESSION['dept_id'];

try {
    $stmt = $conn->prepare("SELECT s.*, u.username, d.designation_name 
                            FROM Staff s 
                            LEFT JOIN Users u ON s.user_id = u.user_id 
                            LEFT JOIN Designation d ON s.designation_id = d.designation_id
                            WHERE s.dept_id = ?
                            ORDER BY s.staff_name ASC");
    $stmt->execute([$dept_id]);
    $staff_members = $stmt->fetchAll();

    // Fetch Department Name
    $dept_stmt = $conn->prepare("SELECT dept_name FROM Department WHERE dept_id = ?");
    $dept_stmt->execute([$dept_id]);
    $dept_name = $dept_stmt->fetchColumn();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="row g-4 mb-4 mt-2">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <h2 class="font-heading fw-bold mb-0 text-dark">Department Faculty</h2>
                <p class="text-muted mb-0">Manage staff profiles for <strong><?php echo htmlspecialchars($dept_name); ?></strong></p>
            </div>
            <a href="add.php" class="btn btn-premium btn-premium-primary px-4">
                <i class="bi bi-person-plus-fill"></i> Add Faculty
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
        <i class="bi bi-check-circle-fill me-2"></i>
        <span><?php echo htmlspecialchars($_GET['msg']); ?></span>
    </div>
<?php endif; ?>

<div class="table-responsive-premium shadow-sm mb-5">
    <table class="table table-premium align-middle mb-0">
        <thead>
            <tr>
                <th class="ps-4 text-center" style="width: 80px;">S.No</th>
                <th>Profile Info</th>
                <th>Designation</th>
                <th>Contact details</th>
                <th>Location</th>
                <th class="text-center">Status</th>
                <th class="pe-4 text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staff_members)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-person-x display-4 mb-3 d-block opacity-25"></i>
                        No staff found in your department.
                    </td>
                </tr>
            <?php else: ?>
                <?php $sno = 1; foreach ($staff_members as $s): ?>
                    <tr>
                        <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                        <td>
                            <div class="d-flex align-items-center py-1">
                                <?php if ($s['photo']): ?>
                                    <img src="../../<?php echo htmlspecialchars($s['photo']); ?>" class="rounded-pill border shadow-sm me-3" style="width: 48px; height: 48px; object-fit: cover;" alt="Staff">
                                <?php else: ?>
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-pill d-flex align-items-center justify-content-center border border-primary border-opacity-10 me-3 shadow-sm" style="width: 48px; height: 48px;">
                                        <i class="bi bi-person-fill fs-4"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['staff_name']); ?></div>
                                    <div class="small fw-bold text-primary opacity-75">ID: #<?php echo htmlspecialchars($s['staff_reg_id']); ?></div>
                                    <?php if ($s['username']): ?>
                                        <div class="x-small text-muted font-monospace" style="font-size: 0.7rem;">@<?php echo htmlspecialchars($s['username']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border-0 px-2 py-1 rounded-pill small fw-bold"><?php echo htmlspecialchars($s['designation_name']); ?></span>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="small text-dark fw-medium"><i class="bi bi-phone me-2 text-primary"></i><?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></span>
                                <span class="small text-muted"><i class="bi bi-envelope me-2 text-primary"></i><?php echo htmlspecialchars($s['email'] ?? '-'); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="small text-muted text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($s['address'] ?? ''); ?>">
                                <i class="bi bi-geo-alt me-1 text-primary"></i>
                                <?php echo htmlspecialchars($s['address'] ?? 'N/A'); ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($s['status'] == 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 border-0 fw-bold" style="font-size: 0.65rem;">ACTIVE</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 border-0 fw-bold" style="font-size: 0.65rem;">INACTIVE</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 text-end">
                            <a href="edit.php?id=<?php echo $s['staff_id']; ?>" class="btn btn-sm btn-light border rounded-pill px-3 py-1 shadow-sm fs-7 fw-bold transition align-items-center d-inline-flex gap-1" style="font-size: 0.75rem;">
                                <i class="bi bi-pencil-square text-primary"></i> Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>
