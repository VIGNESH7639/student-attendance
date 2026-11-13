<?php
require_once '../../includes/auth.php';
requireRole('Admin');
require_once '../../config/database.php';

$dept_id = $_GET['dept_id'] ?? null;

try {
    // Fetch all active departments for the filter with staff counts
    $depts = $conn->query("SELECT d.*, (SELECT COUNT(*) FROM Staff s WHERE s.dept_id = d.dept_id) as staff_count 
                           FROM Department d 
                           WHERE d.status = 1 
                           ORDER BY d.dept_name ASC")->fetchAll();

    // Prepare SQL for staff members
    $sql = "SELECT s.*, d.dept_name, u.username, des.designation_name 
            FROM Staff s 
            JOIN Department d ON s.dept_id = d.dept_id 
            LEFT JOIN users u ON s.user_id = u.user_id
            LEFT JOIN Designation des ON s.designation_id = des.designation_id";

    $params = [];
    if ($dept_id) {
        $sql .= " WHERE s.dept_id = ?";
        $params[] = $dept_id;
    }

    $sql .= " ORDER BY s.staff_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $staff_members = $stmt->fetchAll();
}
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Faculty & Staff</h2>
        <p class="text-muted mb-0 small">Manage institutional staff profiles and designations.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 shadow-sm fw-bold border-2">
            <i class="bi bi-printer me-2"></i> Print List
        </button>
        <a href="add.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="bi bi-person-plus-fill me-2"></i> Add Staff
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-body p-4 bg-white">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted text-uppercase">Department Filter</label>
                <select name="dept_id" class="form-select bg-light border-0 py-2 rounded-3" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['dept_id']; ?>" <?php echo $dept_id == $d['dept_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['dept_name']); ?> (<?php echo $d['staff_count']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Filter</button>
                <a href="list.php" class="btn btn-outline-secondary w-100 rounded-pill py-2 text-decoration-none text-center fw-bold">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-container">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small fw-bold text-uppercase border-bottom">
                <tr>
                    <th class="ps-4 py-3 text-center">S.No</th>
                    <th class="py-3">Profile</th>
                    <th class="py-3">Staff Details</th>
                    <th class="py-3">Department</th>
                    <th class="py-3">Contact</th>
                    <th class="py-3">Address</th>
                    <th class="py-3">Roles</th>
                    <th class="py-3 text-center">Status</th>
                    <th class="pe-4 py-3 text-end">Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($staff_members)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No staff found for the selected criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php $sno = 1; foreach ($staff_members as $s): ?>
                            <tr>
                                <td class="ps-4 text-center text-muted small"><?php echo $sno++; ?></td>
                                <td>
                                    <?php if ($s['photo']): ?>
                                        <img src="../../<?php echo htmlspecialchars($s['photo']); ?>" class="rounded-pill border shadow-sm" style="width: 45px; height: 45px; object-fit: cover;" alt="Staff">
                                    <?php else: ?>
                                        <div class="bg-light rounded-pill border d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                            <i class="bi bi-person text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['staff_name']); ?></div>
                                    <div class="small text-muted text-uppercase fw-bold text-primary"><?php echo htmlspecialchars($s['staff_reg_id']); ?></div>
                                    <div class="small text-muted">
                                        <?php if ($s['username']): ?>
                                            <i class="bi bi-person-circle me-1 opacity-50"></i><?php echo htmlspecialchars($s['username']); ?>
                                        <?php else: ?>
                                            <span class="text-warning small italic"><i class="bi bi-exclamation-circle me-1"></i>No Account</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark"><?php echo htmlspecialchars($s['dept_name']); ?></div>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark"><i class="bi bi-phone me-1 opacity-50"></i> <?php echo htmlspecialchars($s['mobile_no'] ?? '-'); ?></div>
                                    <div class="small text-muted"><i class="bi bi-envelope me-1 opacity-50"></i> <?php echo htmlspecialchars($s['email'] ?? '-'); ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted text-wrap" style="max-width: 250px;">
                                        <?php echo htmlspecialchars($s['address'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border-0 rounded-pill px-3"><?php echo htmlspecialchars($s['designation_name']); ?></span></td>
                                <td class="text-center">
                                    <span class="badge <?php echo $s['status'] == 1 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'; ?> rounded-pill px-3">
                                        <?php echo $s['status'] == 1 ? 'Active' : 'Deactive'; ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="edit.php?id=<?php echo $s['staff_id']; ?>" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 shadow-sm">
                                        <i class="bi bi-pencil-fill px-1"></i>
                                    </a>
                                </td>
                            </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
